<?php

namespace App\Services;

use App\Repositories\GodownAnalysisRepository;
use App\Models\Element;
use App\Models\GodownAnalysis;
use App\Models\GodownAnalysisItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GodownAnalysisService
{
    protected GodownAnalysisRepository $repository;

    public function __construct(GodownAnalysisRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get GRN data and process parameters for Godown Analysis
     * 
     * @param int|string $grnSerial
     * @param int $companyId
     * @param int $financialYearId
     * @return array
     */
    public function getGrnData($grnSerial, $companyId, $financialYearId): array
    {
        $data = $this->repository->getGrnDataByGrnSerial($grnSerial, $companyId, $financialYearId);

        if (empty($data['grn']) || isset($data['message'])) {
            return $data;
        }

        $rawParameters = collect($data['parameters'] ?? []);

        $existingAnalysis = $data['existing_analysis'];

        // Map the parameters to a standardized array format
        $processedParameters = $rawParameters->map(function ($param) use ($existingAnalysis) {
            $existingItem = null;
            if ($existingAnalysis && $existingAnalysis->details) {
                $existingItem = $existingAnalysis->details->where('element_id', $param->element_id)->first();
            }

            return [
                'element' => $param->element_id,
                'element_name' => $param->element->name ?? "Missing (ID: {$param->element_id})",
                'element_range' => $param->element->range ?? null,
                'guarantee' => $existingItem ? $existingItem->guarantee : $param->guarantee,
                'actual_val' => $existingItem ? $existingItem->actual : null,
                'diff_val' => $existingItem ? $existingItem->difference : null,
                'rebate_val' => $existingItem ? $existingItem->rebate : null,
                'premium_val' => $existingItem ? $existingItem->premium : null,
                'ranges' => $param->parameterDetails->map(function ($rd) use ($param) {
                    return [
                        'from' => $rd->from,
                        'to' => $rd->to,
                        'difference' => $rd->difference,
                        'rebate' => $rd->rebate,
                        'premium' => $rd->premium,
                        'element_range' => $param->element->range ?? null
                    ];
                }),
                'rebate_percentage_val' => $existingItem ? $existingItem->rebate_percentage : null,
            ];
        });

        // Inject Mandatory Elements if missing
        $mandatoryNames = ['TORN BAGS'];
        $existingNames = $processedParameters->pluck('element_name')->map(fn($n) => trim(strtoupper($n)))->toArray();

        foreach ($mandatoryNames as $mName) {
            if (!in_array($mName, $existingNames)) {
                $element = Element::where('name', 'like', "%{$mName}%")->first();
                if ($element) {
                    $existingItem = null;
                    if ($existingAnalysis && $existingAnalysis->details) {
                        $existingItem = $existingAnalysis->details->where('element_id', $element->id)->first();
                    }
                    $processedParameters->push([
                        'element' => $element->id,
                        'element_name' => $element->name,
                        'element_range' => $element->range,
                        'guarantee' => $existingItem ? $existingItem->guarantee : (($mName === 'TORN BAGS') ? '1.00' : '0.00'),
                        'actual_val' => $existingItem ? $existingItem->actual : null,
                        'diff_val' => $existingItem ? $existingItem->difference : null,
                        'rebate_val' => $existingItem ? $existingItem->rebate : null,
                        'premium_val' => $existingItem ? $existingItem->premium : null,
                        'ranges' => collect([]),
                        'rebate_percentage_val' => $existingItem ? $existingItem->rebate_percentage : null,
                    ]);
                }
            }
        }

        // Define strict element display order
        $order = [
            'MOISTURE' => 1,
            'ALBUMIN' => 2,
            'FIBER' => 3,
            'SILICA' => 4,
            'TORN BAGS' => 9999, // ALWAYS AT THE VERY END
        ];

        // Sort parameters according to the defined order
        $sortedParameters = $processedParameters->sortBy(function ($p) use ($order) {
            $name = trim(strtoupper($p['element_name'] ?? ''));
            // Unknown elements get 999, so they appear before TORN BAGS (9999)
            return $order[$name] ?? 999;
        })->values();

        $data['parameters'] = $sortedParameters;

        return $data;
    }

    /**
     * Store Godown Analysis data
     *
     * @param array $data
     * @return GodownAnalysis
     */
    public function storeAnalysis(array $data , int $companyId, int $financialYearId )
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $analysis = GodownAnalysis::create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'grn_id' => $data['grn_id'],
                'rebate_total' => $data['rebate_total'] ?? 0,
                'premium_total' => $data['premium_total'] ?? 0,
                'rebate_percentage' => $data['rebate_percentage'] ?? 0,
                'created_by' => current_user_id(),
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    GodownAnalysisItem::create([
                        'godown_analysis_id' => $analysis->id,
                        'element_id' => $item['element_id'],
                        'guarantee' => $item['guarantee'] ?? 0,
                        'actual' => $item['actual'] ?? 0,
                        'difference' => $item['diff'] ?? 0,
                        'rebate' => $item['rebate'] ?? 0,
                        'rebate_percentage' => $item['rebate_percentage'] ?? 0,
                        'premium' => $item['premium'] ?? 0,
                    ]);
                }
            }

            return $analysis;
        });
    }

    /**
     * Update Godown Analysis data
     *
     * @param GodownAnalysis $analysis
     * @param array $data
     * @return GodownAnalysis
     */
    public function updateAnalysis(GodownAnalysis $analysis, array $data)
    {
        return DB::transaction(function () use ($analysis, $data) {
            $analysis->update([
                'rebate_total' => $data['rebate_total'] ?? 0,
                'premium_total' => $data['premium_total'] ?? 0,
                'rebate_percentage' => $data['rebate_percentage'] ?? 0,
                'updated_by' => current_user_id(),
            ]);

            // Clear old items and recreate
            $analysis->details()->delete();

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    GodownAnalysisItem::create([
                        'godown_analysis_id' => $analysis->id,
                        'element_id' => $item['element_id'],
                        'guarantee' => $item['guarantee'] ?? 0,
                        'actual' => $item['actual'] ?? 0,
                        'difference' => $item['diff'] ?? 0,
                        'rebate' => $item['rebate'] ?? 0,
                        'rebate_percentage' => $item['rebate_percentage'] ?? 0,
                        'premium' => $item['premium'] ?? 0,
                    ]);
                }
            }

            return $analysis;
        });
    }

    /**
     * Get paginated Godown Analysis list data
     */
    public function getAnalysisList(array $filters, int $companyId, int $financialYearId)
    {
        $query = GodownAnalysis::with(['grn.account', 'grn.details.destination', 'creator', 'updator'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            
            $query->whereHas('grn', function($q) use ($startDate, $endDate) {
                $q->whereBetween('grn_date', [$startDate, $endDate]);
            });
        }

        if (!empty($filters['supplier_id'])) {
            $query->whereHas('grn', function($q) use ($filters) {
                $q->where('account_id', $filters['supplier_id']);
            });
        }
        
        if (!empty($filters['account_id'])) {
            $query->whereHas('grn', function($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });
        }

        if (!empty($filters['destination_id'])) {
            $query->whereHas('grn.details', function($q) use ($filters) {
                $q->where('destination_id', $filters['destination_id']);
            });
        }

        if (!empty($filters['grn_id'])) {
            $query->where('grn_id', $filters['grn_id']);
        }

        $size = $filters['size'] ?? 50;
        $records = $query->orderBy('id', 'desc')->paginate($size);

        $permissions = userPermissions([
            'godown_analysis.view',
            'godown_analysis.update',
            'godown_analysis.print',
            'godown_analysis.delete',
        ], true);

        $data = $records->map(function ($row) use ($permissions) {
            return [
                'id'            => $row->id,
                'grn_serial'    => $row->grn->grn_serial ?? '--',
                'grn_date'      => $row->grn ? (format_date($row->grn->grn_date) ?? '--') : '--',
                'supplier_name' => $row->grn->account->name ?? '--',
                'city'          => $row->grn->account->city ?? '--',
                'rebate_amount' => $row->rebate_total ?? 0,
                'created_by'    => $row->creator->name ?? '--',
                'updated_by'    => $row->updator->name ?? '--',
            ];
        });

        return [
            'data'         => $data,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'grand_total'  => GodownAnalysis::where('company_id', $companyId)->where('financial_year_id', $financialYearId)->count(),
            'permissions'  => $permissions,
        ];
    }
}

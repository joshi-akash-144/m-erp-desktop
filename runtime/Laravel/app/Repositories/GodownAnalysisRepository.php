<?php

namespace App\Repositories;

use App\Models\DairyParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Grn;
use Carbon\Carbon;
use App\Models\GodownAnalysis;

class GodownAnalysisRepository extends BaseRepository
{
    public function __construct(GodownAnalysis $godownAnalysis)
    {
        parent::__construct($godownAnalysis);

        $this->dairyParameter = new DairyParameter();
        $this->godownAnalysis = new GodownAnalysis();
    }

    /**
     * Get Grn and Parameter data based on Grn Serial
     * 
     * @param int|string $grnSerial
     * @param int $companyId
     * @param int $financialYearId
     * @return array
     */
    public function getGrnDataByGrnSerial($grnSerial, int $companyId, int $financialYearId): array
    {        
        // Remove dot to find the original Grn
        $grnSerial = str_replace('.', '', (string)$grnSerial);

        // 1. Fetch Grn
        $grn = Grn::with(['account:id,name,city', 'details:id,grn_id,condition_id,rate,inclusive_rate,quantity,party_quantity', 'details.condition:id,name'])
            ->where('grn_serial', $grnSerial)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->first();

        if (!$grn) {
            return [ 'message' => 'GRN not found for this serial number'];
        }

        // 2. Get Parameters based on condition_id from Grn
        $conditionId = $grn->details->pluck('condition_id')->filter()->first();          
          
        $parameters = collect();
        if ($conditionId) {
            $parameters = DairyParameter::with(['element', 'parameterDetails']) 
                ->where('condition_id', $conditionId)
                ->where('company_id', $companyId)
                ->get();            

            if ($parameters->isEmpty()) {
                return [ 'message' => 'Analysis Parameters data not found'];
            }
        }else{
            return [ 'message' => 'Analysis Parameters data not found'];
        }

        // 3. Check for existing Analysis Results (History)
        $existingAnalysis = GodownAnalysis::with('details')
            ->where('grn_id', $grn->id)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->latest()
            ->first();

        return [
            'grn' => $grn,
            'parameters' => $parameters,
            'existing_analysis' => $existingAnalysis
        ];
    }

    /**
     * Get Filtered Records for Godown Analysis Register (Using Eloquent Models)
     */
    public function getAnalysisListData(array $filters, int $companyId, int $financialYearId, $paginate = false)
    {
        $query = GodownAnalysis::select([
            'id', 
            'company_id', 
            'financial_year_id', 
            'grn_id',                                       
            'rebate_total',
        ])
        ->with([                               
            'grn:id,account_id,grn_number,grn_date,grn_serial,reference_number,total_quantity,vehicle_number',
            'grn.account:id,name,city',                  
            'grn.details:id,grn_id,destination_id',
            'grn.details.destination:id,name',
        ])
        ->where('company_id', $companyId)
        ->where('financial_year_id', $financialYearId)
        ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
            $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
            $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');
            $q->whereHas('grn', fn($sub) => $sub->whereBetween('grn_date', [$start, $end]));
        })
        // party filter
        ->when(!empty($filters['account_id']) || !empty($filters['supplier_id']), function ($q) use ($filters) {
            $accountId = !empty($filters['account_id']) ? $filters['account_id'] : $filters['supplier_id'];
            $q->whereHas('grn', fn($sub) => $sub->where('account_id', $accountId));
        })
        // destination filter
        ->when(!empty($filters['destination_id']), function ($q) use ($filters) {
            $q->whereHas('grn.details', fn($sub) => $sub->where('destination_id', $filters['destination_id']));
        })
        ->orderBy('id', 'DESC');
        if ($paginate) {
            $perPage = (int)($filters['size'] ?? 50);
            return $query->paginate($perPage > 0 ? $perPage : ($query->count() ?: 1));
        }
        return $query->get();
    }

    public function getAnalysisRebatePendingData(array $filters, int $companyId, int $financialYearId, $paginate = false)
    {        
        // Handle all statuses using Grn as base
        $query = Grn::select([
            'id', 
            'company_id', 
            'financial_year_id', 
            'account_id',       
            'grn_serial',   
            'grn_date',    
            'grn_number',
            'total_quantity',
            'vehicle_number',
        ])->with([                  
            'account:id,name,city',                         
            'details:id,grn_id,item_id,destination_id,condition_id,quantity', 
            'details.item:id,name', 
            'details.condition:id,name', 
            'details.destination:id,name'
        ])
        ->where('company_id', $companyId)
        ->where('financial_year_id', $financialYearId);
        
        $rebateStatus = $filters['rebate_status'] ?? null;

        if ($rebateStatus == 1) {
            // Status 1: GRNs that DON'T have Godown Analysis yet
            $query->whereNotIn('id', function($q) use ($companyId, $financialYearId) {
                $q->select('grn_id')
                  ->from('godown_analyses')
                  ->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId);
            });
        } elseif ($rebateStatus == 2) {
            // Status 2: Analysis is done but Rebate Total is empty or zero
            $query->whereIn('id', function($q) use ($companyId, $financialYearId) {
                $q->select('grn_id')
                  ->from('godown_analyses')
                  ->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId)
                  ->where(function($sub) {
                      $sub->whereNull('rebate_total')
                          ->orWhere('rebate_total', 0);
                  });
            });
        }

        // Apply Common Filters
        $query->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');
                $q->whereBetween('grn_date', [$start, $end]);
            })
            ->when(!empty($filters['account_id']), function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            })
            ->when(!empty($filters['destination_id']), function ($q) use ($filters) {
                $q->whereHas('details', fn($sub) => $sub->where('destination_id', $filters['destination_id']));
            })
            ->orderBy('id', 'DESC');

        return $paginate ? $query->paginate($filters['size'] ?? 50) : $query->get();
    }

    public function countAllAnalysis(int $companyId, int $financialYearId): int
    {
        return GodownAnalysis::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function countAllRebatePending(int $companyId, int $financialYearId): int
    {
        return Grn::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereNotIn('id', function($q) use ($companyId, $financialYearId) {
                $q->select('grn_id')
                  ->from('godown_analyses')
                  ->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId);
            })
            ->count();
    }
}
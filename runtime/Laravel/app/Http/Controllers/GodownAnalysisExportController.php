<?php

namespace App\Http\Controllers;

use App\Exports\GodownAnalysisExport;
use App\Models\GodownAnalysis;
use App\Models\Grn;
use App\Services\GodownAnalysisService;
use App\Services\CompanyService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\AjaxResponse;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class GodownAnalysisExportController extends Controller
{
    protected GodownAnalysisService $godownAnalysisService;
    protected CompanyService $companyService;

    public function __construct(
        GodownAnalysisService $godownAnalysisService,
        CompanyService $companyService
    ) {
        $this->godownAnalysisService = $godownAnalysisService;
        $this->companyService = $companyService;
    }
    
    /**
     * Display a listing of the resource for printing.
     */
    public function print(Request $request)
    {
        $filters = $request->only(
            'start_date',
            'end_date',
            'account_id',
            'supplier_id',
            'destination_id'
        );

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = GodownAnalysis::with(['grn.account', 'creator', 'updator'])
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

        $analyses = $query->orderBy('id', 'desc')->get();

        $company = $this->companyService->current($companyId);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $datePeriod = format_date($filters['start_date']) . ' To ' . format_date($filters['end_date']);
        } else {
            $fy = $company->currentFinancialYear;
            if ($fy) {
                $datePeriod = format_date($fy->start_date) . ' To ' . format_date($fy->end_date);
            }
        }

        $tableConfig = [
            'columns' => [
                ['label' => 'GRN Serial', 'width' => '15%'],
                ['label' => 'Date', 'width' => '10%'],
                ['label' => 'Supplier Name', 'width' => '30%'],
                ['label' => 'City', 'width' => '15%'],
                ['label' => 'Rebate Amount', 'width' => '15%', 'class' => 'text-end'],
            ]
        ];

        $html = view('company.pages.godown-analysis.print', compact('analyses', 'company', 'datePeriod', 'tableConfig'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function exportExcel(): JsonResponse
    {
        return $this->exportByFormat('xlsx');
    }

    public function exportCsv(): JsonResponse
    {
        return $this->exportByFormat('csv');
    }

    private function exportByFormat(string $format): JsonResponse
    {
        $filters = request()->only(
            'start_date',
            'end_date',
            'account_id',
            'supplier_id',
            'destination_id'
        );

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = GodownAnalysis::with(['grn.account'])
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

        $analyses = $query->orderBy('id', 'desc')->get();
        $company = $this->companyService->current($companyId);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $datePeriod = format_date($filters['start_date']) . ' To ' . format_date($filters['end_date']);
        } else {
            $fy = $company->currentFinancialYear;
            if ($fy) {
                $datePeriod = format_date($fy->start_date) . ' To ' . format_date($fy->end_date);
            }
        }

        $headings = ['GRN Serial', 'Date', 'Supplier Name', 'City', 'Rebate Amount'];

        $directory = 'godown_analysis_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
        $fileName = "{$companyNameSlug}_godown_analysis_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new GodownAnalysisExport($company, $analyses, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );
            
            return AjaxResponse::success(
                message: "Godown Analysis Exported successfully ({$format})",
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Export failed',
                errors: [$e->getMessage()],
            );
        }
    }
}

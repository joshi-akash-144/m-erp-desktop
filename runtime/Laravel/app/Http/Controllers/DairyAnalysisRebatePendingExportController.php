<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Repositories\DairyAnalysisRepository;
use App\Services\CompanyService;
use App\Services\DairyAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Exports\DairyAnalysisRebatePendingExport;
use Carbon\Carbon;

class DairyAnalysisRebatePendingExportController extends Controller
{
    protected DairyAnalysisRepository $repository;
    protected $companyService;
    protected $service;

    public function __construct(
        DairyAnalysisRepository $repository, 
        CompanyService $companyService,
        DairyAnalysisService $service
    ) {
        $this->middleware('permission:dairy_analysis_report.rebate_pending_print')->only('print');
        $this->middleware('permission:dairy_analysis_report.rebate_pending_export')->only(['exportExcel', 'exportCsv']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->service = $service;
    }

    public function print(Request $request): JsonResponse
    {      
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);
        
        $company = $this->companyService->current(company_id());
        $filters = $request->currentFilter ?? [];
        $currentYear = $company->currentFinancialYear;
               
        $finaldata = $this->service->getRebatePendingPrintData($request);
        // dd($finaldata);
         $startDate = !empty($filters['start_date']) 
            ? $filters['start_date'] 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";
               
        $data = [
            'companyName' => $company->name,
            'finaldata' => $finaldata,
            'orientation' => $request->input('orientation','portrait'),
            'gstNumber' => $company->gst_number,
            'datePeriod' => $datePeriod,
            'reportTitle' => 'Dairy Analysis Report Register',            
        ];

        $html = view('company.pages.dairy-analysis.rebate_pending_print', $data)->render();

        return AjaxResponse::success(
            message: 'Dairy Analysis data fetched successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request)
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request)
    {
        return $this->exportByFormat($request, 'csv');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $filters = $request->currentFilter ?? [];
        
        // Excel export now needs FLAT data rows
        $rows = $this->service->getRebatePendingExportData($filters, company_id(), financial_year_id());

        $currentYear = $company->currentFinancialYear;

        $startDate = !empty($filters['start_date']) 
            ? $filters['start_date'] 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
        $datePeriod = "$startDate to $endDate";

        $headings = [
            'Sr.No.',
            'Customer Name',
            'Destination',
            'PO No',
            'S.Bill No.',
            'Product Name',
            'S.Bill Date',
            'S.QTY',
            'S.Vehicle No',
            'GRN',
            'Days',
            'File No',
            'Party Bill No.',
            'Supplier Name',
            'Purchase QTY',
            'P.Vehicle No'
        ];

        $directory = 'master_reports';
        $fileName = 'dairy_analysis_' . now()->format('d_m_Y_His') . ".{$format}";
        
        if (!File::exists(storage_path("app/public/{$directory}"))) {
            File::makeDirectory(storage_path("app/public/{$directory}"), 0755, true);
        }
        
        try {
            Excel::store(
                new DairyAnalysisRebatePendingExport($company, $rows, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'Dairy Analysis Rebate Pending Exported successfully',
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Export failed: ' . $e->getMessage()
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\FreightExport;
use App\Helpers\AjaxResponse;
use App\Repositories\FreightRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\FreightService;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Services\FreightInvoiceService;
use App\Models\Freight;

class FreightInvoiceExportController extends Controller
{
    protected FreightInvoiceService $freightInvoiceService;
    protected CompanyService $companyService;

    public function __construct(FreightInvoiceService $freightInvoiceService, CompanyService $companyService)
    {
        $this->middleware('permission:freight_invoice.print')->only('print');
        $this->middleware('permission:freight_invoice.export')->only(['exportExcel', 'exportCsv']);
        $this->freightInvoiceService = $freightInvoiceService;
        $this->companyService = $companyService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter;
        $freightInvoices = $this->freightInvoiceService->freightInvoiceList(array_merge($filters ?? [], [
            'company_id' => company_id(),
            'financial_year_id' => financial_year_id(),
            'page' => 1,
            'size' => 10000 // To get all for printing
        ]));
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        
        $freightInvoicesData = json_decode(json_encode($freightInvoices['data'] ?? []));

        $tableConfig = [
            "columns" => [
                ["label" => "Bill No.", "class" => "text-end", "width" => "10%"],
                ["label" => "Invoice Date", "class" => "text-center", "width" => "15%"],
                ["label" => "Customer Name", "class" => "text-start", "width" => "40%"],
                ["label" => "Narration", "class" => "text-start", "width" => "20%"],                
                ["label" => "Total Amount", "class" => "text-end fw-bold",   "width" => "15%"],
            ],
            "body" => []
        ];
        
        $startDate = !empty($filters['start_date']) 
                ? $filters['start_date'] 
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');
                
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";
       

        $data = [
            'company' => $company,
            'companyName' => $company->name ?? '',
            'currentYear' => $currentYear,
            'allData' => $freightInvoicesData,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'landscape'),
            'datePeriod' => $datePeriod,
        ];
        $html = view('company.pages.freight-invoice.print', $data)->render();
        return AjaxResponse::success(
            message: 'Freight Invoice fetched successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {        
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'csv');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {               
        try {

            $filters = array_merge($request->currentFilter ?? [], [
                'company_id'        => company_id(),
                'financial_year_id' => financial_year_id(),
                'page' => 1,
                'size' => 100000
            ]);
   
            $freights = $this->freightInvoiceService->freightInvoiceList($filters);
            
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;
                
            $startDate = !empty($request->currentFilter['start_date']) 
                ? Carbon::parse($request->currentFilter['start_date'])->format('d-m-Y') 
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');
                
            $endDate = !empty($request->currentFilter['end_date']) 
                ? Carbon::parse($request->currentFilter['end_date'])->format('d-m-Y') 
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
            $datePeriod = "$startDate to $endDate";            

            $headings = [
                'Bill No.',
                'Date',                 
                'Customer',
                'Product',
                'Zone',
                'Qty.',
                'Rate',
                'Amount',               
            ];
            
            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }
            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_freight_invoice_" . now()->format('d_m_Y_His') . ".{$format}";

            $exportData = collect($freights['data'] ?? []);

            Excel::store(
                new \App\Exports\FreightInvoiceExport($company, $exportData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Freight Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
     
}

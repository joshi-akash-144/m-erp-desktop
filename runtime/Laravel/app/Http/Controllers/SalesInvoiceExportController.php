<?php

namespace App\Http\Controllers;

use App\Exports\SalesInvoiceExport;
use App\Helpers\AjaxResponse;
use App\Repositories\SalesInvoiceRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\SalesInvoiceService;
use Illuminate\View\View;
use Carbon\Carbon;

class SalesInvoiceExportController extends Controller
{
    protected SalesInvoiceRepository $repository;
    protected int $companyId;
    protected  $companyService;
    protected $salesInvoiceService;

    public function __construct(SalesInvoiceRepository $repository, CompanyService $companyService, SalesInvoiceService $salesInvoiceService)
    {
        $this->middleware('permission:sales_invoice.print')->only('print');
        $this->middleware('permission:sales_invoice.export')->only(['export']);
        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->salesInvoiceService = $salesInvoiceService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter;
        $salesInvoices = $this->salesInvoiceService->salesInvoiceListAll(company_id(), financial_year_id(), $filters ?? []);
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        $salesInvoices = json_decode(json_encode($salesInvoices));
        // dd($salesInvoices);

        $tableConfig = [
            "columns" => [
                ["label" => "Bill No.", "class" => "text-end", "width" => "8%"],
                ["label" => "Po No.", "class" => "text-end", "width" => "10%"],                
                ["label" => "Date", "class" => "text-center", "width" => "10%"],
                ["label" => "GRN No.", "class" => "text-end", "width" => "10%"],
                ["label" => "Customer Name", "class" => "text-start", "width" => "35%"],
                ["label" => "Item Name", "class" => "text-start", "width" => "15%"],                
                ["label" => "Net Total", "class" => "text-end fw-bold",   "width" => "12%"],
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
            'currentYear' => $currentYear,
            'salesInvoices' => $salesInvoices,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'landscape'),
            'datePeriod' => $datePeriod,
        ];
        $html = view('company.pages.sales-invoice.print', $data)->render();
        return AjaxResponse::success(
            message: 'Sales Invoice fetched successfully',
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
            $filters = $request->currentFilter;
            $salesInvoices = $this->salesInvoiceService->salesInvoiceListAll(company_id(), financial_year_id(), $filters ?? []);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;
                
            $startDate = !empty($filters['start_date']) 
                ? $filters['start_date'] 
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');
                
            $endDate = !empty($filters['end_date']) 
                ? $filters['end_date'] 
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
            $datePeriod = "$startDate to $endDate";


            $headings = [
                'Bill No',
                'Po No',
                'Date', 
                'GRN No.',
                'Customer Name',
                'City',
                'Item Name',
                'Vehicle Number',
                'Net Total',               
            ];
            // dd($salesInvoices->toArray());
            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }
            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_sales_invoice_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use your Export class (make sure it handles $salesInvoices)
            Excel::store(
                new SalesInvoiceExport($company, $salesInvoices, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Sales Invoices Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
     
    // get data for print sales invoice report
    public function salesPrintReport(Request $request): View|JsonResponse
    {            
        $salesInvoiceId = $request->salesInvoiceId;
        $headerEnable = $request->headerEnable ?? 0;        
       
        
        if (is_string($salesInvoiceId) && strpos($salesInvoiceId, ',') !== false) {
            $salesInvoiceId = explode(',', $salesInvoiceId);
        }
        
        $filters = $request->currentFilter ?? [];
        $salesInvoices = $this->salesInvoiceService->getInvoicePrintDetails(
            $salesInvoiceId,
            company_id(),
            financial_year_id(),
            $filters
        );
       
        if (!$salesInvoices) {
            return AjaxResponse::error('Invoice not found for this company/year.');
        }

        $companyId = company_id();

        $html = view('company.pages.sales-invoice.sales_invoice_print', compact('salesInvoices','companyId', 'headerEnable'))->render();
        // if ($headerEnable == 1) {
        // } else {
        //      $html = view('company.pages.sales-invoice.sales_invoice_print', compact('salesInvoices', 'companyId','headerEnable'))->render();
        // }

        return AjaxResponse::success(
            message: 'Sales Invoice Print fetched successfully',
            data: ['html' => $html]
        );
    }

}

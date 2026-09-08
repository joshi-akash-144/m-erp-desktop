<?php

namespace App\Http\Controllers;

use App\Exports\SalesOrderExport;
use App\Helpers\AjaxResponse;
use App\Repositories\SalesOrderRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\SalesOrderService;
use Carbon\Carbon;

class SalesOrderExportController extends Controller
{
    protected SalesOrderRepository $repository;
    protected int $companyId;
    protected  $companyService;
    protected $salesOrderService;

    public function __construct(SalesOrderRepository $repository, CompanyService $companyService, SalesOrderService $salesOrderService)
    {
        $this->middleware('permission:sales_order.print')->only('print');
        $this->middleware('permission:sales_order.export')->only(['export']);
        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->salesOrderService = $salesOrderService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter;
        $salesOrders = $this->salesOrderService->salesOrderListAll(company_id(), financial_year_id(), $filters ?? []);
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        
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
            'salesOrders' => $salesOrders,
            'orientation' => $request->input('orientation', 'landscape'),
            'datePeriod' =>$datePeriod,
        ];

        $html = view('company.pages.sales-order.print', $data)->render();
        return AjaxResponse::success(
            message: 'Sales order fetched successfully',
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
            $salesOrders = $this->salesOrderService->salesOrderListAll(company_id(), financial_year_id(), $filters ?? []);
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
                'Po.No',
                'Date',
                'Last Date',
                'Customer Name',
                'Broker Name',
                'Product Name',
                'Incl Rate',
                'Rate',
                'Order Qty',
                'Rec Qty',
                'Rem Qty',
            ];
            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }
            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_sales_order_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use your Export class (make sure it handles $salesOrders)
            Excel::store(
                new SalesOrderExport($company, $salesOrders, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Sales Orders Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

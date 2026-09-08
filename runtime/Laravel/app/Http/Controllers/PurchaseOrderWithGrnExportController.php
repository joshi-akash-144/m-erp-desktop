<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseOrderWithGrnExport;
use App\Repositories\PurchaseOrderRepository;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\PurchaseOrderService;
use Carbon\Carbon;


class PurchaseOrderWithGrnExportController extends Controller
{
    protected PurchaseOrderRepository $repository;
    protected int $companyId;
    protected  $companyService;

    protected $purchaseOrderService;


    public function __construct(PurchaseOrderRepository $repository, CompanyService $companyService, PurchaseOrderService $purchaseOrderService)
    {

        $this->middleware('permission:purchase_order_with_grn.print')->only('print');
        $this->middleware('permission:purchase_order_with_grn.export')->only(['exportExcel', 'exportCsv']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->purchaseOrderService = $purchaseOrderService;

        // $this->middleware(function ($request, $next) {
        //     $this->companyId = session('company_id');
        //     return $next($request);
        // });
    }

    public function Print(Request $request): JsonResponse
    {
 
        $request->validate([
            'format' => 'required|in:print,pdf',
        ]);
 
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;

        $filters = $request->currentFilter ?? [];
        
        $startDate = !empty($filters['start_date']) 
            ? $filters['start_date'] 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";

        $result = $this->purchaseOrderService->purchaseOrderDetailList(company_id(), financial_year_id(), $filters);
        
        $tableConfig = [
            "columns" => [
                ["label" => "PO No", "class" => "text-center"],
                ["label" => "PO Date", "class" => "text-start"],
                ["label" => "Supplier", "class" => "text-start"],
                // ["label" => "Broker", "class" => "text-start"],
                ["label" => "Item", "class" => "text-start"],
                // ["label" => "Dest.", "class" => "text-start"],
                ["label" => "Rate", "class" => "text-end"],
                ["label" => "Ord.Qty", "class" => "text-end"],
                ["label" => "GRN Date", "class" => "text-start"],
                ["label" => "Bill No", "class" => "text-start"],
                ["label" => "GRN No", "class" => "text-start"],
                ["label" => "Vehicle", "class" => "text-start"],
                ["label" => "Bags", "class" => "text-end"],
                // ["label" => "P.Qty", "class" => "text-end"],
                ["label" => "Rec.Qty", "class" => "text-end"],
                ["label" => "Rem.Qty", "class" => "text-end"],
            ],
            "body" => $result['data']
        ];
            
        $data = [
            'company' => $company,
            'currentYear'=> $currentYear,
            'reportData' => $result['data'],
            'footerData' => $result['footerData'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tableConfig' => $tableConfig,
            'datePeriod' => $datePeriod,
            'orientation' => 'landscape',
        ];
 
        $html = view('company.pages.purchase-order-with-grn.print', $data)->render();
 
        return AjaxResponse::success(
            message: 'Detail report fetched successfully',
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
            
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $filters = $request->currentFilter ?? [];

            $startDate = !empty($filters['start_date']) 
                ? $filters['start_date'] 
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');
                
            $endDate = !empty($filters['end_date']) 
                ? $filters['end_date'] 
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
            $datePeriod = "$startDate to $endDate";

            $purchaseOrdersData = $this->purchaseOrderService->purchaseOrderDetailList(company_id(), financial_year_id(), $filters);
            $purchaseOrders = collect($purchaseOrdersData['data'] ?? []);
            $footerData = $purchaseOrdersData['footerData'] ?? [];

            $headings = [
                'Po.No',
                'Date',
                'Supplier Name',
                'Broker Name',
                'Product Name',
                'Destination',
                'Rate',
                'Order Qty',
                'GRN Date',
                'Bill No',
                'GRN No',
                'Vehicle No',
                'Bags',
                'P.Qty',
                'Rec.Qty',
                'Rem.Qty',
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_purchase_order_with_grn_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use your Export class (make sure it handles $purchaseOrders)
            Excel::store(
                new PurchaseOrderWithGrnExport($company, $purchaseOrders, $headings, $footerData, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Purchase Orders with GRN Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}

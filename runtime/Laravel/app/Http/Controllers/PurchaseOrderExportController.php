<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseOrderExport;
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


class PurchaseOrderExportController extends Controller
{
    protected PurchaseOrderRepository $repository;
    protected int $companyId;
    protected  $companyService;

    protected $purchaseOrderService;


    public function __construct(PurchaseOrderRepository $repository, CompanyService $companyService, PurchaseOrderService $purchaseOrderService)
    {

        $this->middleware('permission:purchase_order.print')->only('print');
        $this->middleware('permission:purchase_order.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->purchaseOrderService = $purchaseOrderService;

        // $this->middleware(function ($request, $next) {
        //     $this->companyId = session('company_id');
        //     return $next($request);
        // });
    }

    public function print(Request $request): JsonResponse
    {

        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter ?? [];
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        $startDate = !empty($filters['start_date']) 
            ? $filters['start_date'] 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";

        $purchaseOrders = $this->purchaseOrderService->purchaseOrderListAll(company_id(), financial_year_id(), $filters);       
        
        // $tableConfig = [
        //     "columns" => [
        //         ["label" => "Date", "class" => "text-start"],
        //         ["label" => "Particular", "class" => "text-start"],
        //         ["label" => "Destination", "class" => "text-start"],
        //         ["label" => "Last Date", "class" => "text-start"],
        //         ["label" => "Product", "class" => "text-start"],
        //         ["label" => "Incl_Rate", "class" => "text-end"],
        //         ["label" => "Rate", "class" => "text-end"],
        //         ["label" => "Order_qty", "class" => "text-end"],
        //         ["label" => "Rec_qty", "class" => "text-end"],
        //         ["label" => "Balance", "class" => "text-end"],                
        //     ],
        //     "body" => []
        // ];
        // dd($datePeriod);
        $data = [
            'company' => $company,
            'currentYear'=> $currentYear,
            'purchaseOrders' => $purchaseOrders,
        // 'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation','landscape'),
            'datePeriod' => $datePeriod,
        ];

        $html = view('company.pages.purchase-order.print', $data)->render();

        return AjaxResponse::success(
            message: 'Purchase order fetched successfully',
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
    
            $purchaseOrders = $this->purchaseOrderService->purchaseOrderListAll(company_id(), financial_year_id(), $filters ?? []);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;
            
            $startDate = !empty($filters['start_date']) ? $filters['start_date'] : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            $endDate = !empty($filters['end_date']) ? $filters['end_date'] : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";

            $headings = [
                'Po.No',
                'Date',
                'Last Date',
                'Supplier Name',
                'Broker Name',
                'Contract No',
                'Product Name',
                'Destination',
                'Incl Rate',
                'Rate',
                'Order Qty',
                'Rec Qty',
                'Balance',
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_purchase_order_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use your Export class (make sure it handles $purchaseOrders)
            Excel::store(
                new PurchaseOrderExport($company, $purchaseOrders, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Purchase Orders Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}

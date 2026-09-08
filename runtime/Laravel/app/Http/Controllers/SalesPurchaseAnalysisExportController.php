<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Throwable;
use App\Exports\SalesPurchaseAnalysisExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesPurchaseAnalysisExportController extends Controller
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        // Add middleware if needed, e.g., $this->middleware('permission:sales_purchase_analysis_export')->only('exportExcel');
        $this->companyService = $companyService;
    }

    public function exportExcel(Request $request)
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;

        // Apply same filtering as index method
        $analysisController = new SalesPurchaseAnalysisController(app(\App\Services\MasterDataService::class));
        $query = $analysisController->getBaseQuery($request);

        $query->select([
            'sales_orders.id as sales_order_id',
            'sales_orders.purchase_order_number as buyer_po_number',
            DB::raw('MAX(sales_party.name) as sales_party_name'),
            DB::raw('MAX(items.name) as item_name'),
            DB::raw('AVG(sales_invoice_items.rate) as sales_rate'),
            DB::raw('AVG(purchase_invoice_items.rate) as purchase_rate'),
            DB::raw('SUM(sales_invoice_items.amount) as sales_amount'),
            DB::raw('SUM(purchase_invoice_items.amount) as purchase_amount'),
            DB::raw('(AVG(sales_invoice_items.rate) - AVG(purchase_invoice_items.rate)) as profit_loss_rate'),
            DB::raw('SUM((sales_invoice_items.rate - purchase_invoice_items.rate) * sales_invoice_items.quantity) as profit_loss_amount'),
            DB::raw('MAX(sales_orders.total_quantity) as so_total_qty'),
            DB::raw('MAX(destinations.name) as destination_name')
        ])->groupBy('sales_orders.id', 'sales_orders.purchase_order_number');
        
        $rows = $query->orderBy('sales_orders.id', 'desc')->get();

        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->format('d-m-Y') 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->format('d-m-Y') 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
        $datePeriod = "$startDate to $endDate";

        $headings = [
            'Dairy PO No.',
            'Customer Account',
            'Item Name',
            'Destination',
            'PO Qty',
            'Purchase Rate',
            'Sale Rate',
            'Profit/Loss Rate',
            'Profit/Loss Amount'
        ];

        $directory = 'master_reports';
        $fileName = 'sales_purchase_analysis_' . now()->format('d_m_Y_His') . ".{$format}";
        
        if (!File::exists(storage_path("app/public/{$directory}"))) {
            File::makeDirectory(storage_path("app/public/{$directory}"), 0755, true);
        }
        
        try {
            Excel::store(
                new SalesPurchaseAnalysisExport($company, $rows, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'Sales Purchase Analysis Exported successfully',
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

    public function exportDetailedExcel(Request $request)
    {
        return $this->exportDetailedByFormat($request, 'xlsx');
    }

    private function exportDetailedByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;

        // Apply same filtering as index method
        $analysisController = new SalesPurchaseAnalysisController(app(\App\Services\MasterDataService::class));
        $query = $analysisController->getDetailedQuery($request);

        if (!$query) {
            return AjaxResponse::error(message: 'No data found for this PO.');
        }

        $rows = $query->orderBy('purchase_orders.id', 'desc')->get();

        // Get Parent Row for exact summary match
        $mainQuery = $analysisController->getBaseQuery($request);
        $parentRow = $mainQuery->select([
            'sales_orders.id as sales_order_id',
            'sales_orders.purchase_order_number as buyer_po_number',
            DB::raw('MAX(sales_party.name) as sales_party_name'),
            DB::raw('MAX(items.name) as item_name'),
            DB::raw('(SUM(sales_invoice_items.rate * sales_invoice_items.quantity) / NULLIF(SUM(sales_invoice_items.quantity), 0)) as sales_rate'),
            DB::raw('(SUM(purchase_invoice_items.rate * purchase_invoice_items.quantity) / NULLIF(SUM(purchase_invoice_items.quantity), 0)) as purchase_rate'),
            DB::raw('SUM(sales_invoice_items.amount) as sales_amount'),
            DB::raw('SUM(purchase_invoice_items.amount) as purchase_amount'),
            DB::raw('((SUM(sales_invoice_items.rate * sales_invoice_items.quantity) / NULLIF(SUM(sales_invoice_items.quantity), 0)) - (SUM(purchase_invoice_items.rate * purchase_invoice_items.quantity) / NULLIF(SUM(purchase_invoice_items.quantity), 0))) as profit_loss_rate'),
            DB::raw('SUM((sales_invoice_items.rate - purchase_invoice_items.rate) * sales_invoice_items.quantity) as profit_loss_amount'),
            DB::raw('MAX(sales_orders.total_quantity) as so_total_qty'),
            DB::raw('(SELECT SUM(received_qty) FROM sales_order_items WHERE sales_order_id = sales_orders.id) as so_received_qty'),
            DB::raw('MAX(destinations.name) as destination_name')
        ])->where('sales_orders.id', $request->sales_order_id)
          ->groupBy('sales_orders.id', 'sales_orders.purchase_order_number')
          ->first();

        $poNumber = $request->input('buyer_po_number') ?: 'Detailed_PO';

        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->format('d-m-Y') 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->format('d-m-Y') 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
                
        $datePeriod = "$startDate to $endDate";

        $headings = [
            'Supplier Name',
            'Supplier PO No',
            'Purchase Rate',
            'Profit/Loss Percentage'
        ];

        $directory = 'master_reports';
        $fileName = 'dairy_po_' . $poNumber . '_' . now()->format('d_m_Y_His') . ".{$format}";
        
        if (!File::exists(storage_path("app/public/{$directory}"))) {
            File::makeDirectory(storage_path("app/public/{$directory}"), 0755, true);
        }
        
        try {
            Excel::store(
                new \App\Exports\SalesPurchaseAnalysisDetailedExport($company, $rows, $datePeriod, $poNumber, $parentRow),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'PO Analysis Exported successfully',
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

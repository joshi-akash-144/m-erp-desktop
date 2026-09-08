<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Services\MasterDataService;
use Carbon\Carbon;

class SalesPurchaseAnalysisController extends Controller
{
    protected MasterDataService $masterDataService;
    public function __construct(MasterDataService $masterDataService) {

        $this->masterDataService = $masterDataService;
    }

    public function getBaseQuery(Request $request)
    {
        $query = SalesInvoice::query()
            ->join('purchase_invoices', function ($join) {
                $join->on('purchase_invoices.sales_invoice_serial', '=', 'sales_invoices.invoice_serial')
                     ->whereColumn('purchase_invoices.company_id', 'sales_invoices.company_id')
                     ->whereColumn('purchase_invoices.financial_year_id', 'sales_invoices.financial_year_id')
                     ->whereNull('purchase_invoices.deleted_at');
            })
            ->join('sales_invoice_items', function ($join) {
                $join->on('sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
                     ->whereNull('sales_invoice_items.deleted_at');
            })
            ->join('purchase_invoice_items', function ($join) {
                $join->on('purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                     ->on('purchase_invoice_items.item_id', '=', 'sales_invoice_items.item_id');
            })
            ->join('items', 'items.id', '=', 'sales_invoice_items.item_id')
            ->join('accounts as sales_party', 'sales_party.id', '=', 'sales_invoices.account_id')
            ->join('accounts as purchase_party', 'purchase_party.id', '=', 'purchase_invoices.account_id')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'purchase_invoice_items.purchase_order_id')
            ->leftJoin('sales_orders', 'sales_orders.id', '=', 'sales_invoices.sales_order_id')
            ->leftJoin('units', 'units.id', '=', 'items.unit_id')
            ->leftJoin('destinations', 'destinations.id', '=', 'sales_invoice_items.destination_id')
            ->where('sales_invoices.company_id', company_id())
            ->where('sales_invoices.financial_year_id', financial_year_id());

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('sales_invoices.invoice_date', '>=', Carbon::parse($request->start_date)->format('Y-m-d'));
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('sales_invoices.invoice_date', '<=', Carbon::parse($request->end_date)->format('Y-m-d'));
        }
        if ($request->has('item_id') && $request->item_id) {
            $query->where('items.id', $request->item_id);
        }
        if ($request->has('account_id') && $request->account_id) {
            $query->where('sales_party.id', $request->account_id);
        }
        if ($request->has('sales_order_id') && $request->sales_order_id) {
            $query->where('sales_invoices.sales_order_id', $request->sales_order_id);
        }
        if ($request->has('so_serial') && $request->so_serial) {
            $query->where('sales_orders.purchase_order_number',   $request->so_serial );
        }
        if ($request->has('po_serial') && $request->po_serial) {
            $query->where('purchase_orders.order_serial', $request->po_serial);
        }

        return $query;
    }

    public function getDetailedQuery(Request $request)
    {
        // Highly optimized separate query for the modal (PO wise)
        // First, fetch the exact sales invoice IDs to prevent MySQL optimizer from doing full table scans on complex JOINs
        $invoiceIdsQuery = SalesInvoice::query()
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id());
            
        if ($request->has('sales_order_id') && $request->sales_order_id) {
            $invoiceIdsQuery->where('sales_order_id', $request->sales_order_id);
        } else if ($request->has('buyer_po_number') && $request->buyer_po_number) {
            $invoiceIdsQuery->join('sales_orders', 'sales_orders.id', '=', 'sales_invoices.sales_order_id')
                            ->where('sales_orders.purchase_order_number', $request->buyer_po_number);
        }
        
        // Apply date filters if they exist to match the main grid
        if ($request->has('start_date') && $request->start_date) {
            $invoiceIdsQuery->whereDate('sales_invoices.invoice_date', '>=', Carbon::parse($request->start_date)->format('Y-m-d'));
        }
        if ($request->has('end_date') && $request->end_date) {
            $invoiceIdsQuery->whereDate('sales_invoices.invoice_date', '<=', Carbon::parse($request->end_date)->format('Y-m-d'));
        }

        $invoiceIds = $invoiceIdsQuery->pluck('sales_invoices.id')->toArray();
        
        if (empty($invoiceIds)) {
            return null;
        }

        return DB::table('sales_invoices')
            ->whereIn('sales_invoices.id', $invoiceIds)
            ->join('purchase_invoices', function ($join) {
                $join->on('purchase_invoices.sales_invoice_serial', '=', 'sales_invoices.invoice_serial')
                     ->whereColumn('purchase_invoices.company_id', 'sales_invoices.company_id')
                     ->whereColumn('purchase_invoices.financial_year_id', 'sales_invoices.financial_year_id')
                     ->whereNull('purchase_invoices.deleted_at');
            })
            ->join('sales_invoice_items', function ($join) {
                $join->on('sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
                     ->whereNull('sales_invoice_items.deleted_at');
            })
            ->join('purchase_invoice_items', function ($join) {
                $join->on('purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                     ->on('purchase_invoice_items.item_id', '=', 'sales_invoice_items.item_id');
            })
            ->join('items', 'items.id', '=', 'sales_invoice_items.item_id')
            ->join('accounts as sales_party', 'sales_party.id', '=', 'sales_invoices.account_id')
            ->join('accounts as purchase_party', 'purchase_party.id', '=', 'purchase_invoices.account_id')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'purchase_invoice_items.purchase_order_id')
            ->leftJoin('sales_orders', 'sales_orders.id', '=', 'sales_invoices.sales_order_id')
            ->leftJoin('units', 'units.id', '=', 'items.unit_id')
            ->leftJoin('destinations', 'destinations.id', '=', 'sales_invoice_items.destination_id')
            ->select([
                'purchase_orders.id as purchase_order_id',
                'purchase_orders.order_serial as po_number',
                DB::raw('MAX(purchase_orders.order_date) as po_date'),
                DB::raw('GROUP_CONCAT(DISTINCT sales_invoices.invoice_serial SEPARATOR ", ") as sales_number'),
                DB::raw('MAX(sales_invoices.invoice_date) as sales_date'),
                DB::raw('MAX(sales_party.name) as sales_party_name'),
                DB::raw('GROUP_CONCAT(DISTINCT purchase_invoices.invoice_serial SEPARATOR ", ") as purchase_number'),
                DB::raw('MAX(purchase_invoices.invoice_date) as purchase_date'),
                DB::raw('MAX(purchase_party.name) as purchase_party_name'),
                DB::raw('MAX(sales_orders.purchase_order_number) as buyer_po_number'),
                DB::raw('GROUP_CONCAT(DISTINCT items.name SEPARATOR ", ") as item_name'),
                DB::raw('MAX(units.name) as unit_name'),
                DB::raw('SUM(sales_invoice_items.quantity) as sales_qty'),
                DB::raw('SUM(purchase_invoice_items.quantity) as purchase_qty'),
                DB::raw('(SUM(sales_invoice_items.rate * sales_invoice_items.quantity) / NULLIF(SUM(sales_invoice_items.quantity), 0)) as sales_rate'),
                DB::raw('(SUM(purchase_invoice_items.rate * purchase_invoice_items.quantity) / NULLIF(SUM(purchase_invoice_items.quantity), 0)) as purchase_rate'),
                DB::raw('SUM(sales_invoice_items.amount) as sales_amount'),
                DB::raw('SUM(purchase_invoice_items.amount) as purchase_amount'),
                DB::raw('((SUM(sales_invoice_items.rate * sales_invoice_items.quantity) / NULLIF(SUM(sales_invoice_items.quantity), 0)) - (SUM(purchase_invoice_items.rate * purchase_invoice_items.quantity) / NULLIF(SUM(purchase_invoice_items.quantity), 0))) as profit_loss_rate'),
                DB::raw('SUM((sales_invoice_items.rate - purchase_invoice_items.rate) * sales_invoice_items.quantity) as profit_loss_amount'),
                DB::raw('MAX(sales_orders.total_quantity) as so_total_qty'),
                DB::raw('MAX(destinations.name) as destination_name')
            ])->groupBy('purchase_orders.id', 'purchase_orders.order_serial');
    }

    public function index(Request $request, MasterDataService $masterDataService)
    {
        if ($request->ajax()) {
            if ($request->get('detailed')) {
                $detailedQuery = $this->getDetailedQuery($request);
                
                if (!$detailedQuery) {
                    return response()->json(['data' => [], 'total' => 0]);
                }
                
                $size = $request->get('size', 50);
                $data = $detailedQuery->orderBy('purchase_orders.id', 'desc')->paginate($size);

            } else {
                $query = $this->getBaseQuery($request);
                
                $query->select([
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
                ])->groupBy('sales_orders.id', 'sales_orders.purchase_order_number');
                
                $size = $request->get('size', 50);
                $data = $query->orderBy('sales_orders.id', 'desc')->paginate($size);
            }
            return response()->json($data);
        }

        $items = $masterDataService->get('items', company_id())->pluck('name', 'id');
        $accounts = $masterDataService->get('customers', company_id())->pluck('name', 'id');
        return view('company.pages.sales-purchase-analysis.index', compact('items', 'accounts'));
    }

    public function dashboardData(Request $request)
    {
        $query = $this->getBaseQuery($request);

        $kpiQuery = clone $query;
        $totals = $kpiQuery->select(
            DB::raw('SUM(purchase_invoice_items.amount) as total_purchase'),
            DB::raw('SUM(sales_invoice_items.amount) as total_sales'),
            DB::raw('SUM((sales_invoice_items.rate - purchase_invoice_items.rate) * sales_invoice_items.quantity) as total_profit')
        )->first();

        $itemsQuery = clone $query;
        $topItems = $itemsQuery->select(
            'items.name as item_name',
            DB::raw('SUM((sales_invoice_items.rate - purchase_invoice_items.rate) * sales_invoice_items.quantity) as profit')
        )
        ->groupBy('items.id', 'items.name')
        ->orderBy('profit', 'desc')
        ->limit(5)
        ->get();

        $trendQuery = clone $query;
        $trend = $trendQuery->select(
            DB::raw('DATE_FORMAT(sales_invoices.invoice_date, "%Y-%m") as month'),
            DB::raw('SUM(purchase_invoice_items.amount) as purchase'),
            DB::raw('SUM(sales_invoice_items.amount) as sales')
        )
        ->groupBy(DB::raw('DATE_FORMAT(sales_invoices.invoice_date, "%Y-%m")'))
        ->orderBy(DB::raw('DATE_FORMAT(sales_invoices.invoice_date, "%Y-%m")'), 'asc')
        ->get();

        return response()->json([
            'totals' => $totals,
            'top_items' => $topItems,
            'trend' => $trend
        ]);
    }
}
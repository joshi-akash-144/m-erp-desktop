<?php

namespace App\Http\Controllers;

use App\Repositories\GodownModuleRepository;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\GrnItem;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Show the application dashboard.
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 'today');
        return view('company.pages.dashboard.dashboard', [
            'currentDays' => $days
        ]);
    }

    /**
     * API: Get recent invoices for Tabulator
     */
    public function recentInvoices()
    {
        $companyId = session('company_id');
        $data = $this->dashboardService->getRecentInvoices($companyId, 10);
        return response()->json($data);
    }

    /**
     * API: Get dashboard metrics for AJAX refresh
     */
    public function filters(Request $request)
    {
        $companyId = session('company_id');
        $financialYearId = session('financial_year_id');
        $days = $request->get('days', 'today');
        $filter = $request->get('filter');

        // Convert to integer unless it is 'all' or 'today'
        if ($days !== 'all' && $days !== 'today') {
            $days = (int) $days;
        }

        if ($filter) {
            $data = $this->dashboardService->getFilterData($filter, $days, $companyId, $financialYearId);
        } else {
            $data = $this->dashboardService->getDashboardFilters($companyId, $financialYearId, $days);
        }

        return response()->json($data);
    }

    /**
     * API: Get top selling products for Tabulator
     */
    public function topSellingProducts()
    {
        $companyId = session('company_id');
        $data = $this->dashboardService->getTopSellingItems($companyId, company_id());
        return response()->json($data);
    }

    /**
     * API: Get Godown Details for Tabulator modal
     * Returns records filtered by the selected filter (days) and status,
     * exactly matching the counts shown on the dashboard cards.
     */
    public function godownDetails(Request $request, GodownModuleRepository $godownRepo)
    {
        $companyId      = session('company_id');
        $financialYearId = session('financial_year_id');
        $days           = $request->get('days', 'today');
        $status         = $request->get('status', 'all');

        // Build date filters — same logic as getGodownDetailsCounts in DashboardRepository
        $filters = [
            'product_status' => $status,
        ];

        if ($days && $days !== 'all') {
            if ($days === 'today') {
                $filters['start_date'] = Carbon::today()->format('d-m-Y');
                $filters['end_date']   = Carbon::today()->format('d-m-Y');
            } else {
                $filters['start_date'] = Carbon::now()->subDays((int) $days)->format('d-m-Y');
                $filters['end_date']   = Carbon::now()->format('d-m-Y');
            }
        }

        $data = $godownRepo->getGodownData($companyId, $financialYearId, $filters);

        // Map to the modal columns.
        // date_in / date_out are already formatted as 'd-m-Y' strings (or '-') by getGodownData.
        $mapped = $data->map(function ($module) {
            $partyName        = $module->account          ? $module->account->name          : null;
            $partyDestination = $module->partyDestination ? $module->partyDestination->name : null;
            $godownName       = $module->godown           ? $module->godown->name           : null;
            $itemName         = $module->item             ? $module->item->name             : null;
            $rate             = $module->rate;
            $timeIn           = $module->time_in;
            $timeOut          = $module->time_out;

            return [
                'party_name'        => $partyName        ?: '-',
                'party_destination' => $partyDestination ?: '-',
                'godown'            => $godownName       ?: '-',
                'date_in'           => ($module->date_in  && $module->date_in  !== '-') ? $module->date_in  : '-',
                'date_out'          => ($module->date_out && $module->date_out !== '-') ? $module->date_out : '-',
                'time_in'           => ($timeIn  !== null && $timeIn  !== '') ? $timeIn  : '-',
                'time_out'          => ($timeOut !== null && $timeOut !== '') ? $timeOut : '-',
                'rate'              => ($rate    !== null && $rate    !== '' && $rate !== 0) ? $rate : '-',
                'item'              => $itemName ?: '-',
            ];
        });

        return response()->json($mapped);
    }
    public function creditors(Request $request)
    {
        $companyId = session('company_id');
        $financialYearId = session('financial_year_id');
        $endDate = now()->format('Y-m-d');

        // Calculate the final till date ledger amount (Opening Balance + all movements till date)
        $balances = \Illuminate\Support\Facades\DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->where(function ($query) use ($endDate) {
                $query->where('v.voucher_type_id', \App\Models\VoucherType::OPENING_BALANCE)
                      ->orWhere('v.voucher_date', '<=', $endDate);
            })
            ->groupBy('vt.account_id')
            ->select('vt.account_id', \Illuminate\Support\Facades\DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance'))
            ->get()
            ->keyBy('account_id');

        $accounts = \App\Models\Account::where('accounts.company_id', $companyId)
            ->join('account_groups', 'account_groups.id', '=', 'accounts.account_group_id')
            ->where('account_groups.name', 'like', '%Creditor%')
            ->select('accounts.id', 'accounts.name as account_name', 'accounts.city')
            ->orderBy('accounts.name')
            ->get();

        $data = $accounts->map(function ($account) use ($balances) {
            $nameWithCity = $account->account_name;
            if (!empty($account->city)) {
                $nameWithCity .= ' (' . $account->city . ')';
            }
            return [
                'account_name' => $nameWithCity,
                'closing_balance' => isset($balances[$account->id]) ? (float)$balances[$account->id]->balance : 0.00,
            ];
        })->filter(function ($item) {
            return $item['closing_balance'] != 0;
        })->values();

        return response()->json($data);
    }

    public function debtors(Request $request)
    {
        $companyId = session('company_id');
        $financialYearId = session('financial_year_id');
        $endDate = now()->format('Y-m-d');

        // Calculate the final till date ledger amount (Opening Balance + all movements till date)
        $balances = \Illuminate\Support\Facades\DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->where(function ($query) use ($endDate) {
                $query->where('v.voucher_type_id', \App\Models\VoucherType::OPENING_BALANCE)
                      ->orWhere('v.voucher_date', '<=', $endDate);
            })
            ->groupBy('vt.account_id')
            ->select('vt.account_id', \Illuminate\Support\Facades\DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance'))
            ->get()
            ->keyBy('account_id');

        $accounts = \App\Models\Account::where('accounts.company_id', $companyId)
            ->join('account_groups', 'account_groups.id', '=', 'accounts.account_group_id')
            ->where('account_groups.name', 'like', '%Debtor%')
            ->select('accounts.id', 'accounts.name as account_name', 'accounts.city')
            ->orderBy('accounts.name')
            ->get();

        $data = $accounts->map(function ($account) use ($balances) {
            $nameWithCity = $account->account_name;
            if (!empty($account->city)) {
                $nameWithCity .= ' (' . $account->city . ')';
            }
            return [
                'account_name' => $nameWithCity,
                'closing_balance' => isset($balances[$account->id]) ? (float)$balances[$account->id]->balance : 0.00,
            ];
        })->filter(function ($item) {
            return $item['closing_balance'] != 0;
        })->values();

        return response()->json($data);
    }

    public function locationWiseGrn(Request $request)
    {
        $companyId = session('company_id');
        $financialYearId = session('financial_year_id');        

        $query = GrnItem::whereHas('grn', function ($q) use ($companyId, $financialYearId) {
            $q->where('company_id', $companyId)
              ->where('financial_year_id', $financialYearId)
              ->whereHas('account', function($q2) {
                  $q2->where('party_type', 'supplier');
              });

            $today = Carbon::now();$q->whereDate('grn_date', $today->toDateString());

        })->whereNotNull('destination_id');

        $data = $query->with('destination:id,name')
            ->selectRaw('destination_id, count(DISTINCT grn_id) as total_grns')
            ->groupBy('destination_id')
            ->orderByDesc('total_grns')
            ->get();
        // dd($data->toArray());
        $result = $data->map(function ($item) {
            return [
                'destination_id' => $item->destination_id,
                'location_name' => $item->destination ? $item->destination->name : 'Unknown',
                'total_grns' => $item->total_grns
            ];
        });
        
        return response()->json($result);
    }

    public function locationWiseGrnDetails(Request $request)
    {
        $companyId = session('company_id');
        $financialYearId = session('financial_year_id');
        $destinationId = $request->get('destination_id');

        $items = GrnItem::with([
                'grn:id,grn_serial,grn_date,created_at,account_id', 
                'grn.account:id,name,city', 
                'item:id,name',
                'destination:id,name'
            ])
            ->where('destination_id', $destinationId)
            ->whereHas('grn', function ($q) use ($companyId, $financialYearId) {
                $q->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId)
                  ->whereDate('grn_date', \Carbon\Carbon::now()->toDateString())
                  ->whereHas('account', function($q2) {
                      $q2->where('party_type', 'supplier');
                  });
            })
            ->select('id', 'grn_id', 'item_id', 'quantity', 'party_quantity', 'destination_id')
            ->get();

        $data = $items->groupBy('grn_id')->map(function ($grnItems) {
            $firstItem = $grnItems->first();
            $isMultiple = $grnItems->count() > 1;
            
            $itemNames = $grnItems->map(function ($item) use ($isMultiple) {
                $name = $item->item?->name ?? '-';
                return $isMultiple ? '&bull; ' . $name : $name;
            })->implode('<br>');

            $quantities = $grnItems->map(function ($item) use ($isMultiple) {
                $qty = number_format((float) $item->quantity, 3);
                return $isMultiple ? '&bull; ' . $qty : $qty;
            })->implode('<br>');

            $partyQuantities = $grnItems->map(function ($item) use ($isMultiple) {
                $pQty = number_format((float) $item->party_quantity, 3);
                return $isMultiple ? '&bull; ' . $pQty : $pQty;
            })->implode('<br>');

            $partyName = $firstItem->grn?->account?->name ?? '-';
            $city = $firstItem->grn?->account?->city;
            if ($city) {
                $partyName .= ' (' . $city . ')';
            }

            return [
                'grn_number' => $firstItem->grn?->grn_serial ?? '-',
                'date' => Carbon::parse($firstItem->grn?->grn_date)->format('d-m-Y') ?? '-',
                'party_name' => $partyName,
                'item_name' => $itemNames ?? '-',
                'party_quantity' => $partyQuantities ?? '-',
                'quantity' => $quantities ?? '-',
                'destination_id' => $firstItem->destination?->name ?? '-',
            ];
        })->values();

        return response()->json($data);
    }
}
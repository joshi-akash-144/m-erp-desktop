<?php

namespace App\Repositories;

use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Grn;
use App\Models\User;
use App\Models\Account;
use App\Models\SalesOrder;
use App\Models\FinancialYear;
use App\Models\SalesInvoiceItem;
use App\Models\AccountYearBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DashboardRepository
{
    /**
     * Fetch Sales specific Filters
     */
    public function getSalesFilters(int $companyId, int $financialYearId, $days = null): array
    {
        $query = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if ($days && $days !== 'all') {
            if ($days === 'today') {
                $query->whereDate('invoice_date', Carbon::today());
            } else {
                $query->where('invoice_date', '>=', Carbon::now()->subDays((int)$days));
            }
        }

        $total = (float) $query->sum('net_amount');

        return [
            'today' => (float) SalesInvoice::whereDate('invoice_date', Carbon::today())
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->sum('net_amount'),
            'total' => $total
        ];
    }

    /**
     * Fetch Purchase specific Filters
     */
    public function getPurchaseFilters(int $companyId, int $financialYearId, $days = null): array
    {
        $query = PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if ($days && $days !== 'all') {
            if ($days === 'today') {
                $query->whereDate('invoice_date', Carbon::today());
            } else {
                $query->where('invoice_date', '>=', Carbon::now()->subDays((int)$days));
            }
        }

        return [
            'total' => (float) $query->sum('grand_total')
        ];
    }

    /**
     * Fetch GRN specific Filters
     */
    public function getGrnFilters(int $companyId, int $financialYearId, $days = null): array
    {
        $query = Grn::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if ($days && $days !== 'all') {
            if ($days === 'today') {
                $query->whereDate('grn_date', Carbon::today());
            } else {
                $query->where('grn_date', '>=', Carbon::now()->subDays((int)$days));
            }
        }

        return [
            'total_revenue' => (float) $query->sum(DB::raw('sub_total + total_tax'))
        ];
    }

    /**
     * Fetch Account/Customer specific Filters
     */
    public function getAccountFilters(int $companyId, $days = 30): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 1)->count();
        $percentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'active_user_percentage' => (int) $percentage,
            'active_customers' => Account::where('party_type', 'customer')
                ->where('company_id', $companyId)
                ->whereHas('sales_invoices', function ($query) use ($companyId, $days) {
                    $query->where('company_id', $companyId);
                    if ($days && $days !== 'all') {
                        if ($days === 'today') {
                            $query->whereDate('invoice_date', Carbon::today());
                        } else {
                            $query->where('invoice_date', '>=', Carbon::now()->subDays((int)$days));
                        }
                    }
                })->count()
        ];
    }


    /**
     * Fetch historical trend data
     */
    public function getTrendData(int $companyId, int $financialYearId, $days = 120): array
    {
        // Handle "All" (Financial Year) case
        $startDate = null;
        if (!$days || $days == 'all') {
            $fy = FinancialYear::find($financialYearId);
            $startDate = $fy ? Carbon::parse($fy->start_date) : Carbon::now()->startOfYear();
            $days = abs((int) Carbon::now()->diffInDays($startDate)) + 1;
        } else if ($days === 'today') {
            $startDate = Carbon::today();
            $days = 1;
        } else {
            $startDate = Carbon::now()->subDays((int)$days);
        }

        $dates = collect(range($days - 1, 0))->map(fn($i) => Carbon::now()->subDays($i)->format('Y-m-d'));

        $sales = SalesInvoice::select(
                DB::raw('DATE(invoice_date) as date'),
                DB::raw('SUM(net_amount) as amount')
            )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('invoice_date', '>=', $startDate)
            ->groupBy('date')
            ->get()
            ->pluck('amount', 'date');

        $purchases = PurchaseInvoice::select(
                DB::raw('DATE(invoice_date) as date'),
                DB::raw('SUM(grand_total) as amount')
            )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('invoice_date', '>=', $startDate)
            ->groupBy('date')
            ->get()
            ->pluck('amount', 'date');

        $grns = Grn::select(
                DB::raw('DATE(grn_date) as date'),
                DB::raw('SUM(sub_total + total_tax) as amount')
            )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('grn_date', '>=', $startDate)
            ->groupBy('date')
            ->get()
            ->pluck('amount', 'date');

        // Ensure all dates are present
        $salesTrend = $dates->map(fn($date) => ['date' => $date, 'amount' => (float)$sales->get($date, 0)]);
        $purchaseTrend = $dates->map(fn($date) => ['date' => $date, 'amount' => (float)$purchases->get($date, 0)]);
        $grnTrend = $dates->map(fn($date) => ['date' => $date, 'amount' => (float)$grns->get($date, 0)]);

        return [
            'salesData'    => $salesTrend,
            'purchaseData' => $purchaseTrend,
            'grnData'      => $grnTrend,
            'labels'       => $dates
        ];
    }

    /**
     * Fetch detailed Account/Customer analytics and trends
     */
    public function getAccountAnalytics(int $companyId, $days = 120): array
    {
        if (!$days || $days == 'all') {
            $fy = FinancialYear::where('company_id', $companyId)->first(); // Simple fallback
            $startDate = $fy ? Carbon::parse($fy->start_date) : Carbon::now()->startOfYear();
            $days = abs((int) Carbon::now()->diffInDays($startDate)) + 1;
        } else if ($days === 'today') {
            $days = 1;
        }

        $dates = collect(range($days - 1, 0))->map(fn($i) => Carbon::now()->subDays($i)->format('Y-m-d'));

        // Daily New Users Activity
        $newUsers = User::where('created_at', '>=', Carbon::now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        // Total Users Growth (Cumulative) for a "wavy" growth curve
        $initialCount = User::where('created_at', '<', Carbon::now()->subDays($days - 1))
            ->count();

        $runningTotal = $initialCount;
        $totalUsersTrend = [];
        foreach ($dates as $date) {
            $dailyNew = $newUsers->get($date, 0);
            $runningTotal += $dailyNew;
            $totalUsersTrend[] = ['date' => $date, 'count' => (int)$runningTotal];
        }

        return [
            'totalUsersTrend' => collect($totalUsersTrend)->map(fn($item) => ['date' => $item['date'], 'count' => (int)$item['count']])
        ];
    }

    /**
     * Calculate Sales Conversion Rate (%)
     */
    public function getConversionRate(int $companyId, int $financialYearId): int
    {
        $totalOrders = SalesOrder::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();

        if ($totalOrders === 0) return 0;

        $totalInvoices = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();

        return (int) round(($totalInvoices / $totalOrders) * 100);
    }

    /**
     * Calculate Growth Percentages for various metrics
     */
    public function getGrowthPercentages(int $companyId, int $financialYearId, $days = 30): array
    {
        if (!$days || $days == 'all') {
            return [
                'sales' => 0,
                'purchase' => 0,
                'grn' => 0,
                'newClients' => 0,
                'totalUsersDiff' => 0
            ];
        }

        $now = Carbon::now();
        
        if ($days === 'today') {
            $currentStart = Carbon::today();
            $prevStart = Carbon::yesterday();
            $prevEnd = Carbon::today()->subSecond(); // End of yesterday
            
            // Sales Growth
            $currentSales = SalesInvoice::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('invoice_date', [$currentStart, $now])
                ->sum('net_amount');
            $prevSales = SalesInvoice::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('invoice_date', [$prevStart, $prevEnd])
                ->sum('net_amount');

            // Purchase Growth
            $currentPurchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('invoice_date', [$currentStart, $now])
                ->sum('grand_total');
            $prevPurchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('invoice_date', [$prevStart, $prevEnd])
                ->sum('grand_total');

            // New Clients Growth
            $currentNewClients = Account::where('company_id', $companyId)
                ->where('party_type', 'customer')
                ->whereBetween('created_at', [$currentStart, $now])
                ->count();
            $prevNewClients = Account::where('company_id', $companyId)
                ->where('party_type', 'customer')
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->count();

            // GRN Growth
            $currentGrn = Grn::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('grn_date', [$currentStart, $now])
                ->sum(DB::raw('sub_total + total_tax'));
            $prevGrn = Grn::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereBetween('grn_date', [$prevStart, $prevEnd])
                ->sum(DB::raw('sub_total + total_tax'));
                
            return [
                'sales' => (int) $this->calculatePerc($currentSales, $prevSales),
                'purchase' => (int) $this->calculatePerc($currentPurchase, $prevPurchase),
                'grn' => (int) $this->calculatePerc($currentGrn, $prevGrn),
                'newClients' => (int) $this->calculatePerc($currentNewClients, $prevNewClients),
                'totalUsersDiff' => $currentNewClients
            ];
        }

        $currentStart = $now->copy()->subDays((int)$days);
        $prevStart = $now->copy()->subDays((int)$days * 2);

        // Sales Growth
        $currentSales = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('invoice_date', [$currentStart, $now])
            ->sum('net_amount');
        $prevSales = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('invoice_date', [$prevStart, $currentStart])
            ->sum('net_amount');

        // Purchase Growth
        $currentPurchase = PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('invoice_date', [$currentStart, $now])
            ->sum('grand_total');
        $prevPurchase = PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('invoice_date', [$prevStart, $currentStart])
            ->sum('grand_total');

        // New Clients Growth
        $currentNewClients = Account::where('company_id', $companyId)
            ->where('party_type', 'customer')
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $prevNewClients = Account::where('company_id', $companyId)
            ->where('party_type', 'customer')
            ->whereBetween('created_at', [$prevStart, $currentStart])
            ->count();

        // GRN Growth
        $currentGrn = Grn::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('grn_date', [$currentStart, $now])
            ->sum(DB::raw('sub_total + total_tax'));
        $prevGrn = Grn::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereBetween('grn_date', [$prevStart, $currentStart])
            ->sum(DB::raw('sub_total + total_tax'));

        return [
            'sales' => (int) $this->calculatePerc($currentSales, $prevSales),
            'purchase' => (int) $this->calculatePerc($currentPurchase, $prevPurchase),
            'grn' => (int) $this->calculatePerc($currentGrn, $prevGrn),
            'newClients' => (int) $this->calculatePerc($currentNewClients, $prevNewClients),
            'totalUsersDiff' => $currentNewClients // Absolute count for simpler display
        ];
    }



    /**
     * Fetch monthly overview for sales and purchases for a financial year
     */
    public function getMonthlyOverview(int $companyId, int $financialYearId): array
    {
        $financialYear = FinancialYear::find($financialYearId);
        if (!$financialYear) return [];

        $startDate = Carbon::parse($financialYear->start_date);
        $endDate = Carbon::parse($financialYear->end_date);

        $sales = SalesInvoice::select(
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('YEAR(invoice_date) as year'),
                DB::raw('SUM(net_amount) as amount')
            )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $purchases = PurchaseInvoice::select(
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('YEAR(invoice_date) as year'),
                DB::raw('SUM(grand_total) as amount')
            )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $data = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $key = $current->format('Y-m');
            $data[] = [
                'month'    => $current->format('M'),
                'sales'    => (float) ($sales[$key]['amount'] ?? 0),
                'purchase' => (float) ($purchases[$key]['amount'] ?? 0)
            ];
            $current->addMonth();
        }

        return $data;
    }

    /**
     * Fetch top selling items
     */
    public function getTopSellingItems(int $companyId, int $limit = 5): Collection
    {
        return SalesInvoiceItem::whereHas('salesInvoice', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->select('item_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(net_amount) as total_amount'))
            ->groupBy('item_id')
            ->orderByDesc('total_amount')
            ->with(['item', 'item.unit'])
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch recent sales invoices
     */
    public function getRecentInvoices(int $companyId, int $limit = 6): Collection
    {
        return SalesInvoice::where('company_id', $companyId)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->with('account')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch outstanding overview for donut chart
     */
    public function getOutstandingOverview(int $companyId, int $financialYearId): array
    {
        return AccountYearBalance::where('account_year_balances.company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->join('accounts', 'accounts.id', '=', 'account_year_balances.account_id')
            ->select('accounts.name as label', DB::raw('SUM(closing_balance) as value'))
            ->groupBy('label')
            ->orderByDesc('value')
            ->limit(4)
            ->get()
            ->toArray();
    }

    public function getProfitabilityTrend(int $companyId, int $financialYearId): array
    {
        $revenue = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->select(DB::raw('MONTH(invoice_date) as month'), DB::raw('SUM(net_amount) as amount'))
            ->groupBy('month')
            ->get()
            ->pluck('amount', 'month');

        $expenses = DB::table('vouchers')
            ->join('voucher_transactions', 'vouchers.id', '=', 'voucher_transactions.voucher_id')
            ->where('vouchers.company_id', $companyId)
            ->where('vouchers.financial_year_id', $financialYearId)
            ->where('vouchers.voucher_type_id', 12) // Expense type
            ->select(DB::raw('MONTH(vouchers.voucher_date) as month'), DB::raw('SUM(voucher_transactions.debit) as amount'))
            ->groupBy('month')
            ->get()
            ->pluck('amount', 'month');

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = [
                'month' => date("M", mktime(0, 0, 0, $m, 1)),
                'revenue' => (float)($revenue[$m] ?? 0),
                'expense' => (float)($expenses[$m] ?? 0)
            ];
        }
        return $data;
    }

    public function getCashFlowPreview(int $companyId): array
    {
        $receivable = SalesInvoice::where('company_id', $companyId)->sum(DB::raw('net_amount - received_amount'));
        $payable = PurchaseInvoice::where('company_id', $companyId)->sum(DB::raw('grand_total - paid_amount'));

        return [
            ['label' => 'Receivables', 'value' => (float)$receivable],
            ['label' => 'Payables', 'value' => (float)$payable]
        ];
    }

    private function calculatePerc($current, $prev): float
    {
        if ($prev <= 0) return $current > 0 ? 100 : 0;
        return round((($current - $prev) / $prev) * 100, 1);
    }

    /**
     * Fetch Godown Details Counts
     */
    public function getGodownDetailsCounts(int $companyId, int $financialYearId, $days = null): array
    {
        $query = \App\Models\GodownModule::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if ($days && $days !== 'all') {
            if ($days === 'today') {
                $query->where(function ($q) {
                    $q->whereHas('grn', function ($grn) {
                        $grn->whereDate('grn_date', Carbon::today());
                    });
                });
            } else {
                $query->where(function ($q) use ($days) {
                    $q->whereHas('grn', function ($grn) use ($days) {
                        $grn->whereDate('grn_date', '>=', Carbon::now()->subDays((int)$days)->format('Y-m-d'));
                    });
                });
            }
        }

        $productIn = (clone $query)->where('in_out_status', 'in')->where('is_cycle', 'close')->count();
        $productOut = (clone $query)->where('in_out_status', 'out')->where('is_cycle', 'close')->count();
        $pendingIn = (clone $query)->where('in_out_status', 'in')->where('is_cycle', 'open')->count();
        $pendingOut = (clone $query)->where('in_out_status', 'out')->where('is_cycle', 'open')->count();

        return [
            'product_in' => $productIn,
            'product_out' => $productOut,
            'pending_in' => $pendingIn,
            'pending_out' => $pendingOut
        ];
    }
}

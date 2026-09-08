<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    protected $dashboardRepo;

    public function __construct(DashboardRepository $dashboardRepo)
    {
        $this->dashboardRepo = $dashboardRepo;
    }

    /**
     * Aggregate all dashboard dashboard analytics into a single payload
     */
    public function getDashboardFilters(int $companyId, int $financialYearId, $days = 7): array
    {
        $sales = $this->dashboardRepo->getSalesFilters($companyId, $financialYearId, $days);
        $purchases = $this->dashboardRepo->getPurchaseFilters($companyId, $financialYearId, $days);
        $grn = $this->dashboardRepo->getGrnFilters($companyId, $financialYearId, $days);
        $accounts = $this->dashboardRepo->getAccountFilters($companyId, $days);
        $godown = $this->dashboardRepo->getGodownDetailsCounts($companyId, $financialYearId, $days);
        $trends = $this->dashboardRepo->getTrendData($companyId, $financialYearId, $days);
        $accountAnalytics = $this->dashboardRepo->getAccountAnalytics($companyId, $days);
        $conversionRate = $this->dashboardRepo->getConversionRate($companyId, $financialYearId);
        $growth = $this->dashboardRepo->getGrowthPercentages($companyId, $financialYearId, $days);
        $monthlyOverview = $this->dashboardRepo->getMonthlyOverview($companyId, $financialYearId);
        $topProducts = $this->dashboardRepo->getTopSellingItems($companyId, 5);
        $recentInvoices = $this->dashboardRepo->getRecentInvoices($companyId, 6);
        $outstandingOverview = $this->dashboardRepo->getOutstandingOverview($companyId, $financialYearId);

        $profitabilityTrend = $this->dashboardRepo->getProfitabilityTrend($companyId, $financialYearId);
        $cashFlowPreview = $this->dashboardRepo->getCashFlowPreview($companyId);

        return [
            'todaySales'       => $sales['today'],
            'totalRevenue'     => $sales['total'],
            'totalExpense'     => $purchases['total'],
            'totalGrnRevenue'  => $grn['total_revenue'],

            'totalUsers'       => $accounts['total_users'],
            'activeUsers'      => $accounts['active_users'],
            'activeUserPercentage' => $accounts['active_user_percentage'],
            'activeCustomers'  => $accounts['active_customers'],

            'salesData'        => $trends['salesData'],
            'purchaseData'     => $trends['purchaseData'],
            'grnData'          => $trends['grnData'],
            'labels'           => $trends['labels'],
    
            
            'totalUsersTrend'  => $accountAnalytics['totalUsersTrend'],
            'conversionRate'   => $conversionRate,
            'growth'           => $growth,
            'monthlyOverview'  => $monthlyOverview,
            'topProducts'      => $topProducts,
            'recentInvoices'   => $recentInvoices,
            'outstandingOverview' => $outstandingOverview,
            'profitabilityTrend'  => $profitabilityTrend,
            'cashFlowPreview'     => $cashFlowPreview,
            'godown'              => $godown,
        ];
    }

    /**
     * Get specific Filter data for individual card refreshes
     */
    public function getFilterData(string $type, $days, int $companyId, int $financialYearId): array
    {
        $trends = $this->dashboardRepo->getTrendData($companyId, $financialYearId, $days);
        $growth = $this->dashboardRepo->getGrowthPercentages($companyId, $financialYearId, $days);

        switch ($type) {
            case 'users':
                $accounts = $this->dashboardRepo->getAccountFilters($companyId, $days);
                $accountAnalytics = $this->dashboardRepo->getAccountAnalytics($companyId, $days);
                return [
                    'totalUsers' => $accounts['total_users'],
                    'activeUsers' => $accounts['active_users'],
                    'activeUserPercentage' => $accounts['active_user_percentage'],
                    'totalUsersTrend' => $accountAnalytics['totalUsersTrend'],
                    'growth' => ['newClients' => $growth['newClients'], 'totalUsersDiff' => $growth['totalUsersDiff']],
                    'labels' => $trends['labels']
                ];

            case 'sales':
                $sales = $this->dashboardRepo->getSalesFilters($companyId, $financialYearId, $days);
                return [
                    'todaySales' => $sales['today'],
                    'totalRevenue' => $sales['total'],
                    'salesData' => $trends['salesData'],
                    'growth' => ['sales' => $growth['sales']],
                    'labels' => $trends['labels']
                ];

            case 'purchase':
                $purchases = $this->dashboardRepo->getPurchaseFilters   ($companyId, $financialYearId, $days);
                return [
                    'totalExpense' => $purchases['total'],
                    'purchaseData' => $trends['purchaseData'],
                    'growth' => ['purchase' => $growth['purchase']],
                    'labels' => $trends['labels']
                ];

            case 'grn':
                $grn = $this->dashboardRepo->getGrnFilters($companyId, $financialYearId, $days);
                return [
                    'totalGrnRevenue' => $grn['total_revenue'],
                    'grnData' => $trends['grnData'],
                    'growth' => ['grn' => $growth['grn']],
                    'labels' => $trends['labels']
                ];

            case 'godown':
                $godown = $this->dashboardRepo->getGodownDetailsCounts($companyId, $financialYearId, $days);
                return [
                    'godown' => $godown
                ];

            case 'overview':
                $monthlyOverview = $this->dashboardRepo->getMonthlyOverview($companyId, $financialYearId);
                return [
                    'monthlyOverview' => $monthlyOverview
                ];

            case 'dairy':
                $outstandingOverview = $this->dashboardRepo->getOutstandingOverview($companyId, $financialYearId);
                return [
                    'outstandingOverview' => $outstandingOverview
                ];

            default:
                return [];
        }
    }
    /**
     * Get formatted invoices for AJAX response
     */
    public function getRecentInvoices(int $companyId, int $limit = 10): array
    {
        $invoices = $this->dashboardRepo->getRecentInvoices($companyId, $limit);
        
        return $invoices->map(function($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_serial' => $invoice->invoice_serial,
                'invoice_date' => $invoice->invoice_date,
                'customer_name' => $invoice->account->name ?? 'N/A',
                'grand_total' => $invoice->grand_total,
                'status' => $invoice->payment_received_status === 'fully_paid' ? 'Paid' : 'Unpaid'
            ];
        })->toArray();
    }
    /**
     * Get formatted top selling items for AJAX response
     */
    public function getTopSellingItems(int $companyId, int $limit = 5): array
    {
        $products = $this->dashboardRepo->getTopSellingItems($companyId, $limit);
        
        return $products->map(function($product) {
            return [
                'item_name' => $product->item->name ?? 'Unknown',
                'total_qty' => $product->total_qty,
                'unit' => $product->item->unit->name ?? '',
                'total_amount' => $product->total_amount
            ];
        })->toArray();
    }
}

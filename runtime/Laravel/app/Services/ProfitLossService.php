<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Repositories\VoucherRepository;
use Illuminate\Support\Collection;

class ProfitLossService
{
    // Root classification codes (Chart of Accounts group codes)
    // Trading Account — Credit side
    const CODE_SALES             = '390'; // Sales
    const CODE_DIRECT_INCOME     = '400'; // Direct Income (Operational)
    // P&L Account
    const CODE_INDIRECT_INCOME   = '410'; // Indirect Income         — P&L Credit
    const CODE_INDIRECT_EXPENSE  = '420'; // Indirect/Admin Expenses — P&L Debit
    // Trading Account — Debit side
    const CODE_PURCHASE          = '450'; // Purchase
    const CODE_DIRECT_EXPENSE    = '440'; // Direct / Manufacturing Expenses
    // Asset group — handled by StockVoucherService; never via accounting ledger
    const CODE_STOCK_IN_HAND     = '140';

    protected VoucherRepository   $voucherRepo;
    protected StockVoucherService $stockVoucherService;

    public function __construct(VoucherRepository $voucherRepo, StockVoucherService $stockVoucherService)
    {
        $this->voucherRepo         = $voucherRepo;
        $this->stockVoucherService = $stockVoucherService;
    }

    public function getTradingAndPLReport(int $companyId, int $financialYearId, array $filters): array
    {
        $trading = $this->getTradingAccount($companyId, $financialYearId, $filters);
        $pl      = $this->getPLAccount($companyId, $financialYearId, $filters, $trading);

        return [
            'trading'   => $trading,
            'pl'        => $pl,
            'is_profit' => $pl['is_net_profit'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────────
    // Trading Account
    //
    // Debit  : Opening Stock | groups under code 440 (Direct/Manufacturing Expenses)
    // Credit : Closing Stock | groups under code 400 (Direct Income / Operational)
    //
    // Classification by ancestor code, NOT group name matching.
    // ─────────────────────────────────────────────────────────────────────────────────
    public function getTradingAccount(int $companyId, int $financialYearId, array $filters): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $filters['to_date']   ?? now()->format('Y-m-d');

        // ── 1. Inventory stock (NOT accounting ledger) ────────────────────────────────
        $stockValuation = $this->stockVoucherService->getStockValuationForTrading(
            $companyId, $financialYearId, $fromDate, $toDate
        );

        // ── 2. Account movements + group tree ────────────────────────────────────────
        $movements = $this->voucherRepo
            ->getAccountMovements($companyId, $financialYearId, $fromDate, $toDate)
            ->keyBy('account_id');

        $groupTree = $this->loadGroupTree($companyId);

        $accounts = Account::where('company_id', $companyId)
            ->select('id', 'name', 'account_group_id')
            ->with('accountGroup:id,name,code,type,parent_id')
            ->get()
            ->keyBy('id');

        $debitBuckets  = [];
        $creditBuckets = [];

        foreach ($accounts as $account) {
            $group = $account->accountGroup;
            if (!$group) continue;

            $movement = $movements[$account->id] ?? null;
            if (!$movement) continue;

            // Walk up parent chain to find classification root code
            $rootCode = $this->findAncestorCode($group->id, $groupTree);

            if ($rootCode === null) continue; // balance-sheet accounts etc.

            $debit  = round((float) $movement->debit,  2);
            $credit = round((float) $movement->credit, 2);

            if ($rootCode === self::CODE_PURCHASE) {
                // Code 450 (Purchase) → Trading Debit, second position
                $this->pushToBucket($debitBuckets, 'trading_dr_purchase', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($debit - $credit, 2),
                ]);
            } elseif ($rootCode === self::CODE_DIRECT_EXPENSE) {
                // Code 440 (Direct/Mfg Expenses) → Trading Debit, third position
                $this->pushToBucket($debitBuckets, 'trading_dr_direct_expense', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($debit - $credit, 2),
                ]);
            } elseif ($rootCode === self::CODE_SALES) {
                // Code 390 (Sales) → Trading Credit, second position
                $this->pushToBucket($creditBuckets, 'trading_cr_sales', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($credit - $debit, 2),
                ]);
            } elseif ($rootCode === self::CODE_DIRECT_INCOME) {
                // Code 400 (Direct Income) → Trading Credit, third position
                $this->pushToBucket($creditBuckets, 'trading_cr_direct_income', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($credit - $debit, 2),
                ]);
            }
            // Code 140 (Stock-in-Hand) → skipped (inventory via StockVoucherService)
            // Codes 410 / 420 → P&L (handled by getPLAccount)
        }

        // ── 3. Build hierarchy ────────────────────────────────────────────────────────
        // Order: Opening Stock (prepended below) → Purchase → Direct/Mfg Expenses
        $debitItems  = $this->buildHierarchy($debitBuckets,  ['trading_dr_purchase', 'trading_dr_direct_expense']);
        // Order: Closing Stock (prepended below) → Sales → Direct Income
        $creditItems = $this->buildHierarchy($creditBuckets, ['trading_cr_sales', 'trading_cr_direct_income']);

        // ── 4. Prepend inventory stock (already hierarchical) ─────────────────────────
        $debitItems  = array_merge($stockValuation['opening_stock']['items'], $debitItems);
        $creditItems = array_merge($stockValuation['closing_stock']['items'],  $creditItems);

        // ── 5. Totals ─────────────────────────────────────────────────────────────────
        $debitSubtotal  = round(array_sum(array_column($debitItems,  'amount')), 2);
        $creditSubtotal = round(array_sum(array_column($creditItems, 'amount')), 2);

        $grossDiff     = round($creditSubtotal - $debitSubtotal, 2);
        $isGrossProfit = $grossDiff >= 0;
        $tradingTotal  = round(max($debitSubtotal, $creditSubtotal), 2);

        return [
            'debit'               => $debitItems,
            'credit'              => $creditItems,
            'debit_subtotal'      => $debitSubtotal,
            'credit_subtotal'     => $creditSubtotal,
            'gross_profit'        => $isGrossProfit  ? $grossDiff      : 0.0,
            'gross_loss'          => !$isGrossProfit ? abs($grossDiff) : 0.0,
            'is_gross_profit'     => $isGrossProfit,
            'trading_total'       => $tradingTotal,
            'opening_stock_total' => $stockValuation['opening_stock']['total_amount'],
            'closing_stock_total' => $stockValuation['closing_stock']['total_amount'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────────
    // P&L Account
    //
    // Debit  : Gross Loss b/d (if loss) | groups under code 420 (Indirect Expenses)
    // Credit : Gross Profit b/d         | groups under code 410 (Indirect Income)
    //
    // $trading — result of getTradingAccount() (provides gross_profit / gross_loss)
    // ─────────────────────────────────────────────────────────────────────────────────
    public function getPLAccount(int $companyId, int $financialYearId, array $filters, array $trading): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $filters['to_date']   ?? now()->format('Y-m-d');

        $movements = $this->voucherRepo
            ->getAccountMovements($companyId, $financialYearId, $fromDate, $toDate)
            ->keyBy('account_id');

        $groupTree = $this->loadGroupTree($companyId);

        $accounts = Account::where('company_id', $companyId)
            ->select('id', 'name', 'account_group_id')
            ->with('accountGroup:id,name,code,type,parent_id')
            ->get()
            ->keyBy('id');

        $debitBuckets  = [];
        $creditBuckets = [];

        foreach ($accounts as $account) {
            $group = $account->accountGroup;
            if (!$group) continue;

            $movement = $movements[$account->id] ?? null;
            if (!$movement) continue;

            $rootCode = $this->findAncestorCode($group->id, $groupTree);

            if ($rootCode === null) continue;

            $debit  = round((float) $movement->debit,  2);
            $credit = round((float) $movement->credit, 2);

            if ($rootCode === self::CODE_INDIRECT_EXPENSE) {
                // Under code 420 → P&L Debit
                $this->pushToBucket($debitBuckets, 'indirect_expense', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($debit - $credit, 2),
                ]);
            } elseif ($rootCode === self::CODE_INDIRECT_INCOME) {
                // Under code 410 → P&L Credit
                $this->pushToBucket($creditBuckets, 'indirect_income', $group->id, $group->name, [
                    'account_id' => $account->id,
                    'name'       => $account->name,
                    'amount'     => round($credit - $debit, 2),
                ]);
            }
            // Codes 390/400/440/450 → Trading; 140 → inventory. All skipped here.
        }

        $debitItems  = $this->buildHierarchy($debitBuckets,  ['indirect_expense']);
        $creditItems = $this->buildHierarchy($creditBuckets, ['indirect_income']);

        $debitSubtotal  = round(array_sum(array_column($debitItems,  'amount')), 2);
        $creditSubtotal = round(array_sum(array_column($creditItems, 'amount')), 2);

        // Gross profit / loss carried from Trading Account
        $grossProfit = $trading['gross_profit']; // positive when profit; 0 when loss
        $grossLoss   = $trading['gross_loss'];   // positive when loss;   0 when profit

        $plCreditTotal = round($creditSubtotal + $grossProfit, 2);
        $plDebitTotal  = round($debitSubtotal  + $grossLoss,   2);

        $netDiff     = round($plCreditTotal - $plDebitTotal, 2);
        $isNetProfit = $netDiff >= 0;
        $plTotal     = round(max($plDebitTotal, $plCreditTotal), 2);

        return [
            'debit'           => $debitItems,
            'credit'          => $creditItems,
            'debit_subtotal'  => $debitSubtotal,
            'credit_subtotal' => $creditSubtotal,
            'net_profit'      => $isNetProfit  ? $netDiff      : 0.0,
            'net_loss'        => !$isNetProfit ? abs($netDiff) : 0.0,
            'is_net_profit'   => $isNetProfit,
            'pl_total'        => $plTotal,
        ];
    }

    public function preparePrintData(mixed $company, array $filters): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $filters['to_date']   ?? now()->format('Y-m-d');

        $report = $this->getTradingAndPLReport(
            companyId:       $company->id,
            financialYearId: financial_year_id(),
            filters:         $filters
        );

        return [
            'company'    => $company,
            'filters'    => $filters,
            'report'     => $report,
            'datePeriod' => date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate)),
        ];
    }
    public function prepareExportFormat(mixed $company, array $filters, string $_format): array
    {
        $fromDate = $filters['from_date'] ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $filters['to_date']   ?? now()->format('Y-m-d');

        $report = $this->getTradingAndPLReport(
            companyId:       $company->id,
            financialYearId: financial_year_id(),
            filters:         $filters
        );

        $datePeriod = date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate));
        $fileName   = 'profit-loss-' . date('Ymd-His') . '.xlsx';

        $export = new \App\Exports\ProfitLossExport($company, $report, $filters, $datePeriod);

        \Maatwebsite\Excel\Facades\Excel::store($export, 'exports/' . $fileName, 'public');

        return [
            'format'   => 'xlsx',
            'file_url' => asset('storage/exports/' . $fileName),
            'file_name' => $fileName,
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────────

    /**
     * Load all account groups for the company into a keyed Collection
     * (id → AccountGroup with code, parent_id).
     */
    private function loadGroupTree(int $companyId): Collection
    {
        return AccountGroup::where('company_id', $companyId)
            ->select('id', 'name', 'code', 'type', 'parent_id')
            ->get()
            ->keyBy('id');
    }

    /**
     * Walk up the account-group parent chain until we find a group whose code
     * matches one of the classification roots.
     * Returns the code as a string, or null if no match found (e.g. balance-sheet).
     */
    private function findAncestorCode(int $groupId, Collection $groups): ?string
    {
        $targets = [
            self::CODE_SALES,
            self::CODE_DIRECT_INCOME,
            self::CODE_INDIRECT_INCOME,
            self::CODE_INDIRECT_EXPENSE,
            self::CODE_PURCHASE,
            self::CODE_DIRECT_EXPENSE,
            self::CODE_STOCK_IN_HAND, // identified so we can explicitly skip it
        ];

        $visited = [];
        $current = $groupId;

        while ($current && !isset($visited[$current])) {
            $visited[$current] = true;
            $group = $groups->get($current);
            if (!$group) break;

            if ($group->code !== null && in_array((string) $group->code, $targets)) {
                return (string) $group->code;
            }

            $current = $group->parent_id;
        }

        return null;
    }

    private function pushToBucket(array &$buckets, string $section, int $groupId, string $groupName, array $accountRow): void
    {
        if (!isset($buckets[$section][$groupId])) {
            $buckets[$section][$groupId] = ['group_name' => $groupName, 'accounts' => []];
        }
        $buckets[$section][$groupId]['accounts'][] = $accountRow;
    }

    /**
     * Build the hierarchical flat array used by the front-end.
     *
     * Level 0 → account group  (is_group=true,  is_account=false, is_item=false)
     * Level 1 → account ledger (is_group=false, is_account=true,  is_item=false)
     *
     * Stock items (is_item=true) are built by StockVoucherService and prepended separately.
     */
    private function buildHierarchy(array $buckets, array $sectionOrder): array
    {
        $items = [];

        foreach ($sectionOrder as $section) {
            $groups = $buckets[$section] ?? [];
            uasort($groups, fn($a, $b) => strcmp($a['group_name'], $b['group_name']));

            foreach ($groups as $groupId => $data) {
                $accounts = $data['accounts'];
                usort($accounts, fn($a, $b) => strcmp($a['name'], $b['name']));

                $children = array_map(fn($acc) => [
                    'account_id'       => $acc['account_id'],
                    'account_group_id' => (int) $groupId,
                    'name'             => $acc['name'],
                    'amount'           => $acc['amount'],
                    'is_group'         => false,
                    'is_account'       => true,
                    'is_item'          => false,
                    'level'            => 1,
                    'section'          => $section,
                ], $accounts);

                $items[] = [
                    'account_group_id' => (int) $groupId,
                    'name'             => $data['group_name'],
                    'amount'           => round(array_sum(array_column($children, 'amount')), 2),
                    'is_group'         => true,
                    'is_account'       => false,
                    'is_item'          => false,
                    'level'            => 0,
                    'section'          => $section,
                    'children'         => $children,
                ];
            }
        }

        return $items;
    }
}

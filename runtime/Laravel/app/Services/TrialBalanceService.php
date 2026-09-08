<?php

namespace App\Services;

use App\Exports\TrialBalanceExport;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\FinancialYear;
use App\Repositories\VoucherRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TrialBalanceService
{
    protected VoucherRepository $voucherRepo;
    protected LedgerReportService $ledgerRepo;

    public function __construct(VoucherRepository $voucherRepo, LedgerReportService $ledgerRepo)
    {
        $this->voucherRepo = $voucherRepo;
        $this->ledgerRepo  = $ledgerRepo;
    }

    /**
     * Main Trial Balance report — drives all 4 combinations of:
     * report_type (group_wise|alphabetic) x view_type (balance|detail).
     *
     * $filters: report_type, view_type, parent_group (alphabetic only),
     *           as_on_date (balance mode) or from_date/to_date (detail mode)
     */
    public function getTrialBalanceReport(int $companyId, int $financialYearId, array $filters = []): array
    {
        $reportType = $filters['report_type'] ?? 'group_wise';
        $viewType   = $filters['view_type']   ?? 'balance';

        if ($viewType === 'detail') {
            $fromDate       = $filters['from_date'] ?? '1900-01-01';
            $toDate         = $filters['to_date']   ?? now()->format('Y-m-d');
            $includeOpening = true;
        } elseif ($viewType === 'balance_only') {
            $fyStart        = FinancialYear::find($financialYearId)?->start_date ?? now()->startOfYear()->format('Y-m-d');
            $fromDate       = Carbon::parse($fyStart)->format('Y-m-d');
            $toDate         = $filters['as_on_date'] ?? now()->format('Y-m-d');
            $includeOpening = false;
        } else {
            $fromDate       = '1900-01-01';
            $toDate         = $filters['as_on_date'] ?? now()->format('Y-m-d');
            $includeOpening = true;
        }

        $accountRows = $this->buildAccountMovementRows($companyId, $financialYearId, $fromDate, $toDate, $includeOpening);

        if ($reportType === 'alphabetic') {
            $parentGroup = $filters['parent_group'] ?? 'no';

            usort($accountRows, fn($a, $b) => strcmp($a['account_name'], $b['account_name']));

            $rows = array_map(fn($r) => $this->formatTrialBalanceRow($r, 'account_name', $viewType), $accountRows);
        } else {
            $rows = $this->aggregateRowsByGroup($accountRows, $viewType);
        }
        // dd($rows);
        return $this->buildTrialBalanceResult($rows, $filters);
    }

    /**
     * Computes signed opening, gross period debit/credit and signed closing per account.
     *
     * opening = FY brought-forward balance (is_opening vouchers, fixed) + non-opening movement
     * before $fromDate. For Balance Only, $fromDate is a sentinel before any data exists, so that
     * second term is always 0 and opening reduces to just the FY brought-forward balance. For
     * Detail, it's the real balance as of the day before the selected From Date.
     *
     * Both modes report Debit/Credit as non-opening movement within [$fromDate, $toDate].
     *
     * Merges two queries by account_id rather than just iterating one, so accounts whose only
     * activity is an opening-voucher entry (no other transactions at all) aren't dropped.
     */
    private function buildAccountMovementRows(int $companyId, int $financialYearId, string $fromDate, string $toDate, bool $includeOpening = true): array
    {
        $movements = $this->voucherRepo->getAccountMovements($companyId, $financialYearId, $fromDate, $toDate)->keyBy('account_id');
        $openingVoucherBalances = $includeOpening
            ? $this->voucherRepo->getOpeningVoucherBalances($companyId, $financialYearId)
            : collect();

        $accounts = Account::where('company_id', $companyId)
            // ->where('is_system', false)
            ->select('id', 'name', 'account_group_id')
            ->with('accountGroup:id,name')
            ->get()
            ->keyBy('id');

        $accountIds = $movements->keys()->merge($openingVoucherBalances->keys())->unique();

        $rows = [];

        foreach ($accountIds as $accountId) {
            $account = $accounts[$accountId] ?? null;

            if (!$account) {
                continue;
            }

            $m = $movements->get($accountId);

            $openingVoucher  = $includeOpening ? round((float) ($openingVoucherBalances[$accountId] ?? 0), 2) : 0.0;
            $preDateMovement = $m ? round((float) $m->opening, 2) : 0.0;
            $opening = round($openingVoucher + $preDateMovement, 2);
            $debit   = $m ? round((float) $m->debit, 2) : 0.0;
            $credit  = $m ? round((float) $m->credit, 2) : 0.0;
            $closing = round($opening + $debit - $credit, 2);

            if ($opening == 0.0 && $debit == 0.0 && $credit == 0.0 && $closing == 0.0) {
                continue;
            }

            $rows[] = [
                'account_id'         => $account->id,
                'account_name'       => $account->name,
                'account_group_id'   => $account->account_group_id,
                'account_group_name' => $account->accountGroup->name ?? 'Unknown Group',
                'opening'            => $opening,
                'debit'              => $debit,
                'credit'             => $credit,
                'closing'            => $closing,
            ];
        }

        return $rows;
    }

    private function aggregateRowsByGroup(array $accountRows, string $viewType): array
    {
        $grouped   = collect($accountRows)->groupBy('account_group_id');
        $groupRows = [];

        foreach ($grouped as $groupId => $rowsInGroup) {
            $opening = round($rowsInGroup->sum('opening'), 2);
            $debit   = round($rowsInGroup->sum('debit'), 2);
            $credit  = round($rowsInGroup->sum('credit'), 2);
            $closing = round($opening + $debit - $credit, 2);

            if ($opening == 0.0 && $debit == 0.0 && $credit == 0.0 && $closing == 0.0) {
                continue;
            }

            $groupRows[] = [
                'account_group_id'   => $groupId,
                'account_group_name' => $rowsInGroup->first()['account_group_name'],
                'opening'            => $opening,
                'debit'              => $debit,
                'credit'             => $credit,
                'closing'            => $closing,
            ];
        }

        usort($groupRows, fn($a, $b) => strcmp($a['account_group_name'], $b['account_group_name']));

        return array_map(fn($r) => $this->formatTrialBalanceRow($r, 'account_group_name', $viewType), $groupRows);
    }

    /**
     * Balance Only displays Debit/Credit as the netted closing balance split into Dr/Cr sides
     * (no separate Opening/Closing columns shown) — Detail shows the real gross Debit/Credit
     * plus Opening/Closing. 'opening'/'closing' are always included in the returned row (used
     * by buildTrialBalanceResult's totals/difference calc) even when not rendered as columns.
     */
    private function formatTrialBalanceRow(array $row, string $labelField, string $viewType): array
    {
        $base = [
            'account_group_id'   => $row['account_group_id'],
            'account_group_name' => $row['account_group_name'],
        ];

        if ($labelField === 'account_name') {
            $base['account_id']   = $row['account_id'];
            $base['account_name'] = $row['account_name'];
        }

        if ($viewType === 'balance_only') {
            return $base + [
                'opening' => 0,
                'debit'   => $row['debit'],
                'credit'  => $row['credit'],
                'closing' => round($row['debit'] - $row['credit'], 2),
            ];
        }

        if ($viewType === 'balance') {
            return $base + [
                'opening' => $row['opening'],
                'debit'   => $row['closing'] > 0 ? $row['closing'] : 0,
                'credit'  => $row['closing'] < 0 ? abs($row['closing']) : 0,
                'closing' => $row['closing'],
            ];
        }

        return $base + [
            'opening' => $row['opening'],
            'debit'   => $row['debit'],
            'credit'  => $row['credit'],
            'closing' => $row['closing'],
        ];
    }

    private function buildTrialBalanceResult(array $rows, array $filters): array
    {
        $totalOpening = round(array_sum(array_column($rows, 'opening')), 2);
        $totalDebit   = round(array_sum(array_column($rows, 'debit')), 2);
        $totalCredit  = round(array_sum(array_column($rows, 'credit')), 2);
        $totalClosing = round(array_sum(array_column($rows, 'closing')), 2);

        $closingDebitTotal  = round(array_sum(array_map(fn($r) => $r['closing'] > 0 ? $r['closing'] : 0, $rows)), 2);
        $closingCreditTotal = round(array_sum(array_map(fn($r) => $r['closing'] < 0 ? abs($r['closing']) : 0, $rows)), 2);
        $diff = round($closingDebitTotal - $closingCreditTotal, 2);

        return [
            'data'        => $rows,
            'last_page'   => 1,
            'filters'     => $filters,
            'grand_total' => [
                'total_opening' => $totalOpening,
                'total_debit'   => $totalDebit,
                'total_credit'  => $totalCredit,
                'total_closing' => $totalClosing,
            ],
            'difference' => [
                'debit'  => $closingDebitTotal < $closingCreditTotal ? abs($diff) : 0,
                'credit' => $closingDebitTotal > $closingCreditTotal ? abs($diff) : 0,
            ],
        ];
    }

    /**
     * Group Wise drill-down with Balance Only (Debit/Credit netted from the as-on-date closing
     * balance) columns. Sibling to getGroupWiseTrialBalance() — kept separate because that
     * method's route/session contract is shared with the Opening Trial Balance page, and it
     * always filters to is_opening=true regardless of the date passed in, which doesn't match
     * the main Trial Balance page's "FY opening + current year movement, as of date" semantics.
     */
    public function getGroupWiseTrialBalanceBalance(int $groupId, array $filters): array
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $toDate   = $filters['as_on_date'] ?? now()->format('Y-m-d');
        $viewType = $filters['view_type']  ?? 'balance_only';

        if ($viewType === 'balance_only') {
            $fyStart  = FinancialYear::find($financialYearId)?->start_date ?? now()->startOfYear()->format('Y-m-d');
            $fromDate = Carbon::parse($fyStart)->format('Y-m-d');
            $includeOpening = false;
        } else {
            $fromDate = '1900-01-01';
            $includeOpening = true;
        }

        $groupName = AccountGroup::where('company_id', $companyId)->where('id', $groupId)->value('name') ?? '';

        $accountRows = $this->buildAccountMovementRows($companyId, $financialYearId, $fromDate, $toDate, $includeOpening);
        $accountRows = array_values(array_filter($accountRows, fn($r) => $r['account_group_id'] == $groupId));

        usort($accountRows, fn($a, $b) => strcmp($a['account_name'], $b['account_name']));

        if ($viewType === 'balance_only') {
            $rows = array_map(fn($r) => [
                'account_id'   => $r['account_id'],
                'account_name' => $r['account_name'],
                'opening'      => 0,
                'debit'        => $r['debit'],
                'credit'       => $r['credit'],
                'closing'      => round($r['debit'] - $r['credit'], 2),
            ], $accountRows);
        } else {
            $rows = array_map(fn($r) => [
                'account_id'   => $r['account_id'],
                'account_name' => $r['account_name'],
                'opening'      => $r['opening'],
                'debit'        => $r['closing'] > 0 ? $r['closing'] : 0,
                'credit'       => $r['closing'] < 0 ? abs($r['closing']) : 0,
                'closing'      => $r['closing'],
            ], $accountRows);
        }

        $totalOpening = round(array_sum(array_column($rows, 'opening')), 2);
        $totalDebit   = round(array_sum(array_column($rows, 'debit')), 2);
        $totalCredit  = round(array_sum(array_column($rows, 'credit')), 2);
        $totalClosing = round(array_sum(array_column($rows, 'closing')), 2);

        return [
            'data'        => $rows,
            'group_name'  => $groupName,
            'date_range'  => $filters,
            'grand_total' => [
                'total_opening' => $totalOpening,
                'total_debit'   => $totalDebit,
                'total_credit'  => $totalCredit,
                'total_closing' => $totalClosing,
            ],
        ];
    }

    /**
     * Group Wise drill-down with Detail (Opening/Debit/Credit/Closing) columns.
     * Sibling to getGroupWiseTrialBalance() — kept separate because that method's
     * route/session contract is shared with the Opening Trial Balance page.
     */
    public function getGroupWiseTrialBalanceDetail(int $groupId, array $filters): array
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $fromDate = $filters['from_date'] ?? '1900-01-01';
        $toDate   = $filters['to_date']   ?? now()->format('Y-m-d');

        $groupName = AccountGroup::where('company_id', $companyId)->where('id', $groupId)->value('name') ?? '';

        $accountRows = $this->buildAccountMovementRows($companyId, $financialYearId, $fromDate, $toDate);
        $accountRows = array_values(array_filter($accountRows, fn($r) => $r['account_group_id'] == $groupId));

        usort($accountRows, fn($a, $b) => strcmp($a['account_name'], $b['account_name']));

        $rows = array_map(fn($r) => [
            'account_id'   => $r['account_id'],
            'account_name' => $r['account_name'],
            'opening'      => $r['opening'],
            'debit'        => $r['debit'],
            'credit'       => $r['credit'],
            'closing'      => $r['closing'],
        ], $accountRows);

        $totalOpening = round(array_sum(array_column($rows, 'opening')), 2);
        $totalDebit   = round(array_sum(array_column($rows, 'debit')), 2);
        $totalCredit  = round(array_sum(array_column($rows, 'credit')), 2);
        $totalClosing = round(array_sum(array_column($rows, 'closing')), 2);

        return [
            'data'        => $rows,
            'group_name'  => $groupName,
            'date_range'  => $filters,
            'grand_total' => [
                'total_opening' => $totalOpening,
                'total_debit'   => $totalDebit,
                'total_credit'  => $totalCredit,
                'total_closing' => $totalClosing,
            ],
        ];
    }

    public function getOpeningTrialBalance(int $companyId, object $company, int $financialYearId, array $filters = []): array
    {

        $startDate = $filters['start_date']
            ?? Carbon::parse($company->currentFinancialYear->start_date)->format('Y-m-d');

        $accountGroups = AccountGroup::where('company_id', $companyId)->pluck('name', 'id')->toArray();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_system', false)
            ->select('id', 'account_group_id')
            ->get()
            ->keyBy('id');

        $systemAccountIdForOpening = Account::where('company_id', $companyId)->where('code', 35000)->value('id');

        $openingBalances = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('is_opening', true)
            ->whereNull('v.deleted_at')
            ->where('account_id', '!=', $systemAccountIdForOpening)
            ->groupBy('vt.account_id')
            ->select(
                'vt.account_id',
                DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance')
            )
            ->get();

        $groupedData = [];
        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($openingBalances as $item) {

            $account = $accounts[$item->account_id] ?? null;

            if (!$account) {
                continue;
            }

            $groupId   = $account->account_group_id;
            $groupName = $accountGroups[$groupId] ?? 'Unknown Group';

            if (!isset($groupedData[$groupId])) {
                $groupedData[$groupId] = [
                    'account_group_id'   => $groupId,
                    'account_group_name' => $groupName,
                    'debit'              => 0,
                    'credit'             => 0,
                    'balance'            => 0,
                ];
            }
            $balance = (float) $item->balance;


            if ($balance > 0) {
                $groupedData[$groupId]['debit'] += $balance;
                $groupedData[$groupId]['balance'] += $balance;
                $totalDebit += $balance;
            } elseif ($balance < 0) {
                $creditAmount = abs($balance);

                $groupedData[$groupId]['credit'] += $creditAmount;
                $groupedData[$groupId]['balance'] -= $creditAmount;
                $totalCredit += $creditAmount;
            }
        }

        $groupedData = array_values(array_filter($groupedData, fn($group) => $group['balance'] != 0));


        usort($groupedData, function ($a, $b) {
            return strcmp($a['account_group_name'], $b['account_group_name']);
        });

        $totalDebit  = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        $differenceAmount = round(abs($totalDebit - $totalCredit), 2);
        // dd($groupedData);
        return [
            'data' => $groupedData,

            'last_page' => 1,

            'grand_total' => [
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
            ],

            'difference' => [
                'debit'  => $totalDebit < $totalCredit ? $differenceAmount : 0,
                'credit' => $totalDebit > $totalCredit ? $differenceAmount : 0,
            ],
        ];
    }

    public function getGroupWiseTrialBalance(int $groupId, $company, array $filters): array
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $startDate = $filters['start_date']
            ?? Carbon::parse($company->currentFinancialYear->start_date)->format('Y-m-d');
        $endDate = $filters['end_date']
            ?? Carbon::parse($company->currentFinancialYear->end_date)->format('Y-m-d');

        $groupName = AccountGroup::where('company_id', $companyId)->where('id', $groupId)->value('name') ?? '';

        $accounts = Account::where('company_id', $companyId)
            ->where('account_group_id', $groupId)
            ->where('is_system', false)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');


        $systemAccountIdForOpening = Account::where('company_id', $companyId)->where('code', 35000)->value('id');

        $accountBalances = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('is_opening', true)
            ->whereNull('v.deleted_at')
            ->where('account_id', '!=', $systemAccountIdForOpening)
            ->groupBy('vt.account_id')
            ->select(
                'vt.account_id',
                DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance')
            )
            ->get();

        $accountWiseTrialBalance = [];
        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($accountBalances as $item) {
            $account = $accounts[$item->account_id] ?? null;

            if (!$account) {
                continue;
            }

            $balance = (float) $item->balance;

            if ($balance == 0) {
                continue;
            }

            $debit  = $balance > 0 ? $balance : 0;
            $credit = $balance < 0 ? abs($balance) : 0;

            $accountWiseTrialBalance[] = [
                'account_id'   => $account->id,
                'account_name' => $account->name,
                'debit'        => round($debit, 2),
                'credit'       => round($credit, 2),
                'balance'      => round($balance, 2),
            ];

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        usort($accountWiseTrialBalance, fn($a, $b) => strcmp($a['account_name'], $b['account_name']));

        $totalDebit  = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        $differenceAmount = round(abs($totalDebit - $totalCredit), 2);
        return [
            'data'        => $accountWiseTrialBalance,
            'last_page'   => 1,
            'group_name'  => $groupName,
            'date_range'  => $filters,
            'grand_total' => [
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
            ],
            'difference' => [
                'debit'  => $totalDebit < $totalCredit ? $differenceAmount : 0,
                'credit' => $totalDebit > $totalCredit ? $differenceAmount : 0,
            ],
        ];
    }

    public function prepareTrialBalancePrintData($company, array $filters): array
    {
        $trialBalanceData = $this->getTrialBalanceReport(company_id(), financial_year_id(), $filters);
        $tableConfig       = $this->buildTrialBalanceTableConfig($filters);

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'portrait',
        ];
    }

    public function prepareTrialBalanceExportFormat($company, array $filters, string $format): array
    {
        $trialBalanceData = $this->getTrialBalanceReport(company_id(), financial_year_id(), $filters);
        $isDetail          = ($filters['view_type']   ?? 'balance') === 'detail';
        $labelHeading      = ($filters['report_type'] ?? 'group_wise') === 'alphabetic' ? 'Account Name' : 'Account Group Name';
        $labelField        = ($filters['report_type'] ?? 'group_wise') === 'alphabetic' ? 'account_name' : 'account_group_name';

        $headings = $isDetail
            ? ['Sr No.', $labelHeading, 'Opening', 'Debit', 'Credit', 'Closing']
            : ['Sr No.', $labelHeading, 'Debit', 'Credit'];

        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => $isDetail
            ? [$key + 1, $item[$labelField], $item['opening'], $item['debit'], $item['credit'], $item['closing']]
            : [$key + 1, $item[$labelField], $item['debit'], $item['credit']]);

        $grandTotal = $trialBalanceData['grand_total'] ?? [];
        $difference = $trialBalanceData['difference']  ?? [];

        $totalsRow = $isDetail
            ? [null, null, $grandTotal['total_opening'] ?? 0, $grandTotal['total_debit'] ?? 0, $grandTotal['total_credit'] ?? 0, $grandTotal['total_closing'] ?? 0]
            : [null, null, $grandTotal['total_debit'] ?? 0, $grandTotal['total_credit'] ?? 0];

        $differenceRow = $isDetail
            ? null
            : [null, null, ($difference['debit'] ?? 0) > 0 ? $difference['debit'] : 0, ($difference['credit'] ?? 0) > 0 ? $difference['credit'] : 0];

        $fileName = $this->storeExcelFile($company, 'trial_balance', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title'  => 'Trial Balance Report',
                'filters'       => $filters,
                'totalDebit'    => $trialBalanceData['grand_total']['total_debit']  ?? 0,
                'totalCredit'   => $trialBalanceData['grand_total']['total_credit'] ?? 0,
                'differenceDebit'  => $difference['debit']  ?? 0,
                'differenceCredit' => $difference['credit'] ?? 0,
                'totalsRow'     => $totalsRow,
                'differenceRow' => $differenceRow,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    private function buildTrialBalanceTableConfig(array $filters): array
    {
        $isAlpha    = ($filters['report_type'] ?? 'group_wise') === 'alphabetic';
        $isDetail   = ($filters['view_type']   ?? 'balance') === 'detail';
        $labelText  = $isAlpha ? 'Account Name' : 'Account Group Name';
        $labelField = $isAlpha ? 'account_name' : 'account_group_name';

        $columns = [
            ['label' => 'Sr No.', 'class' => 'text-start', 'width' => '8%'],
            ['label' => $labelText, 'class' => 'text-start', 'width' => $isDetail ? '24%' : '32%', 'field' => $labelField],
        ];

        if ($isDetail) {
            $columns[] = ['label' => 'Opening', 'class' => 'text-end', 'width' => '17%', 'field' => 'opening'];
            $columns[] = ['label' => 'Debit',   'class' => 'text-end', 'width' => '17%', 'field' => 'debit'];
            $columns[] = ['label' => 'Credit',  'class' => 'text-end', 'width' => '17%', 'field' => 'credit'];
            $columns[] = ['label' => 'Closing', 'class' => 'text-end', 'width' => '17%', 'field' => 'closing'];
        } else {
            $columns[] = ['label' => 'Debit',  'class' => 'text-end', 'width' => '30%', 'field' => 'debit'];
            $columns[] = ['label' => 'Credit', 'class' => 'text-end', 'width' => '30%', 'field' => 'credit'];
        }

        return ['columns' => $columns];
    }

    public function prepareOpeningTrialBalancePrintData($company, array $filters): array
    {
        $trialBalanceData = $this->getOpeningTrialBalance(company_id(), $company, financial_year_id(), $filters);

        $tableConfig = [
            'columns' => [
                ['label' => 'Sr No.',             'class' => 'text-start', 'width' => '12%'],
                ['label' => 'Account Group Name', 'class' => 'text-start', 'width' => '32%'],
                ['label' => 'Debit',              'class' => 'text-end',   'width' => '10%'],
                ['label' => 'Credit',             'class' => 'text-end',   'width' => '10%'],
            ],
        ];

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'portrait',
        ];
    }

    public function prepareOpeningTrialBalanceExportFormat($company, array $filters, string $format): array
    {
        $trialBalanceData = $this->getOpeningTrialBalance(company_id(), $company, financial_year_id(), $filters);

        $headings = ['Sr No.', 'Account Group Name', 'Debit', 'Credit'];
        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => [
            $key + 1,
            $item['account_group_name'],
            $item['debit'],
            $item['credit'],
        ]);

        $fileName = $this->storeExcelFile($company, 'opening_trial_balance', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title'    => 'Opening Trial Balance Report',
                'filters'         => $filters,
                'totalDebit'      => $trialBalanceData['grand_total']['total_debit']  ?? 0,
                'totalCredit'     => $trialBalanceData['grand_total']['total_credit'] ?? 0,
                'differenceDebit' => $trialBalanceData['difference']['debit']         ?? 0,
                'differenceCredit' => $trialBalanceData['difference']['credit']        ?? 0,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    /**
     * Print data for the main Trial Balance page's Balance Only drill-down.
     * Sibling to prepareGroupWiseTrialBalancePrintData() — that one is shared with the
     * Opening Trial Balance page's drill-down, which always wants is_opening-only balances.
     */
    public function getAccountWiseOpeningTrialBalance(int $companyId, object $company, int $financialYearId, array $filters = []): array
    {
        $systemAccountId = Account::where('company_id', $companyId)->where('code', 35000)->value('id');
        $accountGroups   = AccountGroup::where('company_id', $companyId)->pluck('name', 'id')->toArray();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_system', false)
            ->select('id', 'name', 'account_group_id')
            ->get()
            ->keyBy('id');

        $openingBalances = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.is_opening', true)
            ->whereNull('v.deleted_at')
            ->where('vt.account_id', '!=', $systemAccountId)
            ->groupBy('vt.account_id')
            ->select('vt.account_id', DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance'))
            ->get();

        $rows        = [];
        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($openingBalances as $item) {
            $account = $accounts[$item->account_id] ?? null;
            if (!$account) continue;

            $balance = (float) $item->balance;
            if ($balance == 0) continue;

            $debit  = $balance > 0 ? $balance : 0;
            $credit = $balance < 0 ? abs($balance) : 0;

            $rows[] = [
                'account_id'         => $account->id,
                'account_name'       => Str::limit($account->name,35),
                'account_group_name' => $accountGroups[$account->account_group_id] ?? 'Unknown',
                'debit'              => round($debit, 2),
                'credit'             => round($credit, 2),
                'balance'            => round($balance, 2),
            ];

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        usort($rows, fn($a, $b) => strcmp($a['account_name'], $b['account_name']));

        $totalDebit       = round($totalDebit, 2);
        $totalCredit      = round($totalCredit, 2);
        $differenceAmount = round(abs($totalDebit - $totalCredit), 2);

        return [
            'data'        => $rows,
            'last_page'   => 1,
            'grand_total' => ['total_debit' => $totalDebit, 'total_credit' => $totalCredit],
            'difference'  => [
                'debit'  => $totalDebit < $totalCredit ? $differenceAmount : 0,
                'credit' => $totalDebit > $totalCredit ? $differenceAmount : 0,
            ],
        ];
    }

    public function prepareAccountWiseOpeningTrialBalancePrintData($company, array $filters): array
    {
        $trialBalanceData = $this->getAccountWiseOpeningTrialBalance(company_id(), $company, financial_year_id(), $filters);

        $tableConfig = [
            'columns' => [
                ['label' => 'Sr No.',        'class' => 'text-start', 'width' => '8%'],
                ['label' => 'Account Name',  'class' => 'text-start', 'width' => '40%', 'field' => 'account_name'],
                ['label' => 'Account Group', 'class' => 'text-start', 'width' => '18%', 'field' => 'account_group_name'],
                ['label' => 'Debit',         'class' => 'text-end',   'width' => '17%', 'field' => 'debit'],
                ['label' => 'Credit',        'class' => 'text-end',   'width' => '17%', 'field' => 'credit'],
            ],
        ];

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'portrait',
        ];
    }

    public function prepareAccountWiseOpeningTrialBalanceExportFormat($company, array $filters, string $format): array
    {
        $trialBalanceData = $this->getAccountWiseOpeningTrialBalance(company_id(), $company, financial_year_id(), $filters);

        $headings = ['Sr No.', 'Account Name', 'Account Group', 'Debit', 'Credit'];
        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => [
            $key + 1,
            $item['account_name'],
            $item['account_group_name'],
            $item['debit'],
            $item['credit'],
        ]);

        $fileName = $this->storeExcelFile($company, 'opening_trial_balance_account_wise', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title'     => 'Opening Trial Balance — Account Wise',
                'filters'          => $filters,
                'totalDebit'       => $trialBalanceData['grand_total']['total_debit']  ?? 0,
                'totalCredit'      => $trialBalanceData['grand_total']['total_credit'] ?? 0,
                'differenceDebit'  => $trialBalanceData['difference']['debit']         ?? 0,
                'differenceCredit' => $trialBalanceData['difference']['credit']        ?? 0,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    public function prepareGroupWiseBalancePrintData($company, array $filters, int $groupId): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalanceBalance($groupId, $filters);

        if (isset($trialBalanceData['data'])) {
            $trialBalanceData['data'] = array_map(function ($item) {
                $item['account_name'] = \Illuminate\Support\Str::limit($item['account_name'] ?? '', 45);
                return $item;
            }, $trialBalanceData['data']);
        }

        $tableConfig = [
            'columns' => [
                ['label' => 'Sr No.',       'class' => 'text-start', 'width' => '12%'],
                ['label' => 'Account Name', 'class' => 'text-start', 'width' => '32%', 'field' => 'account_name'],
                ['label' => 'Debit',        'class' => 'text-end',   'width' => '10%', 'field' => 'debit'],
                ['label' => 'Credit',       'class' => 'text-end',   'width' => '10%', 'field' => 'credit'],
            ],
        ];

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'portrait',
        ];
    }

    public function prepareGroupWiseBalanceExportFormat($company, array $filters, int $groupId, string $format): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalanceBalance($groupId, $filters);

        $headings = ['Sr No.', 'Account Name', 'Debit', 'Credit'];
        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => [
            $key + 1,
            $item['account_name'],
            $item['debit'],
            $item['credit'],
        ]);

        $grandTotal = $trialBalanceData['grand_total'] ?? [];
        $totalsRow  = [null, null, $grandTotal['total_debit'] ?? 0, $grandTotal['total_credit'] ?? 0];

        $fileName = $this->storeExcelFile($company, 'group_wise_trial_balance', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title' => 'Group Wise Trial Balance Report',
                'filters'      => $filters,
                'totalDebit'   => $grandTotal['total_debit']  ?? 0,
                'totalCredit'  => $grandTotal['total_credit'] ?? 0,
                'totalsRow'    => $totalsRow,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    public function prepareGroupWiseTrialBalancePrintData($company, array $filters, int $groupId): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalance($groupId, $company, $filters);

        if (isset($trialBalanceData['data'])) {
            $trialBalanceData['data'] = array_map(function ($item) {
                $item['account_name'] = \Illuminate\Support\Str::limit($item['account_name'] ?? '', 45);
                return $item;
            }, $trialBalanceData['data']);
        }

        $tableConfig = [
            'columns' => [
                ['label' => 'Sr No.',       'class' => 'text-start', 'width' => '12%'],
                ['label' => 'Account Name', 'class' => 'text-start', 'width' => '32%'],
                ['label' => 'Debit',        'class' => 'text-end',   'width' => '10%'],
                ['label' => 'Credit',       'class' => 'text-end',   'width' => '10%'],
            ],
        ];

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'portrait',
        ];
    }

    public function prepareGroupWiseTrialBalanceExportFormat($company, array $filters, int $groupId, string $format): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalance($groupId, $company, $filters);

        $headings = ['Sr No.', 'Account Name', 'Debit', 'Credit'];
        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => [
            $key + 1,
            $item['account_name'],
            $item['debit'],
            $item['credit'],
        ]);
        

        $fileName = $this->storeExcelFile($company, 'group_wise_trial_balance', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title' => 'Group Wise Trial Balance Report',
                'filters'      => $filters,
                'totalDebit'   => $trialBalanceData['grand_total']['total_debit']  ?? 0,
                'totalCredit'  => $trialBalanceData['grand_total']['total_credit'] ?? 0,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    public function prepareGroupWiseDetailPrintData($company, array $filters, int $groupId): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalanceDetail($groupId, $filters);

        if (isset($trialBalanceData['data'])) {
            $trialBalanceData['data'] = array_map(function ($item) {
                $item['account_name'] = \Illuminate\Support\Str::limit($item['account_name'] ?? '', 30);
                return $item;
            }, $trialBalanceData['data']);
        }

        $tableConfig = [
            'columns' => [
                ['label' => 'Sr No.',       'class' => 'text-start', 'width' => '5%'],
                ['label' => 'Account Name', 'class' => 'text-start', 'width' => '28%', 'field' => 'account_name'],
                ['label' => 'Opening',      'class' => 'text-end',   'width' => '15%', 'field' => 'opening'],
                ['label' => 'Debit',        'class' => 'text-end',   'width' => '15%', 'field' => 'debit'],
                ['label' => 'Credit',       'class' => 'text-end',   'width' => '15%', 'field' => 'credit'],
                ['label' => 'Closing',      'class' => 'text-end',   'width' => '15%', 'field' => 'closing'],
            ],
        ];

        return [
            'company'          => $company,
            'filters'          => $filters,
            'currentYear'      => $company->currentFinancialYear,
            'trialBalanceData' => $trialBalanceData,
            'tableConfig'      => $tableConfig,
            'orientation'      => 'landscape',
        ];
    }

    public function prepareGroupWiseDetailExportFormat($company, array $filters, int $groupId, string $format): array
    {
        $trialBalanceData = $this->getGroupWiseTrialBalanceDetail($groupId, $filters);

        $headings = ['Sr No.', 'Account Name', 'Opening', 'Debit', 'Credit', 'Closing'];
        $rows = collect($trialBalanceData['data'])->map(fn($item, $key) => [
            $key + 1,
            $item['account_name'],
            $item['opening'],
            $item['debit'],
            $item['credit'],
            $item['closing'],
        ]);

        $grandTotal = $trialBalanceData['grand_total'] ?? [];
        $totalsRow  = [
            null, 
            null, 
            $grandTotal['total_opening'] ?? 0, 
            $grandTotal['total_debit'] ?? 0, 
            $grandTotal['total_credit'] ?? 0, 
            $grandTotal['total_closing'] ?? 0
        ];

        $fileName = $this->storeExcelFile($company, 'group_wise_trial_balance_detail', $format, new TrialBalanceExport(
            $company,
            'company.pages.trial-balance.export',
            $rows,
            $headings,
            [
                'report_title' => 'Group Wise Trial Balance Detail',
                'filters'      => $filters,
                'totalDebit'   => $grandTotal['total_debit']  ?? 0,
                'totalCredit'  => $grandTotal['total_credit'] ?? 0,
                'totalsRow'    => $totalsRow,
            ]
        ), $format);

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    public function getAccountLedger(int $accountId, string $fromDate, string $toDate): array
    {
        $filter = [
            'account_id'  => $accountId,
            'start_date'  => $fromDate,
            'end_date'    => $toDate,
            'narration'   => false,
            'voucher_ids' => [],
            'ledger_by'   => 'single',
        ];

        $data        = $this->ledgerRepo->getLedgerData(company_id(), financial_year_id(), $filter);
        $accountName = \App\Models\Account::where('id', $accountId)->value('name') ?? '';

        return [
            'account_id'      => $accountId,
            'account_name'    => $accountName,
            'from_date'       => $fromDate,
            'to_date'         => $toDate,
            'opening_balance' => $data['opening_balance'],
            'transactions'    => $data['transactions']->map(fn($t) => [
                'voucher_date'         => $t->voucher_date,
                'against_account_name' => $t->against_account_name ?? '',
                'voucher_type'         => $t->voucher_type ?? '',
                'voucher_serial'       => $t->voucher_serial ?? '',
                'reference_number'     => $t->reference_number ?? '',
                'debit'                => $t->debit,
                'credit'               => $t->credit,
                'running_balance'      => $t->running_balance,
            ])->values()->toArray(),
            'totals'          => $data['totals'],
            'closing_balance' => $data['closing_balance'],
        ];
    }

    public function getAccountMonthWiseSummary(int $accountId, string $fromDate, string $toDate): array
    {
        return $this->ledgerRepo->getMonthWiseSummary(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            accountId: $accountId,
            fromDate: $fromDate,
            toDate: $toDate
        );
    }

    public function prepareLedgerPrintData($company, array $filters): array
    {
        return $this->ledgerRepo->prepareLedgerPrintData($company, $filters);
    }

    public function prepareLedgerExportFormat($company, array $filters, string $format): array
    {
        return $this->ledgerRepo->prepareLedgerExportFormat($company, $filters, $format);
    }

    public function prepareMonthWisePrintData($company, int $accountId, string $fromDate, string $toDate): array
    {
        $data = $this->getAccountMonthWiseSummary($accountId, $fromDate, $toDate);

        return [
            'company'         => $company,
            'account_name'    => $data['account_name'] ?? '',
            'from_date'       => Carbon::parse($fromDate)->format('d-M-Y'),
            'to_date'         => Carbon::parse($toDate)->format('d-M-Y'),
            'months'          => $data['months'] ?? [],
            'grand_total'     => $data['grand_total'] ?? ['total_debit' => 0, 'total_credit' => 0],
            'opening_balance' => $data['opening_balance'] ?? 0,
            'closing_balance' => $data['closing_balance'] ?? 0,
        ];
    }

    public function prepareMonthWiseExportFormat($company, int $accountId, string $fromDate, string $toDate, string $format): array
    {
        $data     = $this->getAccountMonthWiseSummary($accountId, $fromDate, $toDate);
        $headings = ['Month', 'Period', 'Opening (₹)', 'Debit (₹)', 'Credit (₹)', 'Closing (₹)'];
        $rows     = collect($data['months'] ?? [])->map(fn($m) => [
            $m['month_label'],
            $m['from_date'] . ' — ' . $m['to_date'],
            (float) $m['opening'],
            (float) $m['debit'],
            (float) $m['credit'],
            (float) $m['closing'],
        ]);

        $fileName = $this->storeExcelFile(
            $company,
            'month_wise_summary',
            $format,
            new TrialBalanceExport(
                $company,
                'company.pages.trial-balance.month-wise-export',
                $rows,
                $headings,
                [
                    'report_title'   => 'Month Wise Account Summary',
                    'account'        => $data['account_name'] ?? '',
                    'from_date'      => Carbon::parse($fromDate)->format('d-M-Y'),
                    'to_date'        => Carbon::parse($toDate)->format('d-M-Y'),
                    'openingBalance' => $data['opening_balance'] ?? 0,
                    'closingBalance' => $data['closing_balance'] ?? 0,
                    'totalDebit'     => $data['grand_total']['total_debit']  ?? 0,
                    'totalCredit'    => $data['grand_total']['total_credit'] ?? 0,
                    'filters'        => ['start_date' => $fromDate, 'end_date' => $toDate],
                ]
            ),
            $format
        );

        return [
            'format'    => $format,
            'file_url'  => asset("storage/master_reports/{$fileName}"),
            'file_name' => $fileName,
        ];
    }

    private function storeExcelFile($company, string $slug, string $format, $export, string $storeFormat): string
    {
        $directory     = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companySlug = Str::slug($company->print_name, '_');
        $fileName    = "{$companySlug}_{$slug}_" . now()->format('d_m_Y_His') . ".{$format}";

        Excel::store(
            $export,
            "{$directory}/{$fileName}",
            'public',
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );

        return $fileName;
    }
}

<?php

namespace App\Services;

use App\Exports\LedgerExport;
use App\Models\Account;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LedgerReportService
{
    const LEDGER_BY_SINGLE = 'single';
    const LEDGER_BY_FULL   = 'full';
    const LEDGER_BY_OTHER  = 'other';

    /**
     * ===============================
     * Main Entry Point
     * ===============================
     */
    public function getLedgerData(
        int $companyId,
        int $financialYearId,
        array $filter
    ): array {

        $filter = $this->normalizeFilter($filter);

        // ✅ Dynamic Opening Balance (Busy Style)
        $openingBalance = $this->getOpeningBalance(
            $companyId,
            $financialYearId,
            $filter
        );

        // Transactions in selected period
        $transactions = $this->getTransactions(
            $companyId,
            $financialYearId,
            $filter
        );

        // Running balance only for SINGLE ledger
        // if ($filter['ledger_by'] === 'single') {
        $transactions = $this->applyRunningBalance(
            $transactions,
            $openingBalance,
            $filter['ledger_by'],
            $filter['account_id'],
            $filter['narration']
        );
        // }

        return [
            'opening_balance' => $openingBalance,
            'transactions'    => $transactions,
            'totals'          => $this->calculateTotals($transactions),
            'closing_balance' => $this->getClosingBalance(
                $openingBalance,
                $transactions,
                $filter['ledger_by']
            ),
        ];
    }

    protected function calculateTotals(Collection $rows): array
    {
        return [
            'total_debit'  => $rows->whereNotNull('debit')->sum('debit'),
            'total_credit' => $rows->whereNotNull('credit')->sum('credit'),
        ];
    }

    /**
     * ===============================
     * Normalize Filters
     * ===============================
     */
    protected function normalizeFilter(array $filter): array
    {
        // Accept voucher_type_id (from the JS filter) and normalise to an array
        // for the whereIn clause used in getTransactions().
        $voucherTypeId = $filter['voucher_type_id'] ?? null;
        $voucherIds    = [];
        if ($voucherTypeId) {
            $voucherIds = [(int) $voucherTypeId];
        } elseif (!empty($filter['voucher_ids'])) {
            $voucherIds = (array) $filter['voucher_ids'];
        }

        return [
            'account_id'  => $filter['account_id'] ?? null,
            'against_account_id' => $filter['against_account_id'] ?? null,
            'start_date'  => $filter['start_date'],
            'end_date'    => $filter['end_date'],
            'narration'   => (bool)($filter['narration'] ?? false),
            'voucher_ids' => $voucherIds,
            'ledger_by'   => $filter['ledger_by'] ?? 'single',
        ];
    }

    /**
     * ===============================
     * FINAL OPENING BALANCE (Busy Logic)
     *
     * Opening =
     *   Opening Voucher (Type 17)
     * + Transactions BEFORE start_date
     * ===============================
     */
    protected function getOpeningBalance(
        int $companyId,
        int $financialYearId,
        array $filter
    ): float {

        if (!$filter['account_id']) {
            return 0;
        }

        $financialOpening = $this->getFinancialOpeningBalance(
            $companyId,
            $financialYearId,
            $filter['account_id']
        );

        $prePeriodBalance = $this->getPrePeriodBalance(
            $companyId,
            $financialYearId,
            $filter['account_id'],
            $filter['start_date'],
            $filter['against_account_id'] ?? null
        );

        return $financialOpening + $prePeriodBalance;
    }

    /**
     * ===============================
     * Financial Year Opening
     * Voucher Type = OPENING_BALANCE (17)
     * Date IGNORED
     * ===============================
     */
    protected function getFinancialOpeningBalance(
        int $companyId,
        int $financialYearId,
        int $accountId
    ): float {

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('vt.account_id', $accountId)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->selectRaw('COALESCE(SUM(vt.debit) - SUM(vt.credit), 0)')
            ->value('balance') ?? 0;
    }

    /**
     * ===============================
     * Transactions BEFORE start_date
     * (Exclude Opening Voucher)
     * ===============================
     */
    protected function getPrePeriodBalance(
        int $companyId,
        int $financialYearId,
        int $accountId,
        string $startDate,
        ?int $againstAccountId = null
    ): float {

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('vt.account_id', $accountId)
            ->when($againstAccountId, function($q) use ($againstAccountId) {
                $q->where('vt.against_account_id', $againstAccountId);
            })
            ->where('v.voucher_type_id', '!=', VoucherType::OPENING_BALANCE)
            ->where('v.voucher_date', '<', $startDate)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->selectRaw('COALESCE(SUM(vt.debit) - SUM(vt.credit), 0)')
            ->value('balance') ?? 0;
    }

    /**
     * Get Opening Balances for ALL accounts up to a certain date.
     *
     * Formula:
     *   Voucher Type 17 (Opening)
     * + Transactions before start_date (Type != 17)
     */
    public function getOpeningBalancesForAllAccounts(int $companyId, int $financialYearId, string $startDate, string $endDate): array
    {
        $balances = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->where(function ($query) use ($startDate,$endDate) {
                $query->where('v.voucher_type_id', VoucherType::OPENING_BALANCE)
                    ->orWhereBetween('v.voucher_date', [$startDate, $endDate]);
            })
            ->groupBy('vt.account_id')
            ->select(
                'vt.account_id',
                DB::raw('SUM(vt.debit) as total_debit'),
                DB::raw('SUM(vt.credit) as total_credit'),
                DB::raw('SUM(vt.debit) - SUM(vt.credit) as balance')
            )
            ->get();

        return $balances->keyBy('account_id')->toArray();
    }
    /**
     * ===============================
     * Ledger Transactions (Period)
     * ===============================
     */
    protected function getTransactions(
        int $companyId,
        int $financialYearId,
        array $filter
    ): Collection {

        // Pre-fetch the voucher IDs where the account appears in the period,
        // already restricted to the selected voucher type(s) for efficiency.
        $voucherIds = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.company_id', $companyId)
            ->where('vt.account_id', $filter['account_id'])
            ->when(!empty($filter['against_account_id']), function($q) use ($filter) {
                $q->where('vt.against_account_id', $filter['against_account_id']);
            })
            ->where('v.voucher_type_id', '!=', VoucherType::OPENING_BALANCE)
            ->when(!empty($filter['voucher_ids']), fn($q) => $q->whereIn('v.voucher_type_id', $filter['voucher_ids']))
            ->whereBetween('v.voucher_date', [$filter['start_date'], $filter['end_date']])
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->pluck('v.id')->unique()->toArray();

        if (empty($voucherIds)) {
            return collect();
        }

        $query = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'vt.account_id')
            ->leftJoin('accounts as aa', 'aa.id', '=', 'vt.against_account_id')
            ->leftJoin('voucher_types as vty', 'vty.id', '=', 'v.voucher_type_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.voucher_type_id', '!=', VoucherType::OPENING_BALANCE)
            ->whereBetween('v.voucher_date', [$filter['start_date'], $filter['end_date']])
            ->whereIn('v.id', $voucherIds)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1);

        if (!empty($filter['voucher_ids'])) {
            $query->whereIn('v.voucher_type_id', $filter['voucher_ids']);
        }

        if (!empty($filter['against_account_id']) && $filter['ledger_by'] !== 'full') {
            $query->where('vt.against_account_id', $filter['against_account_id']);
        }

        return $query
            ->orderBy('v.voucher_date')
            ->orderBy('v.id')
            ->orderBy('vt.line_no')
            ->get([
                'v.id as voucher_id',
                'v.voucher_date',
                'v.voucher_number',
                'v.voucher_serial',
                'v.reference_number',
                'v.voucher_type_id',
                'vty.name as voucher_type',

                'vt.account_id',
                'a.name as account_name',

                'vt.against_account_id',
                'aa.name as against_account_name',

                'vt.debit',
                'vt.credit',
                'vt.narration as short_narration',
                'v.narration as full_narration',
            'v.secondary_narration as cheque_narration',
            ]);
    }

    /**
     * ===============================
     * Running Balance (Single Ledger)
     * ===============================
     */
    protected function applyRunningBalance(
        Collection $rows,
        float $openingBalance,
        string $ledgerBy,
        ?int $account_id,
        bool $narration
    ): Collection {

        /*
    |--------------------------------------------------------------------------
    | SINGLE LEDGER
    |--------------------------------------------------------------------------
    */
        $balance    = $openingBalance;
        $finalRows = collect();
        $grouped   = $rows->groupBy('voucher_id');


        if ($ledgerBy === self::LEDGER_BY_SINGLE) {
            $balance = $openingBalance;
            foreach ($grouped as $voucherRows) {
                $voucherNarration = null;

                $firstRow = true;
                foreach ($voucherRows as $row) {
                    // capture narration
                    $narrationParts = [];
                    if (!empty($row->full_narration)) {
                        $narrationParts[] = $row->full_narration;
                    }
                    if (!empty($row->cheque_narration)) {
                        $narrationParts[] = $row->cheque_narration;
                    }
                    if (!empty($narrationParts)) {
                        $voucherNarration = implode("\n", $narrationParts);
                    }

                    if ($row->account_id != $account_id) {
                        continue;
                    }
                    $balance += ($row->debit - $row->credit);
                    $row->running_balance = $balance;
                    if ($firstRow) {
                        $row->running_balance = $balance;
                        $firstRow = false;
                    } else {
                        $row->running_balance = $balance;
                        $row->voucher_date = null;
                        $row->voucher_number = null;
                        $row->voucher_serial = null;
                        $row->reference_number = null;
                        $row->voucher_type = null;
                    }
                    $finalRows->push($row);
                }

                // narration row
                if (!empty($voucherNarration) && $narration) {
                    $finalRows->push((object)[
                        'voucher_id'      => null,
                        'voucher_date'    => null,
                        'voucher_number'  => null,
                        'voucher_type'    => 'Narration',
                        'account_name'    => null,
                        'against_account_name' => null,
                        'debit'           => null,
                        'credit'          => null,
                        'running_balance' => null,
                        'narration'       => $voucherNarration,
                    ]);
                }
            }
            return $finalRows;
        }

        /*
    |--------------------------------------------------------------------------
    | OTHER LEDGER
    |--------------------------------------------------------------------------
    | Balance updates ONCE per voucher
    */
        if ($ledgerBy === self::LEDGER_BY_OTHER) {

            foreach ($grouped as $voucherRows) {
                $voucherNarration = null;
                $isFirst = true;

                foreach ($voucherRows as $row) {
                    if ($row->account_id == $account_id) {
                        continue;
                    }
                    $narrationParts = [];
                    if (!empty($row->full_narration)) {
                        $narrationParts[] = $row->full_narration;
                    }
                    if (!empty($row->cheque_narration)) {
                        $narrationParts[] = $row->cheque_narration;
                    }
                    if (!empty($narrationParts)) {
                        $voucherNarration = implode("\n", $narrationParts);
                    }

                    if ($isFirst) {
                        $balance += ($row->debit - $row->credit);
                        $row->running_balance = $balance;
                        $row->against_account_name = $row->account_name;

                        $tmp = $row->debit;
                        $row->debit  = $row->credit;
                        $row->credit = $tmp;

                        $isFirst = false;
                    } else {
                        // clean header
                        $balance += ($row->debit - $row->credit);
                        $row->running_balance = $balance;

                        $tmp = $row->debit;
                        $row->debit  = $row->credit;
                        $row->credit = $tmp;

                        $row->voucher_date      = null;
                        $row->voucher_number    = null;
                        $row->voucher_serial    = null;
                        $row->reference_number  = null;
                        $row->voucher_type      = null;
                        $row->against_account_name = $row->account_name;
                    }

                    $finalRows->push($row);
                }

                if (!empty($voucherNarration) && $narration) {
                    $finalRows->push((object)[
                        'voucher_id'      => null,
                        'voucher_date'    => null,
                        'voucher_number'  => null,
                        'voucher_type'    => 'Narration',
                        'account_name'    => null,
                        'against_account_name' => null,
                        'debit'           => null,
                        'credit'          => null,
                        'running_balance' => null,
                        'narration'       => $voucherNarration,
                    ]);
                }
            }
            return $finalRows;
        }

        /*
    |--------------------------------------------------------------------------
    | FULL LEDGER
    |--------------------------------------------------------------------------
    | Current ledger row first, others below, narration last
    */
        foreach ($grouped as $voucherRows) {

            $voucherNarration = null;
            $currentLedgerRow = null;
            $otherLedgerRows  = collect();
            $voucherAmount    = 0;
            $isFirst = true;

            foreach ($voucherRows as $row) {

                // capture narration
                $narrationParts = [];
                if (!empty($row->full_narration)) {
                    $narrationParts[] = $row->full_narration;
                }
                if (!empty($row->cheque_narration)) {
                    $narrationParts[] = $row->cheque_narration;
                }
                if (!empty($narrationParts)) {
                    $voucherNarration = implode("\n", $narrationParts);
                }

                // format account name with Dr / Cr
                $accountName = $row->account_name;

                if ($row->debit > 0) {
                    $accountName .= ' : ' . formatIndianNumber($row->debit) . ' Dr';
                }

                if ($row->credit > 0) {
                    $accountName .= ' : ' . formatIndianNumber($row->credit) . ' Cr';
                }
                // dd($accountName);

                $row->against_account_name = $accountName;

                // CURRENT LEDGER ROW
                if ($row->account_id == $account_id && $isFirst) {
                    $voucherAmount = ($row->debit - $row->credit);
                    $currentLedgerRow = $row;
                    $isFirst = false;
                    $totalCr = 0;
                    $totalDr = 0;
                    foreach ($voucherRows as $row) {
                        if ($row->account_id == $account_id) {
                            $totalCr += $row->credit;
                            $totalDr += $row->debit;
                        }
                    }
                    $currentLedgerRow->debit = $totalDr;
                    $currentLedgerRow->credit = $totalCr;
                    $voucherAmount = $totalDr - $totalCr;
                } else {
                    $row->voucher_date     = null;
                    $row->voucher_number   = null;
                    $row->voucher_serial   = null;
                    $row->voucher_type     = null;
                    $row->running_balance  = null;
                    $row->debit            = null;
                    $row->credit           = null;

                    $otherLedgerRows->push($row);
                }
            }

            // update balance ONCE per voucher
            $balance += $voucherAmount;

            // push current ledger row first
            if ($currentLedgerRow) {
                $currentLedgerRow->running_balance = $balance;
                $finalRows->push($currentLedgerRow);
            }

            // push other ledger rows
            foreach ($otherLedgerRows as $row) {
                $finalRows->push($row);
            }

            // narration row
            if (!empty($voucherNarration) && $narration) {
                $finalRows->push((object)[
                    'voucher_id'      => null,
                    'voucher_date'    => null,
                    'voucher_number'  => null,
                    'voucher_type'    => 'Narration',
                    'account_name'    => null,
                    'against_account_name' => null,
                    'debit'           => null,
                    'credit'          => null,
                    'running_balance' => null,
                    'narration'       => $voucherNarration,
                ]);
            }
        }

        return $finalRows;
    }
    // }


    /**
     * ===============================
     * Closing Balance
     * ===============================
     */
    protected function getClosingBalance(
        float $openingBalance,
        Collection $transactions,
        string $ledgerBy,
    ): float {

        $lastBalanceRow = $transactions
            ->whereNotNull('running_balance')
            ->last();

        return $lastBalanceRow->running_balance ?? $openingBalance;
    }
    /**
     * Month-wise summary for a single account over a date range.
     * Partial first/last months are handled naturally: the DB query is bounded by
     * [$fromDate, $toDate], so only actual transactions within those bounds are summed,
     * regardless of whether the boundary falls mid-month.
     */
    public function getMonthWiseSummary(
        int $companyId,
        int $financialYearId,
        int $accountId,
        string $fromDate,
        string $toDate
    ): array {
        $opening = $this->getOpeningBalance($companyId, $financialYearId, [
            'account_id' => $accountId,
            'start_date' => $fromDate,
            'end_date'   => $toDate,
        ]);

        $monthTotals = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('vt.account_id', $accountId)
            ->where('v.voucher_type_id', '!=', VoucherType::OPENING_BALANCE)
            ->whereBetween('v.voucher_date', [$fromDate, $toDate])
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1)
            ->select(
                DB::raw("DATE_FORMAT(v.voucher_date, '%Y-%m') as month_key"),
                DB::raw('SUM(vt.debit) as debit'),
                DB::raw('SUM(vt.credit) as credit')
            )
            ->groupBy(DB::raw("DATE_FORMAT(v.voucher_date, '%Y-%m')"))
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $fromCarbon = Carbon::parse($fromDate);
        $toCarbon   = Carbon::parse($toDate);
        $cursor     = $fromCarbon->copy()->startOfMonth();
        $months     = [];
        $balance    = $opening;

        while ($cursor->lte($toCarbon)) {
            $monthKey   = $cursor->format('Y-m');
            $monthEnd   = $cursor->copy()->endOfMonth();
            $isPartial  = $monthEnd->gt($toCarbon);
            $displayEnd = $isPartial ? $toCarbon : $monthEnd;

            $isFirstMonth = $cursor->format('Y-m') === $fromCarbon->format('Y-m');
            $displayStart = $isFirstMonth ? $fromCarbon : $cursor;

            $monthData = $monthTotals->get($monthKey);
            $debit     = $monthData ? round((float) $monthData->debit,  2) : 0.0;
            $credit    = $monthData ? round((float) $monthData->credit, 2) : 0.0;
            $closing   = round($balance + $debit - $credit, 2);

            $label = $cursor->format('M Y');
            if ($isFirstMonth && $fromCarbon->day > 1) {
                $label .= ' (from ' . $fromCarbon->format('d') . ')';
            }
            if ($isPartial) {
                $label .= ' (upto ' . $displayEnd->format('d') . ')';
            }

            $months[] = [
                'month_label'   => $label,
                'month_key'     => $monthKey,
                'from_date'     => $displayStart->format('d-M-Y'),
                'to_date'       => $displayEnd->format('d-M-Y'),
                'from_date_raw' => $displayStart->format('Y-m-d'),
                'to_date_raw'   => $displayEnd->format('Y-m-d'),
                'opening'       => round($balance, 2),
                'debit'         => $debit,
                'credit'        => $credit,
                'closing'       => $closing,
            ];

            $balance = $closing;
            $cursor->addMonth()->startOfMonth();
        }

        $accountName = Account::where('id', $accountId)->value('name') ?? '';

        return [
            'account_id'      => $accountId,
            'account_name'    => $accountName,
            'from_date'       => $fromDate,
            'to_date'         => $toDate,
            'opening_balance' => round($opening, 2),
            'months'          => $months,
            'grand_total'     => [
                'total_debit'  => round(array_sum(array_column($months, 'debit')),  2),
                'total_credit' => round(array_sum(array_column($months, 'credit')), 2),
            ],
            'closing_balance' => round($balance, 2),
        ];
    }

    public function prepareLedgerPrintData($company,$filters){
        $filters['size'] = 10000; // Get all for export

        $ledgerData = $this->getLedgerData(company_id(), financial_year_id(),$filters ?? []);
        // dd($ledgerData);
        $ledgerPrintData = [
            'data'      => $ledgerData['transactions'],
            'debit'     => $ledgerData['totals']['total_debit'] ?? 0,
            'credit'    => $ledgerData['totals']['total_credit'] ?? 0,
            'opening_balance' => $ledgerData['opening_balance'] ?? 0,
            'closing_balance' => $ledgerData['closing_balance'] ?? 0,
        ];
        // dd($ledgerPrintData);
        $account = null;
        $ledgerAccount = '';
        if (!empty($filters['account_id'])) {
            $account = Account::find($filters['account_id']);
            $ledgerAccount= $account->name . ' (' . $account->city . ')';
        }
        $currentYear = $company->currentFinancialYear;
        // dd($account);
        $tableConfig = [
            'columns' => [
                ["label" => "Date", "class" => "text-start", "width"=>"12%"],
                ["label" => "Particulars", "class" => "text-start", "width"=>"32%"],
                ["label" => "Voucher Type", "class" => "text-start", "width"=>"10%"],
                ["label" => "Voucher No.", "class" => "text-start", "width"=>"10%"],
                ["label" => "Debit", "class" => "text-end", "width"=>"10%"],
                ["label" => "Credit", "class" => "text-end", "width"=>"10%"],
                ["label" => "Balance", "class" => "text-end", "width"=>"10%"],
            ],
        ];

        $data = [
            'company' => $company,
            'account' => $ledgerAccount,
            'filters' => $filters,
            'currentYear' => $currentYear,
            'ledgerReport'=> $ledgerPrintData,
            'tableConfig' => $tableConfig,
            'orientation' => 'portrait',
        ];
        return $data;
    }

    public function prepareLedgerExportFormat($company, $filters, string $format){

            $filters['size'] = 10000; // Get all for export

            $ledgerData = $this->getLedgerData(company_id(), financial_year_id(),$filters);
            // dd($ledgerData);
            $ledgerPrintData = [
                'data'      => $ledgerData['transactions'],
                'debit'     => $ledgerData['totals']['total_debit'] ?? 0,
                'credit'    => $ledgerData['totals']['total_credit'] ?? 0,
                'opening_balance' => $ledgerData['opening_balance'] ?? 0,
                'closing_balance' => $ledgerData['closing_balance'] ?? 0,
            ];
            $account = null;
            $ledgerAccount = '';
            if (!empty($filters['account_id'])) {
                $account = Account::find($filters['account_id']);
                $ledgerAccount= $account->name . ' (' . $account->city . ')';
            }
            $headings = [
                'Date',
                'Particulars',
                'Voucher Type',
                'Voucher No.',
                'Debit',
                'Credit',
                'Balance',
            ];
            // dd($ledgerPrintData['data']);
            $rows = collect($ledgerData['transactions'])->map(function ($ledger) {
            $particulars = $ledger->against_account_name ?? '';
            if (!empty($ledger->narration)) {
                $particulars .= "\n( " . $ledger->narration . " )";
            }
            if (!empty($ledger->short_narration)) {
                $particulars .= "\n( " . $ledger->short_narration . " )";
            }
                return [
                    !empty($ledger->voucher_date) ? format_date($ledger->voucher_date) : '',
                trim($particulars),
                $ledger->voucher_type ?? '',
                    !empty($ledger->voucher_serial) ? trim($ledger->voucher_serial . '/' . ($ledger->reference_number ?? ''), '/') : '',
                $ledger->debit ?? '',
                $ledger->credit ?? '',
                $ledger->running_balance ?? '',
                ];
            });
            // dd($rows);
            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_ledger_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new LedgerExport(
                    $company,
                    'company.pages.ledger.export',
                    $rows,
                    $headings,
                    [
                        'report_title' => 'Ledger Report',
                        'openingStock' => ($ledgerPrintData['opening_balance'] ?? 0),
                        'closingStock' => ($ledgerPrintData['closing_balance'] ?? 0),
                        'totalDebit' => ($ledgerPrintData['debit'] ?? 0),
                        'totalCredit' => ($ledgerPrintData['credit'] ?? 0),
                        'account' => $ledgerAccount ?? '',
                        'filters'=> $filters,
                    ]
                ),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );
            $result = [
                'format' => $format,
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ];
            return $result;
    }
}

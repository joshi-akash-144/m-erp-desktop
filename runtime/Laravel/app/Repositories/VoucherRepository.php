<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherRepository extends BaseRepository
{
    public function __construct(Voucher $voucher)
    {
        parent::__construct($voucher);
    }

    public function getNextVoucherSerial(int $voucherTypeId, int $companyId, int $financialYearId): int
    {
        $lastVoucher = $this->model
            ->where('voucher_type_id', $voucherTypeId)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('voucher_serial')
            ->withTrashed()
            ->first();

        return $lastVoucher ? $lastVoucher->voucher_serial : 0;
    }

    public function hasOpeningBalanceEntry(int $accountId): bool
    {
        return VoucherTransaction::query()
            ->where('account_id', $accountId)
            ->whereHas(
                'voucher',
                fn($q) =>
                $q->where('voucher_type_id', VoucherType::OPENING_BALANCE)
            )
            ->exists();
    }


    public function getOpeningBalanceRow(
        int $companyId,
        int $financialYearId,
        int $accountId
    ): ?VoucherTransaction {

        return VoucherTransaction::query()
            ->where('account_id', $accountId)
            ->whereHas('voucher', function ($q) use ($companyId, $financialYearId) {
                $q->where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    ->where('voucher_type_id', VoucherType::OPENING_BALANCE)
                    ->where('is_opening', true);
            })
            ->orderBy('id') // safety
            ->first();
    }

    public function getLedgerData() {}

    /**
     * Per-account NON-opening movement summary used by the Trial Balance report.
     * - opening: signed net (debit - credit) of non-opening transactions before $fromDate
     * - debit/credit: gross sums of non-opening transactions within [$fromDate, $toDate]
     * Excludes the system "opening contra" account (code 35000), same as getOpeningTrialBalance.
     *
     * Always excludes is_opening=true vouchers — the FY brought-forward balance is added back
     * separately via getOpeningVoucherBalances(), regardless of mode. Computing it that way (rather
     * than folding is_opening rows into this date-based "opening" sum) avoids an edge case: when
     * $fromDate equals the FY start date exactly, an opening voucher dated on that same day would
     * fail the "< fromDate" comparison and get miscounted as period movement instead of opening.
     */
    public function getAccountMovements(int $companyId, int $financialYearId, string $fromDate, string $toDate)
    {
        $from = self::toYmd($fromDate);
        $to   = self::toYmd($toDate);

        $systemAccountIdForOpening = Account::where('company_id', $companyId)->where('code', 35000)->value('id');

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.is_opening', false)
            ->whereNull('v.deleted_at')
            ->when($systemAccountIdForOpening, fn($q) => $q->where('vt.account_id', '!=', $systemAccountIdForOpening))
            ->groupBy('vt.account_id')
            ->selectRaw(
                'vt.account_id,
                 SUM(CASE WHEN v.voucher_date < ? THEN vt.debit - vt.credit ELSE 0 END) as opening,
                 SUM(CASE WHEN v.voucher_date BETWEEN ? AND ? THEN vt.debit ELSE 0 END) as debit,
                 SUM(CASE WHEN v.voucher_date BETWEEN ? AND ? THEN vt.credit ELSE 0 END) as credit',
                [$from, $from, $to, $from, $to]
            )
            ->get();
    }

    /**
     * Per-account FY opening balance (signed, debit - credit) from is_opening=true vouchers only.
     * Used by "Balance Only" mode's Opening column — the true brought-forward balance, independent
     * of whatever as-on-date is selected (same balances as the separate Opening Trial Balance page).
     */
    public function getOpeningVoucherBalances(int $companyId, int $financialYearId)
    {
        $systemAccountIdForOpening = Account::where('company_id', $companyId)->where('code', 35000)->value('id');

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.is_opening', true)
            ->whereNull('v.deleted_at')
            ->when($systemAccountIdForOpening, fn($q) => $q->where('vt.account_id', '!=', $systemAccountIdForOpening))
            ->groupBy('vt.account_id')
            ->selectRaw('vt.account_id, SUM(vt.debit) - SUM(vt.credit) as balance')
            ->get()
            ->pluck('balance', 'account_id');
    }

    public function checkUUIDExists(string $uuid): bool
    {
        return $this->model->where('uuid', $uuid)->exists();
    }

    // Accepts Y-m-d (from JS) or d-m-Y (from format_date helper) and returns Y-m-d.
    private static function toYmd(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
    }
}

<?php

namespace App\Services;

use App\Repositories\AccountRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class AccountBalanceService
{
    protected VoucherService $voucherService;
    protected AccountRepository $accountRepo;


    public function __construct(VoucherService $voucherService, AccountRepository $accountRepo)
    {
        $this->voucherService = $voucherService;
        $this->accountRepo = $accountRepo;
    }

    public function getAccountsBalance($companyId, $financialYearId, ?array $partyTypes)
    {
        $accountIds = $this->accountRepo->getAccountIds($companyId, $partyTypes);
        return $this->voucherService->getAccountsClosingBalance($companyId, $financialYearId, $accountIds);
    }

    public function getAccountClosingBalance($companyId, $financialYearId, $accountId)
    {
        $accountIds = is_array($accountId) ? $accountId : [$accountId];
        $data = $this->voucherService->getAccountsClosingBalance($companyId, $financialYearId, $accountIds);
        $balance = 0;
        if ($data->count() > 0) {
            foreach ($data as $key => $value) {
                $balance = $value->dr - $value->cr;
            }
        }
        return ['balance' => $balance ? round($balance, 2) : 0];
    }

    //     // ✔ Opening Balance
    // public static function getOpeningBalance($ledgerId, $companyId = null)
    // {
    //     return DB::table('opening_balances')
    //         ->where('ledger_id', $ledgerId)
    //         ->when($companyId, fn($q) => $q->where('company_id', $companyId))
    //         ->value('opening_balance');
    // }

    // ✔ Closing Balance
    public function getClosingBalance(int $companyId, int $financialYearId, ?array $accountIds = null)
    {
        $rows = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->when($accountIds, fn($q) => $q->whereIn('vt.account_id', $accountIds))
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->whereNull('v.deleted_at')
            ->select(
                'vt.account_id',
                DB::raw('SUM(vt.debit) as dr'),
                DB::raw('SUM(vt.credit) as cr')
            )
            ->groupBy('vt.account_id')
            ->get();

        // Convert into a clean associative array
        return $rows->mapWithKeys(function ($row) {
            $debit = (float) $row->dr;
            $credit = (float) $row->cr;

            return [
                $row->account_id => [
                    'dr' => $debit,
                    'cr' => $credit,
                    'closing' => $debit - $credit,
                ]
            ];
        });
    }

    // // ✔ Combined Summary (optional)
    // public static function getLedgerSummary($ledgerId, $companyId = null)
    // {
    //     $opening = self::getOpeningBalance($ledgerId, $companyId);
    //     $closing = self::getClosingBalance($ledgerId, $companyId);

    //     return [
    //         'opening' => $opening,
    //         'closing' => $closing,
    //     ];
    // }

}

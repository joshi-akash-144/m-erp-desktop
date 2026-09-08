<?php

namespace App\Services;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use App\Repositories\AccountRepository;
use App\Repositories\VoucherRepository;
use Exception;
use Illuminate\Support\Str;

class OpeningBalanceService
{
    protected AccountRepository $accountRepo;
    protected VoucherRepository $voucherRepo;
    protected VoucherService $voucherService;



    public function __construct(AccountRepository $accountRepo, VoucherRepository $voucherRepo, VoucherService $voucherService)
    {
        $this->accountRepo = $accountRepo;
        $this->voucherRepo = $voucherRepo;
        $this->voucherService = $voucherService;
    }



    public function createOpeningBalanceEntry(int $accountId, array $data)
    {
        // Skip if no opening balance
        if ($data['opening_balance'] == 0) {
            return null;
        }

        // Skip if already created
        if ($this->hasOpeningBalanceEntry($accountId)) {
            return null;
        }

        // Get Opening Balance Difference System Account
        $obDiffAccount = $this->getOpeningBalanceDiffAccount($data['company_id']);

        // Get or Create Opening Balance Voucher Type
        $voucherType = VoucherType::OPENING_BALANCE;
        $companyId = $data['company_id'];

        $financialYearId = $data['financial_year_id'];
        $voucherDate = $data['financial_year_start'];

        $voucherInfo = $this->voucherService->getNextVoucherNumber($voucherType, $companyId, $financialYearId);

        $master =  [
            'uuid'             => $data['uuid'],
            'company_id'       => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_date'     => $voucherDate,
            'voucher_type_id'  => $voucherType,
            'reference_id'     => null,
            'reference_type'   => 'opening_balance',
            'reference_number' => null,
            'voucher_serial'   => $voucherInfo->serial,
            'voucher_number'   => $voucherInfo->voucher_number,
            'narration'       => 'Opening Balance as on ' . $voucherDate,
            'is_opening'       => true,
        ];

        $line = $this->createDualEntry($data, $obDiffAccount);
        $this->voucherService->createVoucher($master,$line);
    }
    /**
     * Create dual entry for opening balance
     */
    protected function createDualEntry(array $data, $obDiffAccount): array
    {
        $lines = [];

        $amount = (float) $data['opening_balance'];

        if ($amount <= 0) {
            return $lines; // no entry if zero
        }

        $balanceType = strtolower($data['opening_type']); // d / c

        // Determine accounts
        $mainAccountId   = $data['account_id'];
        $offsetAccountId = $obDiffAccount->id;

        if ($balanceType === 'd') {

            // Main account DR
            $lines[] = [
                'account_id' => $mainAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => 'Opening Balance',
                'line_no'    => 1,
            ];

            // Offset account CR
            $lines[] = [
                'account_id' => $offsetAccountId,
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => 'Opening Balance Contra',
                'line_no'    => 2,
            ];
        } elseif ($balanceType === 'c') {

            // Offset account DR
            $lines[] = [
                'account_id' => $offsetAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => 'Opening Balance Contra',
                'line_no'    => 1,
            ];

            // Main account CR
            $lines[] = [
                'account_id' => $mainAccountId,
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => 'Opening Balance',
                'line_no'    => 2,
            ];
        } else {
            throw new \InvalidArgumentException('Invalid opening balance type. Use D or C.');
        }

        return $lines;
    }


    /**
     * Get or create Opening Balance Difference account
     */
    protected function getOpeningBalanceDiffAccount($companyId)
    {
        $account = $this->accountRepo->getAccountByCode($companyId, 35000); // code for diff balance account

        if (!$account) {
            throw new Exception("Opening Balance Difference account not found. Please run system setup.");
        }

        return $account;
    }
    /**
     * Check if opening balance entry already exists
     */
    protected function hasOpeningBalanceEntry($accountId)
    {
        return $this->voucherRepo->hasOpeningBalanceEntry($accountId);
    }


    public function updateOpeningBalanceEntry(int $accountId, array $data)
    {
        $companyId      = $data['company_id'];
        $financialYearId = $data['financial_year_id'];
        $newBalance     = (float) ($data['opening_balance'] ?? 0);
        $newType        = strtolower($data['opening_type'] ?? 'd');

        $existingRow = $this->voucherRepo->getOpeningBalanceRow($companyId, $financialYearId, $accountId);

        if (!$existingRow) {
            if ($newBalance > 0) {
                $data['uuid'] = (string) Str::uuid();
                $data['financial_year_start'] = $data['financial_year_start'] ?? financial_year_start();
                $this->createOpeningBalanceEntry($accountId, $data);
            }
            return;
        }

        $voucherId = $existingRow->voucher_id;

        if ($newBalance <= 0) {
            VoucherTransaction::where('voucher_id', $voucherId)->update(['debit' => 0, 'credit' => 0]);
            return;
        }

        $obDiffAccount = $this->getOpeningBalanceDiffAccount($companyId);

        if ($newType === 'd') {
            VoucherTransaction::where('voucher_id', $voucherId)
                ->where('account_id', $accountId)
                ->update(['debit' => $newBalance, 'credit' => 0]);
            VoucherTransaction::where('voucher_id', $voucherId)
                ->where('account_id', $obDiffAccount->id)
                ->update(['debit' => 0, 'credit' => $newBalance]);
        } else {
            VoucherTransaction::where('voucher_id', $voucherId)
                ->where('account_id', $accountId)
                ->update(['debit' => 0, 'credit' => $newBalance]);
            VoucherTransaction::where('voucher_id', $voucherId)
                ->where('account_id', $obDiffAccount->id)
                ->update(['debit' => $newBalance, 'credit' => 0]);
        }
    }

    public function getOpeningBalanceForAccountId($companyId, $financialYearId, $accountId){
        $row = $this->voucherRepo->getOpeningBalanceRow($companyId, $financialYearId, $accountId);
        $data = [
            'opening_balance' => 0,
            'opening_type' => 'D',
        ];
        if ($row) {
                $data['opening_balance'] =  $row->debit > 0 ? $row->debit : $row->credit;
                $data['opening_type']   = $row->debit > 0 ? 'D' : 'C';
            } 
        return $data;
    }

    // /**
    //  * Get opening balance difference for company
    //  */
    // public function getOpeningBalanceDifference($companyId)
    // {
    //     $obDiffAccount = $this->getOpeningBalanceDiffAccount($companyId);

    //     $totalDebit = VoucherDetail::where('account_id', $obDiffAccount->id)
    //         ->sum('debit');

    //     $totalCredit = VoucherDetail::where('account_id', $obDiffAccount->id)
    //         ->sum('credit');

    //     return $totalDebit - $totalCredit;
    // }

    // /**
    //  * Update opening balance entry when account is updated
    //  */
    // public function updateOpeningBalanceEntry(Account $account, $oldOpeningBalance, $oldBalanceType)
    // {
    //     // Find existing opening balance voucher
    //     $voucher = Voucher::where('is_opening', true)
    //         ->whereHas('details', function ($query) use ($account) {
    //             $query->where('account_id', $account->id);
    //         })
    //         ->first();

    //     if (!$voucher) {
    //         // No existing entry, create new
    //         return $this->createOpeningBalanceEntry($account);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Delete old entries
    //         $voucher->details()->delete();

    //         // Create new entries with updated values
    //         $obDiffAccount = $this->getOpeningBalanceDiffAccount($account->company_id);
    //         $this->createDualEntry($voucher, $account, $obDiffAccount);

    //         // Update voucher narration
    //         $voucher->update([
    //             'narration' => "Opening balance for {$account->name} (Updated)",
    //             'updated_by' => auth()->id(),
    //         ]);

    //         DB::commit();
    //         return $voucher;
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }
}

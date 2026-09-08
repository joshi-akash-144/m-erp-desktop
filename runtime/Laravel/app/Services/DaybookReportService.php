<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Support\Collection;

class DaybookReportService
{
    /**
     * Get all daybook transactions for a date range
     * No account filter - returns all transactions
     */
    public function getDaybookData(
        int $companyId,
        int $financialYearId,
        array $filters
    ): Collection {

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $accountId = $filters['account_id'] ?? null;
        $voucherTypeKey = $filters['voucher_type_id'] ?? null;
        $voucherTypeId = null;
        if ($voucherTypeKey && $voucherTypeKey !== 'all') {
            $voucherTypeMapping = [
                'journal'           => VoucherType::JOURNAL,
                'payment'           => VoucherType::PAYMENT,
                'receipt'           => VoucherType::RECEIPT,
                'purchase_invoice'  => VoucherType::PURCHASE_INVOICE,
                'sales_invoice'     => VoucherType::SALE_INVOICE,
                'debit_note'        => VoucherType::DEBIT_NOTE,
                'credit_note'       => VoucherType::CREDIT_NOTE,
                'purchase_return'   => VoucherType::PURCHASE_RETURN,
                'sales_return'       => VoucherType::SALES_RETURN,
            ];
            if (isset($voucherTypeMapping[$voucherTypeKey])) {
                $voucherTypeId = $voucherTypeMapping[$voucherTypeKey];
            }
        }
        $includeNarration = isset($filters['narration']) && ($filters['narration'] === '1' || $filters['narration'] === 1 || $filters['narration'] === true);

        $vouchers = Voucher::with([
            'voucherType',
            'details.account',
        ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('is_active', 1)
            ->where('voucher_type_id','!=',VoucherType::OPENING_BALANCE);

        if ($startDate && $endDate) {
            $vouchers->whereBetween('voucher_date', [$startDate, $endDate]);
        }

        if ($voucherTypeId) {
            $vouchers->where('voucher_type_id', $voucherTypeId);
        }

        if ($accountId) {
            $vouchers->whereHas('details', function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            });
        }

        // Order vouchers by voucher_date and id to keep transactions chronological and grouped per voucher
        $vouchers = $vouchers->orderBy('voucher_date')
            ->orderBy('voucher_type_id')
            ->orderBy('voucher_serial')
            ->get();

        $transactions = collect();
        foreach ($vouchers as $voucher) {
            foreach ($voucher->details as $transaction) {

                $transactions->push((object)[
                    'voucher_id' => $voucher->id,
                    'voucher_date' => $voucher->voucher_date,
                    'voucher_number' => $voucher->voucher_number,
                    'voucher_serial' => $voucher->voucher_serial,
                    'reference_number' => $voucher->reference_number,
                    'voucher_type' => $voucher->voucherType->name ?? null,
                    'account_id' => $transaction->account_id,
                    'account_name' => $transaction->account->name ?? null,
                    'against_account_id' => $transaction->against_account_id,
                    'debit' => $transaction->debit,
                    'credit' => $transaction->credit,
                    'narration' => $voucher->narration,
                ]);
            }
        }

        // Add narration rows if requested
        if ($includeNarration) {
            $transactions = $this->addNarrationRows($transactions);
        }

        // Blank out duplicate date, voucher_type, and voucher_serial for grouped rows
        $transactions = $this->blankDuplicateGroupHeaders($transactions);

        return $transactions;
    }

    /**
     * Add narration rows to transactions
     * Adds a narration row at the end of each voucher group
     */
    protected function addNarrationRows(Collection $transactions): Collection
    {
        $result = collect();
        $grouped = $transactions->groupBy('voucher_id');

        foreach ($grouped as $voucherTransactions) {
            foreach ($voucherTransactions as $transaction) {
                $result->push($transaction);
            }

            // Add narration row only if narration exists and is not empty
            $firstTxn = $voucherTransactions->first();
            if ($firstTxn && !empty(trim((string)($firstTxn->narration ?? '')))) {
                $result->push((object)[
                    'row_type' => 'narration',
                    'voucher_id' => null,
                    'voucher_date' => null,
                    'voucher_number' => null,
                    'voucher_serial' => null,
                    'reference_number' => null,
                    'voucher_type' => 'Narration',
                    'account_id' => null,
                    'account_name' => null,
                    'against_account_id' => null,
                    'debit' => null,
                    'credit' => null,
                    'narration' => $firstTxn->narration,
                ]);
            }
        }

        return $result;
    }

    /**
     * Blank out duplicate date, voucher_type, and voucher_serial for grouped rows
     * When consecutive rows have the same date, voucher_type, and voucher_serial,
     * only the first row shows these values, others show blank
     */
    protected function blankDuplicateGroupHeaders(Collection $transactions): Collection
    {
        $result = collect();
        $currentDate = null;
        $currentType = null;
        $currentSerial = null;

        foreach ($transactions as $transaction) {
            // Make a copy to avoid modifying original
            $row = (object) (array) $transaction;

            if (
                $row->voucher_date === $currentDate &&
                $row->voucher_type === $currentType &&
                $row->voucher_serial === $currentSerial
            ) {
                // Blank out the group headers
                $row->voucher_date = null;
                $row->voucher_type = null;
                $row->voucher_serial = null;
            } else {
                // Update current group key values when group changes
                $currentDate = $row->voucher_date;
                $currentType = $row->voucher_type;
                $currentSerial = $row->voucher_serial;
            }

            $result->push($row);
        }

        return $result;
    }
}

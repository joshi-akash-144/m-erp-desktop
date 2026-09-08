<?php

namespace App\Repositories;

use App\Models\StockVoucher;
use App\Models\StockVoucherTransaction;
use App\Models\VoucherType;
use Carbon\Carbon;

class StockVoucherRepository extends BaseRepository
{
    public function __construct(StockVoucher $stockVoucher)
    {
        parent::__construct($stockVoucher);
    }

    public function findByVoucherId(int $voucherId): ?StockVoucher
    {
        return StockVoucher::where('voucher_id', $voucherId)->first();
    }

    public function deleteByVoucherId(int $voucherId): void
    {
        $stock = StockVoucher::where('voucher_id', $voucherId)->first();

        if ($stock) {
            $stock->transactions()->delete();
            $stock->delete();
        }
    }

    public function getStockStatus(int $companyId, int $financialYearId, array $filters = [])
    {
        $transactions = StockVoucherTransaction::with([
                'item:id,name,unit_id',
                'item.unit:id,name'
            ])
            ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $filters) {
                $q->where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId);

                if (!empty($filters['as_at_date'])) {
                    $asAt = Carbon::createFromFormat('d-m-Y', $filters['as_at_date'])->format('Y-m-d');
                    $q->where('voucher_date', '<=', $asAt);
                } elseif (!empty($filters['end_date'])) {
                    $end = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->format('Y-m-d');
                    // Opening stock always included; other entries up to end_date
                    $q->where(function ($inner) use ($end) {
                        $inner->where('voucher_type_id', VoucherType::OPENING_STOCK)
                              ->orWhere('voucher_date', '<=', $end);
                    });
                }
            })
            ->get();

        return $transactions;
    }

    public function monthWiseStockStatus(int $companyId, int $financialYearId, $id, array $filters = [])
    {
        $stocks = StockVoucherTransaction::with([
                'stockVoucher:id,voucher_date,voucher_type_id',
                'item:id,name,unit_id',
                'item.unit:id,name',
            ])
            ->select('id', 'stock_voucher_id', 'item_id', 'in_qty', 'out_qty', 'rate', 'amount')
            ->where('item_id', $id)
            ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $filters) {
                $q->where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    // Opening stock is always in opening balance — never in period rows
                    ->where('voucher_type_id', '!=', VoucherType::OPENING_STOCK);

                if (!empty($filters['as_at_date'])) {
                    $asAt = Carbon::createFromFormat('d-m-Y', $filters['as_at_date'])->format('Y-m-d');
                    $q->where('voucher_date', '<=', $asAt);
                } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->format('Y-m-d');
                    $end   = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->format('Y-m-d');
                    $q->whereBetween('voucher_date', [$start, $end]);
                }
            })
            ->get();

        return $stocks;
    }

    /**
     * Return all StockVoucherTransactions that contribute to stock value as at $date.
     *
     * Opening stock vouchers (type 28) are ALWAYS included (they are the FY brought-forward balance).
     * Regular transactions are included when:
     *   $inclusive = false  → voucher_date <  $date   (for period opening balance)
     *   $inclusive = true   → voucher_date <= $date   (for period closing balance)
     *
     * $date must be in Y-m-d format.
     */
    public function getStockValuationAsAt(int $companyId, int $financialYearId, string $date, bool $inclusive)
    {
        $operator = $inclusive ? '<=' : '<';

        return StockVoucherTransaction::with([
                'item:id,name,item_group_id,unit_id',
                'item.unit:id,name',
                'item.itemGroup:id,name',
            ])
            ->select('item_id', 'in_qty', 'out_qty', 'rate', 'amount')
            ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $date, $operator) {
                $q->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId)
                  ->where(function ($inner) use ($date, $operator) {
                      $inner->where('voucher_type_id', VoucherType::OPENING_STOCK)
                            ->orWhere('voucher_date', $operator, $date);
                  });
            })
            ->get();
    }

    public function dateWiseStockStatus(int $companyId, int $financialYearId, int $id, $startDate, $endDate)
    {
        $start = Carbon::createFromFormat('d-m-Y', $startDate)->format('Y-m-d');
        $end   = Carbon::createFromFormat('d-m-Y', $endDate)->format('Y-m-d');

        $stocks = StockVoucherTransaction::with([
            'stockVoucher:id,voucher_date,voucher_id,voucher_type_id,voucher_serial,reference_number',
            'stockVoucher.voucherType:id,name',
            'item:id,name,unit_id',
            'item.unit:id,name',
        ])
        ->select('id', 'stock_voucher_id', 'item_id', 'in_qty', 'out_qty', 'rate', 'amount')
        ->where('item_id', $id)
        ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $start, $end) {
            $q->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                // Opening stock is always in opening balance — never in period rows
                ->where('voucher_type_id', '!=', VoucherType::OPENING_STOCK)
                ->whereBetween('voucher_date', [$start, $end]);
        })
        ->get();

        $vouchers = $stocks->pluck('stockVoucher')->unique('id');

        $purchaseVouchers = $vouchers->filter(fn($v) => $v->voucherType && $v->voucherType->name === 'Purchase Invoice');
        if ($purchaseVouchers->isNotEmpty()) {
            \Illuminate\Database\Eloquent\Collection::make($purchaseVouchers)->load([
                'purchaseInvoice:id,voucher_id,invoice_serial,account_id,sales_invoice_serial',
                'purchaseInvoice.account:id,name'
            ]);
        }

        $salesVouchers = $vouchers->filter(fn($v) => $v->voucherType && $v->voucherType->name === 'Sales Invoice');
        if ($salesVouchers->isNotEmpty()) {
            \Illuminate\Database\Eloquent\Collection::make($salesVouchers)->load([
                'salesInvoice:id,voucher_id,invoice_serial,account_id',
                'salesInvoice.account:id,name'
            ]);
        }

        return $stocks;
    }
}

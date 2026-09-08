<?php

namespace App\Services;

use App\Models\StockVoucher;
use App\Models\StockVoucherTransaction;
use App\Repositories\StockVoucherRepository;
use Carbon\Carbon;

class StockVoucherService
{
    protected StockVoucherRepository $stockVoucherRepo;

    public function __construct(
        StockVoucherRepository $stockVoucherRepo,

    ) {
        $this->stockVoucherRepo = $stockVoucherRepo;
    }

    public function upsertStockVoucher(array $master, array $transactions): ?StockVoucher
    {
        if (empty($transactions)) {
            $this->stockVoucherRepo->deleteByVoucherId($master['voucher_id']);
            return null;
        }

        $stock = $this->stockVoucherRepo->findByVoucherId(
            $master['voucher_id']
        );

        if ($stock) {
            $stock->update($master);

            $stock->transactions()->delete();

            foreach ($transactions as $transaction) {
                $stock->transactions()->create($transaction);
            }

            return $stock;
        }

        $stock = $this->stockVoucherRepo->create($master);

        foreach ($transactions as $transaction) {
            $stock->transactions()->create($transaction);
        }

        return $stock;
    }

    public function getStockStatus(int $companyId ,int $financialYearId ,array $filters = []): array
    {
        $transactions = $this->stockVoucherRepo->getStockStatus($companyId,$financialYearId,$filters);
        $stockStatus = [];
        $totalQuantity = 0;
        $totalAmount = 0;

        //1. Group by item to provide a summarized stock view
        $groupedItems = $transactions->groupBy('item_id');

        foreach ($groupedItems as $itemId => $itemTransactions) {
            $first = $itemTransactions->first();
            $itemQty = 0;
            $itemAmt = 0;

            $inQty = 0;
            $inAmt = 0;

            foreach ($itemTransactions as $transaction) {
                $qty = (float)$transaction->in_qty - (float)$transaction->out_qty;
                $itemQty += $qty;

                // Only use inward transactions to calculate average cost
                if ((float)$transaction->in_qty > 0) {
                    $inQty += (float)$transaction->in_qty;
                    // If the transaction has an 'amount' column use it, else fallback to in_qty * rate
                    $amt = (float)$transaction->amount > 0 ? (float)$transaction->amount : ((float)$transaction->in_qty * (float)$transaction->rate);
                    $inAmt += $amt;
                }
            }

            // Calculate average rate based on purchases
            $averageRate = $inQty > 0 ? ($inAmt / $inQty) : 0;
            // The value of the remaining stock is its quantity multiplied by its average cost
            $itemAmt = round(abs($itemQty * $averageRate), 2);

            $stockStatus[] = [
                'item_id' => $itemId,
                'item_name' => $first->item->name ?? 'Unknown',
                'unit_name' => $first->item->unit->name ?? ' ',
                'quantity' => round($itemQty, 4),
                'rate' => round($averageRate, 4),
                'amount' => $itemAmt
            ];

            $totalQuantity += $itemQty;
            $totalAmount += $itemAmt;
        }

        // 2. Sort alphabetically by item name
        usort($stockStatus, fn($a, $b) => strcmp($a['item_name'], $b['item_name']));

        // 3. APPLY PAGINATION MANUALLY
        $currentPage = request()->input('page', 1);
        $pageSize = $filters['size'] ?? 5;
        
        // Slice the final calculated array to return only the current chunk
        $offset = ($currentPage - 1) * $pageSize;
        $paginatedItems = array_slice($stockStatus, $offset, $pageSize);
        
        // Calculate total pages for Tabulator
        $lastPage = ceil(count($stockStatus) / $pageSize);

        return [
            'data' => array_values($paginatedItems),
            'last_page' => $lastPage,
            'grand_total' => [
                'total_quantity' => round($totalQuantity, 4),
                'total_amount' => round($totalAmount, 2)
            ]
        ];       
    }
    public function monthWiseStockStatus(int $companyId, int $financialYearId, int $id, ?array $filters = []): array
    {
        $openingBalance = 0;
        $openingAmount = 0;

        if (!empty($filters['as_at_date']) || !empty($filters['start_date'])) {
            $openingTxnsQuery = StockVoucherTransaction::where('item_id', $id)
                ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $filters) {
                    $q->where('company_id', $companyId)
                      ->where('financial_year_id', $financialYearId);
                      
                    if (!empty($filters['as_at_date'])) {
                        $q->where('voucher_type_id', \App\Models\VoucherType::OPENING_STOCK);
                    } elseif (!empty($filters['start_date'])) {
                        $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->format('Y-m-d');
                        $q->where(function ($inner) use ($start) {
                            $inner->where('voucher_type_id', \App\Models\VoucherType::OPENING_STOCK)
                                  ->orWhere('voucher_date', '<', $start);
                        });
                    }
                });

            $openingTxns = $openingTxnsQuery->get();
            $inQtyTotal = 0;
            $inAmtTotal = 0;

            foreach ($openingTxns as $txn) {
                $inQty = (float)$txn->in_qty;
                $outQty = (float)$txn->out_qty;
                $openingBalance += ($inQty - $outQty);

                if ($inQty > 0) {
                    $inQtyTotal += $inQty;
                    $inAmtTotal += ((float)$txn->amount > 0 ? (float)$txn->amount : ($inQty * (float)$txn->rate));
                }
            }
            
            $avgRate = $inQtyTotal > 0 ? ($inAmtTotal / $inQtyTotal) : 0;
            $openingAmount = round(abs($openingBalance * $avgRate), 2);
        }

        $stocks = $this->stockVoucherRepo->monthWiseStockStatus($companyId, $financialYearId, $id, $filters);

        // Sort by date so running balance is correct
        $stocks = $stocks->sortBy(fn($s) => $s->stockVoucher->voucher_date);

        if ($stocks->isEmpty()) {
            return [
                'data'          => [],
                'last_page'     => 1,
                'item_name'     => '',
                'opening_stock' => $openingBalance,
                'opening_amount'=> $openingAmount,
                'closing_stock' => $openingBalance,
                'total_qty_in'  => 0,
                'total_qty_out' => 0,
            ];
        }

        $firstStock     = $stocks->first();
        $item_name      = $firstStock->item->name . ' ( Unit: ' . $firstStock->item->unit->name . ' )';
        $monthWiseStock = [];
        $runningBalance = $openingBalance;
        $runningInQtyTotal = $inQtyTotal ?? 0;
        $runningInAmtTotal = $inAmtTotal ?? 0;
        $totalQtyIn     = 0;
        $totalQtyOut    = 0;

        foreach ($stocks as $stock) {
            $voucherDate   = $stock->stockVoucher->voucher_date;
            $monthKey      = date('Y-m', strtotime($voucherDate));   // unique key per month
            $monthNameYear = date('F Y', strtotime($voucherDate));

            $quantity    = (float)$stock->in_qty - (float)$stock->out_qty;
            $quantityIn  = $quantity > 0 ? $quantity  : 0.0;
            $quantityOut = $quantity < 0 ? -$quantity : 0.0;

            $amount    = (float)$stock->rate * $quantity;
            $amountIn  = $amount > 0 ? $amount  : 0.0;
            $amountOut = $amount < 0 ? -$amount : 0.0;

            $runningBalance += ($quantityIn - $quantityOut);
            $totalQtyIn     += $quantityIn;
            $totalQtyOut    += $quantityOut;
            
            if ($quantityIn > 0) {
                $runningInQtyTotal += $quantityIn;
                $runningInAmtTotal += $amountIn;
            }
            $currentAvgRate = $runningInQtyTotal > 0 ? ($runningInAmtTotal / $runningInQtyTotal) : 0;
            $runningAmount = round(abs($runningBalance * $currentAvgRate), 2);

            if (!isset($monthWiseStock[$monthKey])) {
                $monthWiseStock[$monthKey] = [
                    'item_id'         => $stock->item_id,
                    'month_name_year' => $monthNameYear,
                    'qty_in'          => $quantityIn,
                    'amount_in'       => $amountIn,
                    'qty_out'         => $quantityOut,
                    'amount_out'      => $amountOut,
                    'balance'         => $runningBalance,
                    'balance_amount'  => $runningAmount,
                ];
            } else {
                $monthWiseStock[$monthKey]['qty_in']      += $quantityIn;
                $monthWiseStock[$monthKey]['amount_in']   += $amountIn;
                $monthWiseStock[$monthKey]['qty_out']     += $quantityOut;
                $monthWiseStock[$monthKey]['amount_out']  += $amountOut;
                $monthWiseStock[$monthKey]['balance']      = $runningBalance;
                $monthWiseStock[$monthKey]['balance_amount'] = $runningAmount;
            }
        }
        
        $closingAmount = isset($runningAmount) ? $runningAmount : $openingAmount;

        $currentPage           = request()->input('page', 1);
        $pageSize              = $filters['size'] ?? 9999;
        $offset                = ($currentPage - 1) * $pageSize;
        $paginatedMonthWiseStock = array_slice($monthWiseStock, $offset, $pageSize);
        $lastPage              = (int) ceil(count($monthWiseStock) / $pageSize) ?: 1;

        return [
            'data'          => array_values($paginatedMonthWiseStock),
            'last_page'     => $lastPage,
            'item_name'     => $item_name,
            'opening_stock' => $openingBalance,
            'opening_amount'=> $openingAmount,
            'closing_stock' => $runningBalance,
            'closing_amount'=> $closingAmount,
            'total_qty_in'  => $totalQtyIn,
            'total_qty_out' => $totalQtyOut,
        ];
    }
    /**
     * Returns opening and closing stock in the hierarchical format expected by
     * ProfitLossService::getTradingAccount().
     *
     * Both dates must be Y-m-d.
     *
     * Shape per section:
     *   [
     *     'total_amount' => float,
     *     'items'        => [
     *       [ 'item_group_id', 'name', 'amount', 'is_group'=>true,  'is_item'=>false, 'level'=>0, 'section', 'children' => [
     *           [ 'item_id', 'item_group_id', 'name', 'quantity', 'rate', 'amount', 'is_group'=>false, 'is_item'=>true, 'level'=>1, 'section' ]
     *         ]
     *       ],
     *     ]
     *   ]
     */
    public function getStockValuationForTrading(int $companyId, int $financialYearId, string $fromDate, string $toDate): array
    {
        $openingTxns = $this->stockVoucherRepo->getStockValuationAsAt($companyId, $financialYearId, $fromDate, false);
        $closingTxns = $this->stockVoucherRepo->getStockValuationAsAt($companyId, $financialYearId, $toDate,   true);

        return [
            'opening_stock' => $this->buildStockHierarchy($openingTxns, 'opening_stock'),
            'closing_stock' => $this->buildStockHierarchy($closingTxns, 'closing_stock'),
        ];
    }

    private function buildStockHierarchy($transactions, string $section): array
    {
        // ── 1. Aggregate qty and inward cost per item ─────────────────────────
        $itemData = [];

        foreach ($transactions as $t) {
            $itemId = $t->item_id;

            if (!isset($itemData[$itemId])) {
                $item = $t->item;
                $itemData[$itemId] = [
                    'item_id'        => $itemId,
                    'item_group_id'  => $item?->item_group_id ?? 0,
                    'item_group_name'=> $item?->itemGroup?->name ?? 'Uncategorized',
                    'name'           => $item?->name ?? 'Unknown',
                    'unit_name'      => $item?->unit?->name ?? '',
                    'net_qty'        => 0.0,
                    'in_qty_total'   => 0.0,
                    'in_amt_total'   => 0.0,
                ];
            }

            $inQty  = (float) $t->in_qty;
            $outQty = (float) $t->out_qty;

            $itemData[$itemId]['net_qty'] += $inQty - $outQty;

            if ($inQty > 0) {
                $inAmt = (float) $t->amount > 0
                    ? (float) $t->amount
                    : $inQty * (float) $t->rate;

                $itemData[$itemId]['in_qty_total'] += $inQty;
                $itemData[$itemId]['in_amt_total'] += $inAmt;
            }
        }

        // ── 2. Compute value per item and group by ItemGroup ──────────────────
        // Formula mirrors getStockStatus: avgRate from inward cost only, then qty × rate.
        // abs() applied to match the stock status page display (positive even for negative net qty).
        $groupBuckets = [];

        foreach ($itemData as $data) {
            $avgRate = $data['in_qty_total'] > 0
                ? $data['in_amt_total'] / $data['in_qty_total']
                : 0.0;

            $value   = round(abs($data['net_qty'] * $avgRate), 2);
            $groupId = $data['item_group_id'];

            if (!isset($groupBuckets[$groupId])) {
                $groupBuckets[$groupId] = [
                    'group_name' => $data['item_group_name'],
                    'total'      => 0.0,
                    'items'      => [],
                ];
            }

            $groupBuckets[$groupId]['items'][] = [
                'item_id'       => $data['item_id'],
                'item_group_id' => $groupId,
                'name'          => $data['name'],
                'unit_name'     => $data['unit_name'],
                'quantity'      => round($data['net_qty'], 4),
                'rate'          => round($avgRate, 4),
                'amount'        => $value,
                'is_group'      => false,
                'is_account'    => false,
                'is_item'       => true,
                'level'         => 1,
                'section'       => $section,
            ];

            $groupBuckets[$groupId]['total'] += $value;
        }

        // ── 3. Build level-0 group entries ────────────────────────────────────
        $hierarchyItems = [];
        $totalAmount    = 0.0;

        foreach ($groupBuckets as $groupId => $bucket) {
            $children = $bucket['items'];
            usort($children, fn($a, $b) => strcmp($a['name'], $b['name']));

            $groupTotal   = round($bucket['total'], 2);
            $totalAmount += $groupTotal;

            $hierarchyItems[] = [
                'item_group_id' => $groupId,
                'name'          => $bucket['group_name'],
                'amount'        => $groupTotal,
                'is_group'      => true,
                'is_account'    => false,
                'is_item'       => false,
                'level'         => 0,
                'section'       => $section,
                'children'      => $children,
            ];
        }

        usort($hierarchyItems, fn($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'total_amount' => round($totalAmount, 2),
            'items'        => $hierarchyItems,
        ];
    }

    public function dateWiseStockStatus(int $companyId, int $financialYearId, int $id, $startDate, $endDate, array $filters = []): array
    {
        $start = Carbon::createFromFormat('d-m-Y', $startDate)->format('Y-m-d');

        // Opening balance = Opening Stock vouchers (type 28) + regular transactions before startDate
        $openingTxnsQuery = StockVoucherTransaction::where('item_id', $id)
            ->whereHas('stockVoucher', function ($q) use ($companyId, $financialYearId, $start) {
                $q->where('company_id', $companyId)
                  ->where('financial_year_id', $financialYearId)
                  ->where(function ($inner) use ($start) {
                      $inner->where('voucher_type_id', \App\Models\VoucherType::OPENING_STOCK)
                            ->orWhere('voucher_date', '<', $start);
                  });
            });

        $openingTxns = $openingTxnsQuery->get();
        $openingBalance = 0;
        $inQtyTotal = 0;
        $inAmtTotal = 0;

        foreach ($openingTxns as $txn) {
            $inQty = (float)$txn->in_qty;
            $outQty = (float)$txn->out_qty;
            $openingBalance += ($inQty - $outQty);

            if ($inQty > 0) {
                $inQtyTotal += $inQty;
                $inAmtTotal += ((float)$txn->amount > 0 ? (float)$txn->amount : ($inQty * (float)$txn->rate));
            }
        }
        
        $avgRate = $inQtyTotal > 0 ? ($inAmtTotal / $inQtyTotal) : 0;
        $openingAmount = round(abs($openingBalance * $avgRate), 2);
        // dd($openingBalance);
        $stocks = $this->stockVoucherRepo->dateWiseStockStatus($companyId, $financialYearId, $id, $startDate, $endDate);
        // dd($stocks->toArray());
        // Sort by date to ensure running balance is calculated correctly
        $stocks = $stocks->sortBy(function($stock) {
            return $stock->stockVoucher->voucher_date;
        });

        $balance = (float)$openingBalance;
        $runningInQtyTotal = $inQtyTotal ?? 0;
        $runningInAmtTotal = $inAmtTotal ?? 0;
        $runningAmount = $openingAmount;
        $dateWiseStock = [];
        $totalQntyIn = 0.00;
        $totalQntyOut = 0.00;
        $unitName=$stocks->first()->item->unit->name;
        $itemName = $stocks->isNotEmpty() 
            ? $stocks->first()->item->name . " ( Unit: " . $unitName . " )"
            : " " ;
        $item_id = $stocks->isNotEmpty() 
            ? $stocks->first()->item_id
            : " " ;
        foreach ($stocks as $stock) {
            $inQty = (float)$stock->in_qty ?: 0.00;
            $outQty = (float)$stock->out_qty ?: 0.00;
            
            $balance += ($inQty - $outQty);
            
            $voucherTypeName = $stock->stockVoucher->voucherType->name ?? '';
            $dateWiseStock[] = [
                'invoice_id' => $stock->stockVoucher?->purchaseInvoice?->id ?? $stock->stockVoucher?->salesInvoice?->id,
                'voucher_date' => $stock->stockVoucher->voucher_date,
                'voucher_type' => $voucherTypeName ?: ' ',
                'voucher_bill_no' => $voucherTypeName === 'Purchase Invoice'
                    ? ($stock->stockVoucher->voucher_serial ?? '') . '/' . ($stock->stockVoucher->reference_number ?? '')
                    : ($stock->stockVoucher->voucher_serial ?? ''),
                'sales_invoice_number' => $stock->stockVoucher->purchaseInvoice->sales_invoice_serial ?? ' ',
                // 'supplier_name' => $stock->stockVoucher->purchaseInvoice->account->name ?? 'Internal',
                'supplier_name' => $voucherTypeName === 'Purchase Invoice'
                    ? ($stock->stockVoucher->purchaseInvoice->account->name ?? ' ')
                    : ($stock->stockVoucher->salesInvoice->account->name ?? ' '),
                'quantity_in' => $inQty,
                'quantity_out' => $outQty,
                'balance' => $balance,
            ];

            $totalQntyIn += $inQty;
            $totalQntyOut += $outQty;
            
            if ($inQty > 0) {
                $runningInQtyTotal += $inQty;
                $amt = ((float)$stock->amount > 0 ? (float)$stock->amount : ($inQty * (float)$stock->rate));
                $runningInAmtTotal += $amt;
            }
            $currentAvgRate = $runningInQtyTotal > 0 ? ($runningInAmtTotal / $runningInQtyTotal) : 0;
            $runningAmount = round(abs($balance * $currentAvgRate), 2);
        }  
        $closingAmount = isset($runningAmount) ? $runningAmount : $openingAmount;
        // dd($dateWiseStock);
        
        // 2. APPLY PAGINATION MANUALLY
        $currentPage = request()->input('page', 1);
        $pageSize = $filters['size'] ?? 20;
        
        // Slice the final calculated array to return only the current chunk
        $offset = ($currentPage - 1) * $pageSize;
        $paginatedDateWiseStock = array_slice($dateWiseStock, $offset, $pageSize);
        
        // Calculate total pages for Tabulator
        $lastPage = ceil(count($dateWiseStock) / $pageSize);
        return [
            'data' => $paginatedDateWiseStock,
            'item_id' => $item_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'last_page' => $lastPage,
            'item_name' => $itemName,
            'unit_name'=>$unitName,
            'opening_stock' => (float)$openingBalance,
            'opening_amount' => $openingAmount,
            'closing_stock' => $balance,
            'closing_amount' => $closingAmount,
            'closing_amount' => $closingAmount,
            'total_qty_in' => $totalQntyIn,
            'total_qty_out' => $totalQntyOut,
            'range_date' => 'From: ' . $startDate . ' - To: ' . $endDate,
        ];
    }
}

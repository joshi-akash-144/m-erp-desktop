<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoiceItem;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\StockVoucherService;
use App\Services\VoucherService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ItemRepository extends BaseRepository
{
    protected VoucherService $voucherService;

    protected StockVoucherService $stockVoucherService;
    public function __construct(Item $item, VoucherService $voucherService, StockVoucherService $stockVoucherService)
    {
        parent::__construct($item);
        $this->voucherService = $voucherService;
        $this->stockVoucherService = $stockVoucherService;
    }

    /**
     * Create new item.
     */
    public function create(array $data): Item
    {
        return DB::transaction(function () use ($data) {
            // dd($data);

            $lastSku = $this->model->where('company_id', $data['company_id'])->max('sku');

            $nextNumber = $lastSku ? intval(preg_replace('/[^0-9]/', '', $lastSku)) + 1 : 1;

            $data['sku'] = 'SKU-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);


            $item = $this->model->create($data);

            // Set opening balances
            $openingInfo['openingQty'] = (float) ($data['opening_qty'] ?? 0);
            $openingInfo['openingValue'] = (float) ($data['opening_value'] ?? 0);
            $openingInfo['openingRate'] = ($openingInfo['openingQty'] > 0 && $openingInfo['openingValue'] > 0) ? $openingInfo['openingValue'] / $openingInfo['openingQty'] : 0;


            $this->createOpeningStock($item, $openingInfo, $data['financial_year_id']);

            return $item;
        });
    }

    private function createOpeningStock(Item $item, array $openingInfo, int $financialYearId): void
    {

        $lastSerial = $this->voucherService->getNextVoucherNumber(VoucherType::OPENING_STOCK, $item->company_id, $financialYearId);

        // first make voucher entry 
        $master = [
            'uuid'              => $item->uuid,
            'company_id'        => $item->company_id,
            'financial_year_id' => $financialYearId,
            'voucher_date'      => financial_year_start(), // start of financial year date
            'voucher_type_id'   => VoucherType::OPENING_STOCK ?? 28,
            'source_id'         => null,
            'source_type'       => null,
            'reference_number'  => 'OP-' . $item->sku,
            'voucher_serial'    => $lastSerial->serial,
            'voucher_number'    => $lastSerial->voucher_number,
            'narration'         => 'Opening stock for item ' . $item->name,
            'is_opening'         => true,
        ];

        //Two account affect hear stock in hand and opening stock account 
        //140000 - stock in hand0
        //34000 - opening stock account

        $stockAccountId = Account::where('company_id', $item->company_id)
            ->where('code', '14000')
            ->value('id');
        $openingStockAccountId = Account::where('company_id', $item->company_id)
            ->where('code', '34000')
            ->value('id');

        $amount = abs($openingInfo['openingValue']);

        if ($openingInfo['openingValue'] >= 0) {

            $lines[] = [
                'account_id' => $stockAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'item_id'    => $item->id,
            ];

            $lines[] = [
                'account_id' => $openingStockAccountId,
                'debit'      => 0,
                'credit'     => $amount,
                'item_id'    => $item->id,
            ];
        } else {

            $lines[] = [
                'account_id' => $openingStockAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'item_id'    => $item->id,
            ];

            $lines[] = [
                'account_id' => $stockAccountId,
                'debit'      => 0,
                'credit'     => $amount,
                'item_id'    => $item->id,
            ];
        }

        $voucher = $this->voucherService->createVoucher($master, $lines);

        // create stock voucher 
        $stockMaster = [
            'voucher_id'        => $voucher->id,
            'company_id'        => $voucher->company_id,
            'financial_year_id' => $voucher->financial_year_id,
            'voucher_type_id'   => VoucherType::OPENING_STOCK ?? 28,
            'voucher_number'    => $voucher->voucher_number,
            'voucher_serial'    => $voucher->voucher_serial,
            'reference_number'  => 'OP-' . $item->sku,
            'voucher_date'      => financial_year_start(), // start of financial year date
        ];
        $stockTransactions = [
            'item_id' => $item->id,
            'in_qty' => $openingInfo['openingQty'],
            'out_qty' => 0,
            'rate' => $openingInfo['openingRate'],
            'amount' => $openingInfo['openingValue'],

        ];

         $this->stockVoucherService->upsertStockVoucher(
            $stockMaster,
            [$stockTransactions]
        );


    }

    public function update(Model $item, array $data): Model
    {
        return DB::transaction(function () use ($item, $data) {
            $item->update($data);

            $financialYearId = (int) session('financial_year_id');
            $openingQty      = (float) ($data['opening_qty'] ?? 0);
            $openingValue    = (float) ($data['opening_value'] ?? 0);
            $openingRate     = ($openingQty > 0 && $openingValue > 0) ? $openingValue / $openingQty : 0;

            $voucher = Voucher::where('uuid', $item->uuid)
                ->where('voucher_type_id', VoucherType::OPENING_STOCK)
                ->where('company_id', $item->company_id)
                ->where('financial_year_id', $financialYearId)
                ->first();

            if ($voucher) {
                $stockAccountId        = Account::where('company_id', $item->company_id)->where('code', '14000')->value('id');
                $openingStockAccountId = Account::where('company_id', $item->company_id)->where('code', '34000')->value('id');

                $amount = abs($openingValue);

                if ($openingValue >= 0) {
                    $lines = [
                        ['account_id' => $stockAccountId,        'debit' => $amount, 'credit' => 0,       'item_id' => $item->id],
                        ['account_id' => $openingStockAccountId, 'debit' => 0,       'credit' => $amount, 'item_id' => $item->id],
                    ];
                } else {
                    $lines = [
                        ['account_id' => $openingStockAccountId, 'debit' => $amount, 'credit' => 0,       'item_id' => $item->id],
                        ['account_id' => $stockAccountId,        'debit' => 0,       'credit' => $amount, 'item_id' => $item->id],
                    ];
                }

                $this->voucherService->updateVoucher(
                    ['narration' => 'Opening stock for item ' . $item->name],
                    $lines,
                    $voucher->id
                );

                $this->stockVoucherService->upsertStockVoucher([
                    'voucher_id'        => $voucher->id,
                    'company_id'        => $voucher->company_id,
                    'financial_year_id' => $voucher->financial_year_id,
                    'voucher_type_id'   => VoucherType::OPENING_STOCK,
                    'voucher_number'    => $voucher->voucher_number,
                    'voucher_serial'    => $voucher->voucher_serial,
                    'reference_number'  => 'OP-' . $item->sku,
                    'voucher_date'      => $voucher->voucher_date,
                ], [[
                    'item_id' => $item->id,
                    'in_qty'  => $openingQty,
                    'out_qty' => 0,
                    'rate'    => $openingRate,
                    'amount'  => $openingValue,
                ]]);
            } else {
                $this->createOpeningStock($item, [
                    'openingQty'   => $openingQty,
                    'openingValue' => $openingValue,
                    'openingRate'  => $openingRate,
                ], $financialYearId);
            }

            return $item->fresh();
        });
    }

    public function isUsedInTransactions(Item $item): bool
    {
        return PurchaseInvoiceItem::where('item_id', $item->id)->exists()
            || SalesInvoiceItem::where('item_id', $item->id)->exists();
    }

    public function delete(Model $item): bool
    {
        return DB::transaction(function () use ($item) {
            $financialYearId = (int) session('financial_year_id');

            $voucher = Voucher::where('uuid', $item->uuid)
                ->where('voucher_type_id', VoucherType::OPENING_STOCK)
                ->where('company_id', $item->company_id)
                ->where('financial_year_id', $financialYearId)
                ->first();

            if ($voucher) {
                $this->stockVoucherService->upsertStockVoucher(
                    ['voucher_id' => $voucher->id],
                    []
                );
                $voucher->details()->delete();
                $voucher->delete();
            }

            return $item->delete();
        });
    }

    public function hasAccounts(Item $item): bool
    {
        return $item->accounts()->exists();
    }
}

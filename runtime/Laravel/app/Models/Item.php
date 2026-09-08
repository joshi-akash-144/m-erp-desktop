<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use App\Models\VoucherType;
use App\Models\StockVoucherTransaction;

class Item extends BaseMaster
{
    use HasCompanyContext;
    public const IS_MAINTAIN_STOCK_BALANCE = ['yes','no'];

    protected $fillable = [
        'uuid', 'company_id', 'item_group_id','unit_id','tax_category_id', 'code', 'name', 'print_name','created_by', 'updated_by', 'deleted_by','is_maintain_stock_balance','sku','hsn_sac_code','sale_type_local_id','sale_type_interstate_id','purchase_type_local_id','purchase_type_interstate_id'
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function itemGroup()
    {
        return $this->belongsTo(ItemGroup::class, 'item_group_id');
    }

    public function taxCategory()
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function currentOpeningStock()
    {
        return $this->hasOne(StockVoucherTransaction::class, 'item_id')
                    ->join('stock_vouchers as sv', 'sv.id', '=', 'stock_voucher_transactions.stock_voucher_id')
                    ->where('sv.voucher_type_id', VoucherType::OPENING_STOCK)
                    ->where('sv.financial_year_id', session('financial_year_id'))
                    ->where('sv.company_id', session('company_id'))
                    ->select('stock_voucher_transactions.*');
    }

    public function purchaseTypeLocal()
    {
        return $this->belongsTo(PurchaseType::class, 'purchase_type_local_id');
    }

    public function purchaseTypeInterstate()
    {
        return $this->belongsTo(PurchaseType::class, 'purchase_type_interstate_id');
    }

    public function saleTypeLocal()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_local_id');
    }

    public function saleTypeInterstate()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_interstate_id');
    }
}

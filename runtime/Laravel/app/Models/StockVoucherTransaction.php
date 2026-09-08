<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockVoucherTransaction extends Model
{

    protected $fillable = [
        'stock_voucher_id',
        'item_id',
        'in_qty',
        'out_qty',
        'rate',
        'amount',
    ];

    public function stockVoucher()
    {
        return $this->belongsTo(StockVoucher::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'item_id',
        'destination_id',
        'condition_id',
        'ordered_qty',
        'received_qty',
        // 'remaining_qty',
        'rate',
        'inclusive_rate',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'amount',
        'net_amount',
        'is_closed',
        'remarks',
    ];

    protected $appends = ['remaining_qty']; // Set Value for Accessor
    /* -----------------------------------------
     | Relationships
     |------------------------------------------
     */

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id')->with('unit', 'saleTypeLocal', 'saleTypeInterstate');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class, 'condition_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    /* -----------------------------------------
     | Accessors
     |------------------------------------------
     */

    public function getRemainingQtyAttribute()
    {
        return $this->ordered_qty - $this->received_qty;
    }
}

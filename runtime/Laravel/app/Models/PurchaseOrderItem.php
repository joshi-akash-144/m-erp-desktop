<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'condition_id',
        'ordered_qty',
        'received_qty',
        // 'remaining_qty', 
        'rate',
        'inclusive_rate',
        'discount',
        'taxable_amount',
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class, 'condition_id');
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

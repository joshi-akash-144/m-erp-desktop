<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    protected $table = 'grn_items';

    /* ---------------------------------------------
     | Fillable Fields
     |----------------------------------------------*/
    protected $fillable = [
        'grn_id',
        'item_id',
        'unit_name',
        'condition_id',
        'destination_id',
        'purchase_order_id',
        'purchase_order_item_id',
        'purchase_order_serial',
        'quantity',
        'party_quantity',
        'rate',
        'bag_count',
        'inclusive_rate',
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
        'remarks',
    ];

    /* ---------------------------------------------
     | Casts
     |----------------------------------------------*/
    protected $casts = [
        'qty'               => 'decimal:4',
        'party_qty'         => 'decimal:4',
        'rate'              => 'decimal:2',
        'inclusive_rate'    => 'decimal:2',
        'taxable_amount'    => 'decimal:2',
        'cgst_rate'         => 'decimal:2',
        'sgst_rate'         => 'decimal:2',
        'igst_rate'         => 'decimal:2',
        'cgst_amount'       => 'decimal:2',
        'sgst_amount'       => 'decimal:2',
        'igst_amount'       => 'decimal:2',
        'tax_amount'        => 'decimal:2',
        'amount'            => 'decimal:2',
        'net_amount'        => 'decimal:2',
    ];

    /* ---------------------------------------------
     | Relationships
     |----------------------------------------------*/

    public function grn()
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class, 'condition_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }


    public function PurchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable =  [
        'purchase_invoice_id',
        'item_id',
        'quantity',
        'party_quantity',
        'rate',
        'inclusive_rate',
        'tax_amount',
        'amount',
        'net_amount',
        'cgst_rate',
        'sgst_rate',
        "igst_rate",
        "bag_count",
        "cgst_amount",
        "sgst_amount",
        "igst_amount",
        "taxable_amount",
        "condition_id",
        "destination_id",
        "purchase_order_serial",
        "purchase_order_id",
        "purchase_order_item_id",
    ];


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

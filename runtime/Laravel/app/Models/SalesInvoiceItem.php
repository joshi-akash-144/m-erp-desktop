<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable =  [
        "sales_invoice_id",
        "sales_invoice_serial",

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
    ];

    protected $appends = ['remaining_qty']; // Set Value for Accessor
    /* -----------------------------------------
     | Relationships
     |------------------------------------------
     */

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
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

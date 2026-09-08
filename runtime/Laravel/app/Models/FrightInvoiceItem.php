<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrightInvoiceItem extends Model
{
    protected $table = 'freight_invoice_items';
     protected $fillable = [
        'freight_id',
        'item_id',
        'quantity',
        'import_date',
        'billing_date',
        'customer_po_no',
        'sold_to_party',
        'to',
        'from',
        'vehicle_id',
        'zone_id',
        'is_used',
        'lr_number',
        'dc_number',
        'rate',
    ];
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'to');
    }
}

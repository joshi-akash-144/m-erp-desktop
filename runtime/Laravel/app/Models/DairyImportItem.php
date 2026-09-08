<?php

namespace App\Models;

class DairyImportItem extends BaseMaster
{

    protected $fillable = [
        'dairy_import_id',
        'product_id',
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

    public function dairyImport()
    {
        return $this->belongsTo(DairyImport::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function product()
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'to');
    }
}

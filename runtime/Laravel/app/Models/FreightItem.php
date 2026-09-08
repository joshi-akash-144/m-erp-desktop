<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreightItem extends Model
{
    protected $fillable = [
        'freight_id',
        'item_id',
        'zone_id',
        'bag_type',
        'bag_count',
        'net_weight',
        'kms',
        'quantity',
        'rate',
        'amount',
    ];

    public function freight()
    {
        return $this->belongsTo(Freight::class, 'freight_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceItem extends Model
{

    protected $fillable = [
        'reference_id',
        'destination_id',
        'item_id',
        'quantity',
        'rate',
        'rebate',
        'cd_percentage',
        'cd_amount',
        'net_total',
        'total_amount',
        'tds',
        'freight',
        'gst',
        'penalty'

    ];

    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreightContractorItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'freight_id',
        'destination_id',
        'contractor_id',
        'vehicle_id',
        'date',
        'code',
        'route',
        'vendor',
        'bag_count',
        'kms',
        'rate',
        'amount',
    ];

    public function freight(): BelongsTo
    {
        return $this->belongsTo(Freight::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

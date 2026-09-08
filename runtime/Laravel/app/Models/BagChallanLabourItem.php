<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BagChallanLabourItem extends Model
{
    protected $table = 'bag_challan_labour_items';

    protected $fillable = [
        'bag_challan_labour_id',
        'grn_id',
        'bags',
    ];

    protected $casts = [
        'bags' => 'decimal:2',
    ];

    public function bagChallanLabour()
    {
        return $this->belongsTo(BagChallanLabour::class, 'bag_challan_labour_id');
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }
}

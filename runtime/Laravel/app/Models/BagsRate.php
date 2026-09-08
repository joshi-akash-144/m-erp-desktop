<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class BagsRate extends BaseMaster
{
    use SoftDeletes;

    /* ---------------------------------------------
    |  BAG TYPE
    |----------------------------------------------*/
    const BAG_GUNNY   = 'gunny';
    const BAG_PLASTIC = 'plastic';

    protected $fillable = [
        'uuid',
        'company_id',
        'bags_type',
        'bags_rate',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}

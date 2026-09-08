<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends BaseMaster
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'city',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}

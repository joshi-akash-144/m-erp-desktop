<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class Zone extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'rate',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status'                  => 'boolean',
    ];
}

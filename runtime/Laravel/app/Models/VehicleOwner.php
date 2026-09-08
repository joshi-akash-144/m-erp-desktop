<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class VehicleOwner extends BaseMaster
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];
}

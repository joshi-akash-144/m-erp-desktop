<?php

namespace App\Models;

use App\Traits\HasCompanyContext;


class Godown extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'destination_id',
        'godown_name',
        'remark',
        'company_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function destinationName()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }
    
    
}

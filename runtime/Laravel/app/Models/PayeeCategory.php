<?php

namespace App\Models;

use App\Traits\HasCompanyContext;


class PayeeCategory extends BaseMaster
{
    use HasCompanyContext;    
    protected $fillable = [                
        'uuid',
        'code',
        'payee_category',
        'company_id',
        'status',
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

}

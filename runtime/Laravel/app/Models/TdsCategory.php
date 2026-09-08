<?php

namespace App\Models;

use App\Traits\HasCompanyContext;


class TdsCategory extends BaseMaster
{
    use HasCompanyContext;
    public const TYPES = ['tds', 'tcs', 'higher'];

    protected $fillable = [                
        'uuid',
        'section',
        'code',
        'category_name',
        'default_account_id',
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

    public function details()
    {
        return $this->hasMany(TdsCategoryDetail::class, 'tds_category_id');
    }

    public function defaultAccount()
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }
}

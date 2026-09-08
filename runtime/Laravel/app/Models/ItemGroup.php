<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class ItemGroup extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'name',
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
    public function items()
    {
        return $this->hasMany(Item::class, 'item_group_id');
    }
}

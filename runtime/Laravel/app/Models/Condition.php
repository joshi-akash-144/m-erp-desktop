<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class Condition extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'name','print_name','uuid', 'company_id', 'created_by', 'updated_by', 'deleted_by','is_active'
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function conditionParameter(){
        return $this->hasMany(DairyParameter::class,'condition_id');
    }
}

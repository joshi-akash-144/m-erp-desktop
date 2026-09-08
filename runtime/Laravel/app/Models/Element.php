<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class Element extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'name','print_name','range','uuid', 'company_id','is_active', 'created_by', 'updated_by', 'deleted_by',
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function elementParameter(){
        return $this->hasMany(DairyParameter::class, 'element_id');

    }

}

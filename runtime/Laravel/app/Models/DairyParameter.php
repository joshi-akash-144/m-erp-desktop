<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class DairyParameter extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'condition_id','element_id','guarantee','uuid', 'company_id','is_active', 'created_by', 'updated_by', 'deleted_by',
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
        return $this->hasMany(DairyParameterDetail::class, 'parameter_id');
    }

    public function parameterDetails(){
        return $this->hasMany(DairyParameterDetail::class,'parameter_id');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class,'condition_id');
    }

    public function element()
    {
        return $this->belongsTo(Element::class,'element_id');
    }
}

<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitConversion extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'name','main_unit_id','sub_unit_id','conversion_factor','uuid', 'company_id','is_active', 'created_by', 'updated_by', 'deleted_by',
    ];

    //  public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function mainUnit()
    {
        return $this->belongsTo(Unit::class, 'main_unit_id');
    }

    public function subUnit()
    {
        return $this->belongsTo(Unit::class, 'sub_unit_id');
    }
}

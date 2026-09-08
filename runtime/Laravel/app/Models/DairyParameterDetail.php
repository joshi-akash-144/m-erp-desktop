<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class DairyParameterDetail extends BaseMaster
{
    use HasCompanyContext;

    protected $table = 'dairy_parameter_details';

    protected $fillable = [
        'parameter_id','from','to','difference','rebate','premium','is_active'
    ];

    public function dairyParameter(){
        return $this->belongsTo(DairyParameter::class,'parameter_id');
    }
}

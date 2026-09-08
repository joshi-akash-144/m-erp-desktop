<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DairyParameterMapping extends Model
{
    protected $fillable = ['old_dairy_parameter_id', 'new_dairy_parameter_id', 'name', 'company_id','name'];
}

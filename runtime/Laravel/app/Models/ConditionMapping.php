<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConditionMapping extends Model
{
    protected $fillable = ['old_condition_id', 'new_condition_id', 'company_id', 'name'];
}

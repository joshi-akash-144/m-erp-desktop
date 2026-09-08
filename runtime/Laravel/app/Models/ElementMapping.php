<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementMapping extends Model
{
    protected $fillable = ['old_element_id', 'new_element_id', 'name', 'company_id','name'];
}

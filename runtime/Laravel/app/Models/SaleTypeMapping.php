<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleTypeMapping extends Model
{
    protected $fillable = [ 'company_id', 'name','old_sale_type_id','new_sale_type_id'];
}

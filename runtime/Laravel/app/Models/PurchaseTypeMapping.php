<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseTypeMapping extends Model
{
    protected $fillable = [ 'company_id', 'name','old_purchase_type_id','new_purchase_type_id'];
}

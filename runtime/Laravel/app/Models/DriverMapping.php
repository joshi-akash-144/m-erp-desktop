<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverMapping extends Model
{
    protected $fillable = [ 'company_id', 'name','old_driver_id','new_driver_id'];
}

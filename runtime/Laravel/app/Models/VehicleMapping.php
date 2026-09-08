<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleMapping extends Model
{
    protected $fillable = [ 'company_id', 'name','old_vehicle_id','new_vehicle_id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DieselItem extends Model
{
    protected $guarded = ['id'];

    public function diesel()
    {
        return $this->belongsTo(Diesel::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

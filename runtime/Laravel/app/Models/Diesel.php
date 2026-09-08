<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diesel extends Model
{
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DieselItem::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

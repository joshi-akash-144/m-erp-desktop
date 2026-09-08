<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Moisture extends Model
{
    use HasFactory, SoftDeletes;


    protected $guarded = ['id'];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function godownModule()
    {
        return $this->belongsTo(GodownModule::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'godown_name',
        'ip_address',
        'url',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Weight locations are global and do not belong to a specific company
    // public function company()
    // {
    //     return $this->belongsTo(Company::class, 'company_id', 'id');
    // }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}

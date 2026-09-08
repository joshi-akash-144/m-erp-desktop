<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class FinancialYear extends BaseMaster
{
    protected $fillable = [
        'name',
        'uuid',
        'start_date',
        'end_date',
        'is_current',
        'created_by',
        'status',
        'company_id',
        'updated_by'
    ];

    /**
     * Scope for active years.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }


    protected static function booted()
    {
        parent::booted(); 
        
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });


        static::deleting(function ($model) {
            if (Auth::check() && $model->usesSoftDeletes()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }


    public function usesSoftDeletes()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
    }
}

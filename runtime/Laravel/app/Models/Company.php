<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Company extends BaseMaster
{
    public const TYPE_OF_DEALER = ['registered', 'unregistered', 'composition', 'uni_holder'];

    const TRADING = 'TRADING';
    const TRANSPORT = 'TRANSPORT';
    const MANUFACTURING = 'MANUFACTURING';

    protected $fillable = [
        'uuid',
        'name',
        'print_name',
        'legal_name',
        'country_id',
        'state_id',
        'address_one',
        'address_two',
        'cin',
        'pan',
        'tin',
        'code',
        'mobile_number',
        'phone_number',
        'email',
        'gst_number',
        'type_of_dealer',
        'tds_applicable',
        'status',
        'created_by',
        'updated_by',
        'financial_year_start'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Company belongs to a country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // Company belongs to a state
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Financial years of the company
    public function financialYears()
    {
        return $this->hasMany(FinancialYear::class);
    }

    // Current financial year
    public function currentFinancialYear()
    {
        return $this->hasOne(FinancialYear::class)->where('is_current', true);
    }

    // Year closing batches
    public function yearClosingBatches()
    {
        return $this->hasMany(YearClosingBatch::class);
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->where('status', true);
    }

    // public function accountGroups()
    // {
    //     return $this->belongsToMany(AccountGroup::class, 'company_account_group');
      
    // }

    public function gstCredentials()
    {
        return $this->hasMany(CompanyGstCredential::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'company_modules')
            ->withPivot(['is_active', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function activeModules()
    {
        return $this->belongsToMany(Module::class, 'company_modules')
            ->wherePivot('is_active', true)
            ->withPivot(['is_active', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }
}

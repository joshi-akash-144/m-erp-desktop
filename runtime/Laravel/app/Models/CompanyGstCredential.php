<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyGstCredential extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'sandbox_client_id',
        'sandbox_base_url',
        'sandbox_secret_id',
        'sandbox_gstin',
        'sandbox_email',
        'sandbox_username',
        'sandbox_password',
        'production_client_id',
        'production_base_url',
        'production_secret_id',
        'production_gstin',
        'production_email',
        'production_username',
        'production_password',
        'created_by',
        'updated_by',
    ];

    public function getSandboxPasswordAttribute($value)
    {
        if (!$value) return null;
        try { return decrypt($value); } catch (\Exception $e) { return null; }
    }

    public function setSandboxPasswordAttribute($value)
    {
        $this->attributes['sandbox_password'] = $value ? encrypt($value) : null;
    }

    public function getProductionPasswordAttribute($value)
    {
        if (!$value) return null;
        try { return decrypt($value); } catch (\Exception $e) { return null; }
    }

    public function setProductionPasswordAttribute($value)
    {
        $this->attributes['production_password'] = $value ? encrypt($value) : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

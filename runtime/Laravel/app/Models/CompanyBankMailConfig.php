<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBankMailConfig extends Model
{
    protected $fillable = [
        'company_id',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port'      => 'integer',
    ];

    public function getPasswordAttribute($value)
    {
        if (!$value) return null;
        try { return decrypt($value); } catch (\Exception $e) { return null; }
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? encrypt($value) : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

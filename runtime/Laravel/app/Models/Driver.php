<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends BaseMaster
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'company_id',
        'vehicle_id',
        'date_of_joining',
        'license_number',
        'adhara_number',
        'religion',
        'qualification',
        'marital_status',
        'blood_group',
        'salary',
        'status',
        'license_category',
        'license_issuing_authority',
        'license_expiry_date_tr',
        'license_expiry_date_nt',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
   

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    
}

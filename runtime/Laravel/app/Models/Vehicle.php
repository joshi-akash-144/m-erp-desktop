<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Enums\FuelType;
use Illuminate\Database\Eloquent\SoftDeletes;   

class Vehicle extends BaseMaster
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        // 'license_number',
        'renewal_date',
        'model',
        'mfg_year',
        'manufacturer',
        'chassis_no',
        'engine_no',
        'fuel_type',
        'fuel_tank_capacity',
        'gross_weight',
        'unladen_weight',
        'weight_capacity',
        'account_id',
        'vehicle_owner_id',
        // 'driver_id',
        'status',
        'national_permit_due_date',
        'fitness_due_date',        
        'policy_due_date',
        'passing_due_date',
        'tax_due_date',
        'permit_due_date',
        'puc_no',
        'puc_due_date', 
        'insurance_company_name',
        'insurance_policy_no',
        'power_cc',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    
    public function vehicleOwner()
    {
        return $this->belongsTo(VehicleOwner::class, 'vehicle_owner_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }   
}

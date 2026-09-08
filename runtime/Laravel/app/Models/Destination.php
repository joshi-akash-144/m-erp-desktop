<?php

namespace App\Models;

use App\Traits\HasCompanyContext;


class Destination extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'name',
        'contact_person_name',
        'email',
        'mobile_number',
        'phone_number',
        'kms',
        'address_one',
        'address_two',
        'city',
        'district',
        'taluka',
        'state_id',
        'country_id',
        'postal_code',
        'company_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function DestinationName()
    {
        return $this->hasMany(Godown::class, 'destination_id');
    }

    
}

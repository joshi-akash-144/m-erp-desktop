<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class Broker extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'print_name',
        'pan',
        'mobile_number',
        'email',
        'country_id',
        'state_id',
        'city',
        'postal_code',
        'address_one',
        'address_two',
        'sale_commission_rate',
        'purchase_commission_rate',
        'bank_name',
        'bank_branch_name',
        'bank_account_number',
        'bank_ifsc',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status'                  => 'boolean',
        'sale_commission_rate'    => 'decimal:4',
        'purchase_commission_rate'=> 'decimal:4',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}

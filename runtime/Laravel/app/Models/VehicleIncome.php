<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleIncome extends Model
{
    protected $fillable = [ 
        'voucher_id',
        'company_id',
        'financial_year_id',
        'freight_id',
        'income_account_id',
        'amount',
        'vehicle_id',
        'income_date',
        'voucher_date'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function freight()
    {
        return $this->belongsTo(Freight::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'voucher_id',
        'voucher_date',
        'vehicle_id',
        'account_id', //Driver Id
        'driver_silak_balance',
        'narration',
        'start_kms',
        'end_kms',
        'total_kms',
        'start_diesel',
        'end_diesel',
        'diesel_average',
        'idle_days',
        'idle_day_wage',
        'idle_day_wage_amount',
        'expense_total',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'voucher_date'         => 'date',
        'driver_silak_balance' => 'decimal:2',
        'start_kms'            => 'decimal:2',
        'end_kms'              => 'decimal:2',
        'total_kms'            => 'decimal:2',
        'start_diesel'         => 'decimal:2',
        'end_diesel'           => 'decimal:2',
        'diesel_average'       => 'decimal:2',
        'idle_day_wage'        => 'decimal:2',
        'idle_day_wage_amount' => 'decimal:2',
        'expense_total'        => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'account_id');
    }

    public function driverAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function items()
    {
        return $this->hasMany(DriverExpenseItem::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

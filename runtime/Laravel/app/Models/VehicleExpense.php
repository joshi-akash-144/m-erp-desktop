<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleExpense extends Model
{
    protected $fillable = [
        'voucher_id',
        'company_id',
        'financial_year_id',
        'vehicle_id',
        'expense_account_id',
        'amount',
        'bill_date',
        'voucher_date',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function voucherTransaction()
    {
        return $this->hasOne(VoucherTransaction::class, 'voucher_id', 'voucher_id')
                    ->where('credit', '>', 0);
    }
}

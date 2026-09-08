<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverExpenseItem extends Model
{
    protected $fillable = [
        'driver_expense_id',
        'billing_date',
        'expense_account_id',
        'from_destination_id',
        'to_destination_id',
        'item_id',
        'dc_lr',
        'rate',
        'bags',
        'weight',
        'trips',
        'amount',
        'remark',

    ];

    protected $casts = [
        'item_date' => 'date',
        'bags'      => 'decimal:2',
        'weight'    => 'decimal:2',
        'amount'    => 'decimal:2',
    ];

    public function driverExpense()
    {
        return $this->belongsTo(DriverExpense::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function fromDestination()
    {
        return $this->belongsTo(Destination::class, 'from_destination_id');
    }

    public function toDestination()
    {
        return $this->belongsTo(Destination::class, 'to_destination_id');
    }
}

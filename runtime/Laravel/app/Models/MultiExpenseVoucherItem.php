<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultiExpenseVoucherItem extends Model
{
    protected $fillable = [
        'multi_expense_voucher_id',
        'voucher_id',
        'bill_no',
        'bill_date',
        'vehicle_id',
        'amount',
        'challan_number',
        'remark',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'amount'    => 'decimal:2',
    ];

    public function multiExpenseVoucher()
    {
        return $this->belongsTo(MultiExpenseVoucher::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}

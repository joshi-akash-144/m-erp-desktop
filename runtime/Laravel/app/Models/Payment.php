<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'bank_id',
        'payment_date',
        'mode',
        'amount',
        'cheque_number',
        'cheque_date',
        'cheque_alpha_number',
        'cheque_time',
        'utr_number',
        'status',
        'cheque_name',
        'ac_pay',
        'rtgs',
        'cheque_status',
        'cancelled_by',
        'cancelled_at',
    ];

    // relation with payment voucher with has many
    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucher extends Model
{
    use SoftDeletes;

    const ENTRY_FROM_PAYMENT_VOUCHER = 'payment_voucher';
    const ENTRY_FROM_PAYMENT_PAYABLE = 'payment_payable';
    protected $fillable = [
        'voucher_id',
        'company_id',
        'financial_year_id',
        'paid_amount',
        'bank_id',
        'is_paid',
        'is_approved',
        'is_hold',
        'account_id',
        'file_number',
        'mode',
        'entry_from',
        'payment_id',
        'is_pass_to_rtgs','approved_by','approved_at',
        'is_transport_payment',
        'transport_payment_release_id',
        'is_voucher_only',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function bank()
    {
        return $this->belongsTo(Account::class, 'bank_id');
    }
}

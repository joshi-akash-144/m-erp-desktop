<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPaymentRelease extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'payment_date',
        'bank_id',
        'total_amount',
        'created_by',
    ];

    public function bank()
    {
        return $this->belongsTo(Account::class, 'bank_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class, 'transport_payment_release_id');
    }
}

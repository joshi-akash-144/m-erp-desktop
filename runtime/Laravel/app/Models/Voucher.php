<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends BaseMaster
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'voucher_type_id',
        'voucher_serial',
        'voucher_number',
        'voucher_date',
        'reference_number',
        'source_type',
        'source_id',
        'is_active', 
        'created_by',
        'updated_by',
        'deleted_by',
        'is_opening',
        'status',
        'narration',
        'secondary_narration'
    ];


    public function details()
    {
        return $this->hasMany(VoucherTransaction::class, 'voucher_id', 'id');
    }

    public function references()
    {
        return $this->hasMany(Reference::class, 'voucher_id', 'id');
    }

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id', 'id');
    }

    public function paymentVoucher()
    {
        return $this->hasOne(PaymentVoucher::class, 'voucher_id', 'id');
    }
}

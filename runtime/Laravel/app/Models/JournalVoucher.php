<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use SoftDeletes;
    const ENTRY_FORM_VOUCHER = 'voucher';
    const TRANSPORT_EXP_VOUCHER = 'transport_expense_voucher';

    protected $fillable = [
        'voucher_id',
        'company_id',
        'financial_year_id',
        'vehicle_id',
        'gst_nature',
        'entry_from',
        'bill_date',
    ];

}
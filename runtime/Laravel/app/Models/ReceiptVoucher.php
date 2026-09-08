<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptVoucher extends Model
{
    use SoftDeletes;
    
    const ENTRY_FROM_RECEIPT_VOUCHER = 'receipt_voucher';
    const ENTRY_FROM_RECEIPT_RECEIVABLE = 'receipt_receivable';
    protected $fillable = [
        'voucher_id',
        'company_id',
        'financial_year_id',
        'received_amount',
        // 'bank_id',
        'is_received',
        'is_approved',
        'is_hold',
        // 'account_id',
        'mode',
        'entry_from'
    ];

}
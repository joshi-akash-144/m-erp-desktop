<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNoteVoucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'voucher_id',
        'company_id',
        'financial_year_id',
        'gst_nature',
        'entry_from',
        'bill_date',
    ];
}

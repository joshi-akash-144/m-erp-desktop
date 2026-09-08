<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTransaction extends Model
{
    protected $fillable = [
        'voucher_id',
        'account_id',
        'debit',
        'credit',
        'narration',
        'is_party_account',
        'line_no',
        'against_account_id'
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

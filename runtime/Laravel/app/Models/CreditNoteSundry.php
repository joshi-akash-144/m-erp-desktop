<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteSundry extends Model
{
    protected $table = 'credit_note_sundries';

    protected $fillable = [
        'credit_note_id',
        'sundry_id',
        'code',
        'name',
        'bill_sundry_type',
        'calculation_type',
        'apply_on',
        'bill_sundry_modal_dr_id',
        'bill_sundry_modal_cr_id',
        'base_amount',
        'rate_percent',
        'value',
        'amount',
        'sort_order',
        'remarks',
        'affect_net_total',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function sundry()
    {
        return $this->belongsTo(BillSundry::class, 'sundry_id');
    }

    public function drAccount()
    {
        return $this->belongsTo(Account::class, 'bill_sundry_modal_dr_id');
    }

    public function crAccount()
    {
        return $this->belongsTo(Account::class, 'bill_sundry_modal_cr_id');
    }
}

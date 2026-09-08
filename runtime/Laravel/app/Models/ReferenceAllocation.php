<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceAllocation extends Model
{
    const AGAINST_REF = 'against_ref'; // again ref adjustment
    const NewRef = 'new_ref';
    const ADVANCE_ADJUSTMENT = 'advance'; // advance adjustment
    const OnAccount = 'on_account';

    const SourceType = [
        'purchase_invoice',
        'sales_invoice',
        'payment',
        'receipt',
        'journal',
        'debit_note',
        'credit_note',
    ];

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'voucher_id',
        'reference_id',
        'amount',
        'allocation_type',
        'reference_number',
        'account_id',
        'source_type',
        'source_id',
        'created_at',
        'updated_at'
    ];

    public function reference()
    {
        return $this->belongsTo(Reference::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}

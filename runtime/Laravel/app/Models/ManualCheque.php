<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ManualCheque extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'account_id',
        'cheque_format_id',
        'name',
        'amount',
        'cheque_date',
        'cheque_no',
        'narration',
        'second_narration',
        'account_payee',
        'rtgs',
        'is_approved',
        'approved_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];    

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function chequeFormat()
    {
        return $this->belongsTo(ChequeMaster::class, 'cheque_format_id');
    }
}

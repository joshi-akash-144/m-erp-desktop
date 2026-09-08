<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountYearBalance extends Model
{
    use SoftDeletes;

    public const OPENING_TYPE = ['D', 'C'];

    protected $fillable = [
        'company_id', 'account_id', 'financial_year_id', 'opening_balance',
        'opening_type', 'closing_balance', 'closing_type'
    ];

    // Relation
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

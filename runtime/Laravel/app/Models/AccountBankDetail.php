<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountBankDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'bank_beneficiary_name', 'bank_name', 'bank_branch_name',
        'bank_account_number', 'bank_ifsc', 'bank_account_type','is_default_bank','rtgs_form_view_id'
    ];

    // Relation
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

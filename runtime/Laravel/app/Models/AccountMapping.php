<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountMapping extends Model
{

    // protected $table = 'account_mappings';

    protected $fillable = [
        'old_account_id',
        'new_account_id',
        'company_id',
        'old_account_group_id',
        'new_account_group_id',
        'old_account_name',
        'account_type',
    ];
}

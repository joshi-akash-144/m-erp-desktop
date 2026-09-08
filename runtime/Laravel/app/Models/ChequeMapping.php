<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeMapping extends Model
{
    protected $fillable = [
        'company_id',
        'new_payment_id',
        'old_payment_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GodownMapping extends Model
{
    protected $fillable = [
        'company_id',
        'new_godown_id',
        'old_godown_id',
        'name'
    ];
}

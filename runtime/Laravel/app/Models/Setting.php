<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_id',
        'module',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}

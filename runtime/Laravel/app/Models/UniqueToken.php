<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueToken extends Model
{
    protected $fillable = [
        'unique_request_id',
    ];
}

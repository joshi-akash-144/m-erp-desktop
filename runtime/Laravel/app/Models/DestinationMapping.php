<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestinationMapping extends Model
{
    protected $fillable = [
        'company_id',
        'new_destination_id',
        'old_destination_id',
        'name'
    ];
}

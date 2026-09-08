<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'template_name',
        'subject',
        'message',
        'status',
        'created_by',
        'updated_by',
    ];
}

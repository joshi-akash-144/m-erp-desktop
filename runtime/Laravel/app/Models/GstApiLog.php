<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GstApiLog extends Model
{
    protected $fillable = [
        'company_id',
        'service',
        'method',
        'endpoint',
        'request_payload',
        'response_payload',
        'status_code',
        'is_success',
        'error_message',
        'duration_ms',
        'created_by',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'is_success'       => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    // Record Types
    const RECORD_TYPE_MASTER = 1;
    const RECORD_TYPE_VOUCHER = 2;

    // Actions
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_RESTORE = 'restore';
    const ACTION_APPROVE = 'approve';
    const ACTION_CANCEL = 'cancel';
    const ACTION_POST = 'post';
    const ACTION_UNPOST = 'unpost';
    const ACTION_PRINT = 'print';
    const ACTION_EXPORT = 'export';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';

    // Status
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 0;

    // HTTP Methods
    const HTTP_METHOD_GET = 1;
    const HTTP_METHOD_POST = 2;
    const HTTP_METHOD_PUT = 3;
    const HTTP_METHOD_DELETE = 4;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'action',
        'module',
        'record_type',
        'model_name',
        'record_id',
        'voucher_id',
        'reference_number',
        'version',
        'user_id',
        'user_name',
        'user_email',
        'ip_address',
        'user_agent',
        'request_url',
        'http_method',
        'org_amount',
        'final_amount',
        'description',
        'status',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('action', $type);
    }

    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('performed_at', [$start, $end]);
    }

    public function details()
    {
        return $this->hasMany(AuditTrailDetail::class);
    }
}

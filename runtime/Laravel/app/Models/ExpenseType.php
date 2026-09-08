<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class ExpenseType extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'expense_type_group_id',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}

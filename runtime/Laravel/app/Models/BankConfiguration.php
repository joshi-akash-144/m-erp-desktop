<?php

namespace App\Models;
use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankConfiguration extends BaseMaster
{
     use HasCompanyContext;

    protected $fillable = [
        'uuid',
        'company_id',
        'bank_id',
        'rtgs_id',
        'cheque_id',
        'status',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function bankName()
    {
        return $this->belongsTo(AccountBankDetail::class, 'bank_id');
    }

}

<?php

namespace App\Models;

use App\Models\BaseMaster;
use App\Traits\HasCompanyContext;

class PurchaseType extends BaseMaster
{
    use HasCompanyContext;

    public const TAXABLE_TYPES = ['taxable', 'exempt', 'nil_rated', 'zero_rated', 'non_gst'];
    public const TRANSACTION_TYPES = ['domestic', 'import'];
    public const REGIONS = ['local', 'interstate'];


    protected $fillable = [
        'uuid', 'company_id', 'name', 'account_id',
        'taxation_type', 'transaction_type', 'region',
        'cgst', 'sgst', 'igst', 'status','is_active','created_by','updated_by','deleted_by'
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

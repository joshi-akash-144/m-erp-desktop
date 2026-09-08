<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class TaxCategory extends BaseMaster
{
    use HasCompanyContext;

    public const TYPES = ['goods', 'services'];
    public const ZERO_TAX_TYPE = ['exempt', 'zero_rated','non_gst','nil_rated','taxable'];

    protected $fillable = [
        'code','uuid', 'company_id', 'name', 'type', 'cgst', 'sgst', 'igst','zero_tax_type', 'status','is_active','created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'cgst' => 'decimal:2',
        'sgst' => 'decimal:2',
        'igst' => 'decimal:2',
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accounts()
    {
        return $this->hasMany(AccountTaxDetail::class, 'tax_category_id');
    }
}

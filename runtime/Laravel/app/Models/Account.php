<?php

namespace App\Models;

use App\Traits\HasCompanyContext;

class Account extends BaseMaster
{
    use HasCompanyContext;

    // public const IS_BILLWISE = ['yes', 'no'];
    public const CUSTOMER_TYPE = 'customer';
    public const SUPPLIER_TYPE = 'supplier';
    public const BROKER_TYPE = 'broker';
    public const ACCOUNT_TYPE = 'account';
    
    public const PARTY_TYPE = ['customer', 'supplier', 'broker', 'account'];
    const GST_TYPE_LOCAL = 'local';
    const GST_TYPE_INTERSTATE = 'interstate';

    protected $fillable = [
        'uuid', 'company_id', 'account_group_id', 'code', 'name', 'print_name', 'city','postal_code',
        'address_one', 'address_two', 'mobile_number','whatsapp_number','email','is_billwise','state_id','country_id','is_active', 'created_by', 'updated_by', 'deleted_by','party_type','gst_type','is_active', 'is_hidden', 'cheque_master_id', 'rtgs_form_id'
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    protected $casts = [
        'is_billwise' => 'boolean',
    ];

    // Relations
    public function bankDetail()
    {
        return $this->hasOne(AccountBankDetail::class);
    }

    public function taxDetail()
    {
        return $this->hasOne(AccountTaxDetail::class);
    }


    public function preference()
    {
        return $this->hasOne(AccountPreference::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class);
    }

    
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function taxCategory(){
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    public function yearBalances()
    {
        return $this->hasMany(AccountYearBalance::class, 'account_id');
    }

    public function currentYearBalance()
    {
        return $this->hasOne(AccountYearBalance::class, 'account_id')
                    ->where('financial_year_id', session('financial_year_id'));
    }

    public function group()
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }
    public function bankDetails()
    {
        return $this->hasMany(AccountBankDetail::class, 'account_id');
    }

    public function sales_invoices()
    {
        return $this->hasMany(SalesInvoice::class, 'account_id');
    }

}

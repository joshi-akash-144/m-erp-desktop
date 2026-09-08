<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Freight extends BaseMaster
{
    use SoftDeletes;

    const ENTRY_FROM_VOUCHER = 'voucher';
    const ENTRY_FROM_INVOICE = 'invoice';
    const ENTRY_FROM_INVOICE2 = 'invoice2';
    
    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'account_id',
        'voucher_id',
        'vehicle_id',
        'consignor_id',
        'consignee_id',
        'from_destination_id',
        'to_destination_id',
        'grn_serial',
        'lr_number',
        'invoice_serial',
        'invoice_number',
        'prefix',
        'invoice_date',
        'from_date',
        'to_date',
        'total_amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
        'entry_from',
        'reference_number'
    ];

    protected $casts = [
        'status'       => 'boolean',
        'invoice_date' => 'date',
        'from_date'    => 'date',
        'to_date'      => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function consignor()
    {
        return $this->belongsTo(TransportParty::class, 'consignor_id');
    }

    public function consignee()
    {
        return $this->belongsTo(TransportParty::class, 'consignee_id');
    }

    public function fromDestination()
    {
        return $this->belongsTo(Destination::class, 'from_destination_id');
    }

    public function toDestination()
    {
        return $this->belongsTo(Destination::class, 'to_destination_id');
    }

    public function items()
    {
        return $this->hasMany(FreightItem::class, 'freight_id');
    }

    public function contractorItems()
    {
        return $this->hasMany(FreightContractorItem::class, 'freight_id');
    }
}

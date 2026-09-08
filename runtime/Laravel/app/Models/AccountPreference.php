<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountPreference extends Model
{
    use SoftDeletes;

    public const TRANSPORT_MODE = ['road', 'air', 'rail', 'ship'];

    protected $fillable = [
        'account_id', 'transport_mode', 'sale_type_id', 'purchase_type_id','distance',
        'station','contact_person','transport','purchase_unit_id','sale_unit_id','purchase_commission_rate','sale_commission_rate'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id');
    }

    public function purchaseType()
    {
        return $this->belongsTo(PurchaseType::class, 'purchase_type_id');
    }

    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }
}

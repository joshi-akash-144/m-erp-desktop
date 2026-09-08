<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasCompanyContext, SoftDeletes;

    // Delivery days default
    const DEFAULT_DELIVERY_DAYS = 15;

    // Tax types
    const TAX_LOCAL       = 'local';
    const TAX_INTERSTATE  = 'interstate';
    const GST_TYPE        = [self::TAX_LOCAL, self::TAX_INTERSTATE];

    // Statuses
    const STATUS_OPEN   = 'open';
    const STATUS_CLOSE  = 'close';
    const STATUS_CANCEL = 'cancel';
    const STATUS_HOLD   = 'hold';
    // const STATUS_DUE   = 'due';

    protected $fillable = [
        'uuid',
        'order_number',
        'financial_year_id',
        'order_serial',
        'order_no',
        'company_id',

        'purchase_order_number',
        'purchase_order_date',

        'account_id',
        'gst_type',

        'broker_id',
        'delivery_date',
        'delivery_days',
        'due_date',

        'remarks',
        'status',
        'grand_total',
        'total_tax',

        'total_quantity',
        'remaining_quantity',
        'received_quantity',

        'sub_total',
        'discount_amount',
        'round_off',
        'order_status',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_skip_serial_generation',
    ];

    /* -----------------------------------------
     | Relationships
     |------------------------------------------
     */

    public function details()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id')->with('item', 'condition', 'destination');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function items()
    {
        return $this->hasManyThrough(
            Item::class,
            SalesOrderItem::class,
            'sales_order_id',
            'id',
            'id',
            'item_id'
        );
    }
    public function sales_order_items()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id');
    }
}

<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PurchaseOrderItem;

class PurchaseOrder extends Model
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
    const STATUS_DUE   = 'due';
    
    protected $fillable = [
        'uuid',
        'order_number',
        'financial_year_id',
        'order_serial',
        'order_no',
        'company_id',
        'account_id',
        'gst_type',
        'destination_id',
        'broker_id',
        'order_date',
        'due_date',
        'delivery_days',
        'contract_number',
        'remarks',
        'status',
        'grand_total',
        'total_tax',
        'total_quantity',
        'sub_total',
        'discount_amount',
        'round_off',
        'order_status',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_skip_serial_generation',
    ];

    public function getOrderStatusAttribute($value)
    {
        if ($value === self::STATUS_OPEN && $this->due_date && $this->due_date < date('Y-m-d')) {
            return self::STATUS_DUE;
        }
        return $value;
    }

    /* -----------------------------------------
     | Relationships
     |------------------------------------------
     */

    public function details()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
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

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function items()
    {
        return $this->hasManyThrough(
            Item::class,
            PurchaseOrderItem::class,
            'purchase_order_id',
            'id',
            'id',
            'item_id'
        );
    }
    public function purchase_order_items()
    {       
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }
    // A Purchase Order can have many GRN items through its order_no
    public function grnItems()
    {
        return $this->hasMany(GrnItem::class, 'purchase_order_id', 'id');
    }
    
    // You might also want a direct relation to the GRNs via the items
    public function grns()
    {
        return $this->hasManyThrough(
            Grn::class, 
            GrnItem::class, 
            'purchase_order_id', // Foreign key on grn_items table
            'id',       // Foreign key on grns table (assuming Grn uses 'id' as primary key)
            'order_serial', // Local key on purchase_orders table
            'grn_id'    // Local key on grn_items table (assuming GrnItem links to Grn via grn_id)
        );
    }
}

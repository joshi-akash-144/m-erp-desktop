<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallanItem extends Model
{
    protected $fillable = [
        'delivery_challan_id',
        'item_id',
        'condition_id',
        'destination_id',
        'sales_order_id',
        'sales_order_item_id',
        'sales_order_serial',
        'quantity',
        'party_quantity',
        'bag_count',
        'rate',
        'inclusive_rate',
        'taxable_amount',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'amount',
        'net_amount',
        'grand_total',
        'remarks',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'party_quantity'  => 'decimal:4',
        'rate'            => 'decimal:2',
        'inclusive_rate'  => 'decimal:2',
        'taxable_amount'  => 'decimal:2',
        'cgst_rate'       => 'decimal:2',
        'sgst_rate'       => 'decimal:2',
        'igst_rate'       => 'decimal:2',
        'cgst_amount'     => 'decimal:2',
        'sgst_amount'     => 'decimal:2',
        'igst_amount'     => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'amount'          => 'decimal:2',
        'net_amount'      => 'decimal:2',
        'grand_total'     => 'decimal:2',
    ];

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightInvoiceItemOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'item_id',
        'sort_order',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

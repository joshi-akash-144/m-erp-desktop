<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class DairyImport extends BaseMaster
{
    const DAIRY_FILE = 1;
    const DAY_TO_DAY_FILE = 2;
    const FREIGHT_ENTRY = 3;

    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'uuid',
        'import_date',
        'product_id',
        'is_used',
        'remarks',
        'import_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function product()
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    public function items()
    {
        return $this->hasMany(DairyImportItem::class);
    }
}

<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Penalty extends Model
{
    use SoftDeletes, HasCompanyContext;

    protected $table = 'penalties';

    protected $fillable = [
        'uuid',
        'item_id',
        'days',
        'amount',
        'company_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function booted()
    {
        static::creating(function ($penalty) {
            if (empty($penalty->uuid)) {
                $penalty->uuid = (string) Str::uuid();
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

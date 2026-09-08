<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Unit extends BaseMaster
{
    use HasCompanyContext;

    protected $fillable = [
        'name','print_name', 'uqc','code','uuid', 'company_id','is_active', 'created_by', 'updated_by', 'deleted_by',
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }


    public function mainUnitConversion(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'main_unit_id');
    }

    public function subUnitConversion(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'sub_unit_id');
    }

    public static function nextCode(): int
    {
        $maxCode = self::withTrashed()
            ->where('company_id',session('company_id'))
            ->max('code');
        
        if (!$maxCode) {
            return 1000;
        }
        
        if (!$maxCode) {
            throw new \Exception("Error In Code Generation.");
        }

        return $maxCode + 1;
    }
}

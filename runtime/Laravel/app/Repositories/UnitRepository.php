<?php

namespace App\Repositories;

use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitRepository extends BaseRepository
{
    public function __construct(Unit $unit)
    {
        parent::__construct($unit);
    }

    private function hasMainUnitConversion(Unit $unit):bool
    {
        return $unit->mainUnitConversion()->exists();
    }

    private function hasSubUnitConversion(Unit $unit):bool
    {
        return $unit->subUnitConversion()->exists();
    }

    public function canDelete(Unit $unit): bool
    {
        return ($this->hasMainUnitConversion($unit) || $this->hasSubUnitConversion($unit));
    }
    
     /**
     * Generate the next sequential code for a record in the given company.
     *
     * This method retrieves the current maximum `code` for the specified
     * company and returns the next number in sequence. If no record exists,
     * it starts with a default prefix value.
     *
     * @param  int  $companyId  The ID of the company to generate the code for.
     * @return int  The next sequential code.
     */
    public function nextCode(int $companyId): int
    {
        $prefix = 1000;

        $maxCode = $this->model->where('company_id', $companyId)->max('code');

        return $maxCode ? $maxCode + 1 : $prefix + 1;
    }

}

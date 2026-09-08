<?php

namespace App\Repositories;

use App\Models\FinancialYear;

class FinancialYearRepository extends BaseRepository
{
    public function __construct(FinancialYear $financialYear)
    {
        parent::__construct($financialYear);
    }
}

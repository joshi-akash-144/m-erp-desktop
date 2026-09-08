<?php

namespace App\Repositories;

use App\Models\UnitConversion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitConversionRepository extends BaseRepository
{
       /**
     * AccountGroupRepository constructor.
     *
     * @param UnitConversion $accountGroup
     */
    public function __construct(UnitConversion $UnitConversion)
    {
        parent::__construct($UnitConversion);
    }

}

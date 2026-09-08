<?php

namespace App\Repositories;

use App\Models\GodownUnitLocation;

class GodownUnitLocationRepository extends BaseRepository
{
    public function __construct(GodownUnitLocation $godownUnitLocation)
    {
        parent::__construct($godownUnitLocation);
    }
}

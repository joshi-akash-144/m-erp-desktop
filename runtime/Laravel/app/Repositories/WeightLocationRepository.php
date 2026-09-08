<?php

namespace App\Repositories;

use App\Models\WeightLocation;

class WeightLocationRepository extends BaseRepository
{
    /**
     * WeightLocationRepository constructor.
     *
     * @param WeightLocation $weightLocation
     */
    public function __construct(WeightLocation $weightLocation)
    {
        parent::__construct($weightLocation);
    }
}

<?php

namespace App\Repositories;

use App\Models\Driver;

class DriverRepository extends BaseRepository
{
    public function __construct(Driver $driver)
    {
        parent::__construct($driver);
    }
}

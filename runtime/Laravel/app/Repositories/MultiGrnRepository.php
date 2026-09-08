<?php

namespace App\Repositories;

use App\Models\MultiGrn;
use App\Repositories\BaseRepository;

class MultiGrnRepository extends BaseRepository
{
    public function __construct(MultiGrn $multiGrn)
    {
        parent::__construct($multiGrn);
    }
}

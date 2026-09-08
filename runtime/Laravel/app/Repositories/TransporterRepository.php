<?php

namespace App\Repositories;

use App\Models\Transporter;

class TransporterRepository extends BaseRepository
{
    public function __construct(Transporter $model)
    {
        parent::__construct($model);
    }
}

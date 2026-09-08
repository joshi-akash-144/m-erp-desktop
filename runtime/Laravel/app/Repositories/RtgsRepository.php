<?php

namespace App\Repositories;

use App\Models\RtgsFormView;

class RtgsRepository extends BaseRepository
{

    public function __construct(RtgsFormView $rtgsFormView)
    {
        parent::__construct($rtgsFormView);
    }
}

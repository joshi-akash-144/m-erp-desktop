<?php

namespace App\Repositories;

use App\Models\State;

class StateRepository  extends BaseRepository
{
    public function __construct(State $state)
    {
        parent::__construct($state);
    }
}
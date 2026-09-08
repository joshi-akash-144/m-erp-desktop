<?php

namespace App\Repositories;

use App\Models\TransportParty;

class TransportPartyRepository extends BaseRepository
{
    public function __construct(TransportParty $transportParty)
    {
        parent::__construct($transportParty);
    }
}

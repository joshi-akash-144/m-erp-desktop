<?php

namespace App\Repositories;

use App\Models\Destination;

class DestinationRepository extends BaseRepository
{
    public function __construct(Destination $destination)
    {
        parent::__construct($destination);
    }

    private function hasDestinationName(Destination $destination): bool
    {
        return $destination->destinationName()->exists();
    }

    public function canDelete(Destination $destination): bool
    {
        return $this->hasDestinationName($destination);
    }
}

<?php

namespace App\Repositories;

use App\Models\VehicleOwner;
use App\Models\Vehicle;

class VehicleOwnerRepository extends BaseRepository
{
    public function __construct(VehicleOwner $vehicleOwner)
    {
        parent::__construct($vehicleOwner);
    }

    private function hasVehicleOwnerId(VehicleOwner $vehicleOwner):bool
    {
        return Vehicle::where('vehicle_owner_id', $vehicleOwner->id)->exists();
    }

    public function canDelete(VehicleOwner $vehicleOwner): bool
    {
        return $this->hasVehicleOwnerId($vehicleOwner);
    }
}

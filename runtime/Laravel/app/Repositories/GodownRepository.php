<?php

namespace App\Repositories;

use App\Models\Godown;
use App\Models\Account;
use App\Models\GodownUnitLocation;
use App\Models\WeightLocation;
use App\Models\Item;
use App\Models\Destination;

class GodownRepository extends BaseRepository
{
    public function __construct(Godown $godown)
    {
        parent::__construct($godown);
    }

    /**
     * Fetch all brokers for a specific company
     */
    public function getBrokers(int $companyId)
    {
        return Account::where('company_id', $companyId)
            ->where('party_type', Account::BROKER_TYPE)
            ->orderBy('name')
            ->get();
    }

    /**
     * Fetch all items for a specific company
     */
    public function getItems(int $companyId)
    {
        return Item::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Fetch all destinations for a specific company
     */
    public function getDestinations(int $companyId)
    {
        return Destination::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Fetch all Godowns for a specific destination
     */
    public function getGodowns(int $destinationId)
    {
        return GodownUnitLocation::where('destination_id', $destinationId)
            ->orderBy('godown_name')
            ->get();
    }

    /**
     * Fetch all Godowns for a specific company
     */
    public function getAllGodowns(int $companyId)
    {
        return GodownUnitLocation::whereHas('destination', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->orderBy('godown_name')->get();
    }

    /**
     * Fetch all active weight locations
     */
    public function getWeightLocations()
    {
        return WeightLocation::where('status', true)
            ->orderBy('godown_name')
            ->get();
    }

    /**
     * Fetch all suppliers for a specific company
     */
    public function getSuppliers(int $companyId)
    {
        return Account::where('company_id', $companyId)
            ->where('party_type', Account::SUPPLIER_TYPE)
            ->orderBy('name')
            ->get();
    }
}

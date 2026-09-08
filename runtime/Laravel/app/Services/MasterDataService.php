<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Grn;
use App\Repositories\CommonRepository;
use Illuminate\Support\Collection;

class MasterDataService
{
    protected CommonRepository $commonRepo;

    public function __construct(CommonRepository $commonRepo)
    {
        $this->commonRepo = $commonRepo;
    }

    /**
     * Fetch master data dynamically
     * $type = 'accounts', 'brokers', 'units', etc.
     * $companyId required for company-specific data
     * $columns optional, defaults to ['id','name']
     */
    public function get(string $type, int $companyId = 0, array $columns = ['id', 'name']): Collection
    {
        return match ($type) {
            'accounts'      => $this->commonRepo->getAccounts($companyId, $columns),
            'suppliers'     => $this->commonRepo->getSuppliers($companyId, $columns),
            'accountGroups' => $this->commonRepo->getAccountGroups($companyId, $columns),
            'brokers'       => $this->commonRepo->getBrokers($companyId, $columns),
            'units'         => $this->commonRepo->getUnits($companyId, $columns),
            'taxCategories' => $this->commonRepo->getTaxCategories($companyId, $columns),
            'conditions'    => $this->commonRepo->getConditions($companyId, $columns),
            'items'         => $this->commonRepo->getItems($companyId, $columns),
            'destinations'  => $this->commonRepo->getDestinations($companyId, $columns),
            'godown_units'  => $this->commonRepo->getGodownUnits($companyId, $columns === ['id', 'name'] ? ['id', 'godown_name'] : $columns),
            'weight_locations' => $this->commonRepo->getWeightLocations($companyId, $columns === ['id', 'name'] ? ['id', 'godown_name'] : $columns),
            'countries'     => $this->commonRepo->getCountries($columns),
            'states'        => $this->commonRepo->getStates($columns),
            'financialYears' => $this->commonRepo->getFinancialYears($columns),
            'purchaseTypes' => $this->commonRepo->getPurchaseTypes($companyId, $columns),
            'customers'     => $this->commonRepo->getCustomers($companyId, $columns),
            'transporters'  => $this->commonRepo->getTransporters($companyId, $columns),
            'vehicles'      => $this->commonRepo->getVehicles($companyId, $columns === ['id', 'name'] ? ['id', 'name as vehicle_number'] : $columns),
            'transportParties'      => $this->commonRepo->getTransportParty($companyId,['id', 'name','city']),
            default         => collect([]),
        };
    }

    public function purchaseOrderMasterData(int $companyId = 0): array
    {
        return [
            'accounts' => $this->get('suppliers', $companyId, ['id', 'name', 'city'])
                ->merge($this->get('customers', $companyId, ['id', 'name', 'city'])),
            'brokers'       => $this->get('brokers', $companyId, ['id', 'name', 'city']),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations'        => $this->get('destinations', $companyId),
            'purchaseTypes'        => $this->get('purchaseTypes', $companyId),
        ];
    }

    public function purchaseInvoiceMasterData(int $companyId = 0): array
    {
        return [
            'suppliers'    => $this->get('suppliers', $companyId, ['id', 'name', 'city']),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations' => $this->get('destinations', $companyId),
            'purchaseTypes'=> $this->get('purchaseTypes', $companyId),
        ];
    }


    public function salesOrderMasterData(int $companyId = 0): array
    {
        return [
            'customers'      => $this->get('customers', $companyId),
            'brokers'       => $this->get('brokers', $companyId),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations'        => $this->get('destinations', $companyId),
        ];
    }

    public function salesInvoiceMasterData(int $companyId = 0): array
    {
        return [
            'customers'      => $this->get('customers', $companyId),
            'brokers'       => $this->get('brokers', $companyId),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations'        => $this->get('destinations', $companyId),
        ];
    }

    // /**
    //  * Fetch multiple master data at once
    //  */
    // public function all(int $companyId = 0): array
    // {
    //     return [
    //         'accounts'      => $this->get('accounts', $companyId),
    //         'suppliers'      => $this->get('suppliers', $companyId),
    //         'accountGroups' => $this->get('accountGroups', $companyId),
    //         'brokers'       => $this->get('brokers', $companyId),
    //         'units'         => $this->get('units', $companyId),
    //         'taxCategories' => $this->get('taxCategories', $companyId),
    //         'countries'     => $this->get('countries'),
    //         'states'        => $this->get('states'),
    //         'financialYears'=> $this->get('financialYears'),
    //     ];
    // }


    public function dairyAnalysisMasterData(int $companyId = 0): array
    {
        return [
            'suppliers'      => $this->get('suppliers', $companyId, ['id', 'name','city']),
            'customers'      => $this->get('customers', $companyId),
            'brokers'       => $this->get('brokers', $companyId),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations'        => $this->get('destinations', $companyId),
        ];
    }

    public function godownModuleMasterData(int $companyId = 0): array
    {
        return [
            'brokers'       => $this->get('brokers', $companyId),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations' => $this->get('destinations', $companyId),
            'godown_units' => $this->get('godown_units', $companyId),
            'weight_locations' => $this->get('weight_locations', $companyId),
            'suppliers' => $this->get('suppliers', $companyId),

        ];
    }

    public function deliveryChallanMasterData(int $companyId = 0): array
    {
        return [
            'customers'      => $this->get('customers', $companyId),
            'brokers'       => $this->get('brokers', $companyId),
            'conditions'   => $this->get('conditions', $companyId),
            'items'        => $this->get('items', $companyId),
            'destinations'        => $this->get('destinations', $companyId),
        ];
    }
    public function transporterMasterData(int $companyId = 0): array
    {
        return [
            'states'       => $this->get('states', $companyId),
            'transporters' => $this->get('transporters', $companyId),
        ];
    }

    public function godownsMasterData(int $destinationId, array $columns = ['id', 'godown_name']): Collection
    {
        return $this->commonRepo->getGodowns($destinationId, $columns);
    }

    public function getCreditors(int $companyId = 0)
    {
        // Step 1: Find main "Creditors" group
        $creditorGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '270')
            ->first();

        if (!$creditorGroup) {
            return collect(); // return empty
        }

        // Step 2: Get all child groups under creditors
        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $creditorGroup->id)
            ->pluck('id')
            ->toArray();

        // Step 3: Include main group also
        $allGroupIds = array_merge([$creditorGroup->id], $childGroups);

        // Step 4: Get accounts under all groups
        return Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $allGroupIds)
            ->select('id', 'name', 'city')
            ->orderBy('name')
            ->get();
    }

    public function getDebtors(int $companyId = 0)
    {
        // Step 1: Find main "Debtors" group
        $debtorGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '170')
            ->first();

        if (!$debtorGroup) {
            return collect(); // return empty
        }

        // Step 2: Get all child groups under Debtors
        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $debtorGroup->id)
            ->pluck('id')
            ->toArray();

        // Step 3: Include main group also
        $allGroupIds = array_merge([$debtorGroup->id], $childGroups);

        // Step 4: Get accounts under all groups
        return Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $allGroupIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function banks(int $companyId = 0)
    {
        $bankGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '130')
            ->first();

        if (!$bankGroup) {
            return collect(); // return empty
        }

        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $bankGroup->id)
            ->pluck('id')
            ->toArray();

        $allGroupIds = array_merge([$bankGroup->id], $childGroups);

        return Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $allGroupIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getCreditorAndDebtor($companyId)
    {
        $debtor = $this->getDebtors($companyId);
        $creditor = $this->getCreditors($companyId);

        $collation = array_merge($debtor->toArray(), $creditor->toArray());

        usort($collation, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $collation;
    }
    public function getCreditorAndDebtorAndBank($companyId)
    {
        $debtor = $this->getDebtors($companyId);
        $creditor = $this->getCreditors($companyId);
        $banks = $this->banks($companyId);

        $collation = array_merge($debtor->toArray(), $creditor->toArray(), $banks->toArray());

        usort($collation, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $collation;
    }
}

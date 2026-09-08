<?php

namespace App\Repositories;

use App\Models\Account;


class CommonRepository
{
    protected $accountGroupRepo;
    protected $countryRepo;
    protected $stateRepo;
    protected $taxCategoryRepo;
    protected $unitRepo;
    protected $accountRepo;
    protected $companyRepo;
    protected $financialYearRepo;
    protected $conditionRepo;
    protected $voucherRepo;
    protected $itemRepo;
    protected $elementRepo;
    protected $destinationRepo;
    protected $purchaseTypeRepo;
    protected $saleTypeRepo;
    protected $godownUnitLocationRepo;
    protected $weightLocationRepo;
    protected $transporterRepo;
    protected $godownRepo;
    protected $brokerRepo;
    protected $chequeRepo;
    protected $rtgsRepo;
    protected $vehicleRepo;
    protected $transportPartyRepo;

    public function __construct(
        AccountGroupRepository $accountGroupRepo,
        CountryRepository $countryRepo,
        StateRepository $stateRepo,
        TaxCategoryRepository $taxCategoryRepo,
        UnitRepository $unitRepo,
        AccountRepository $accountRepo,
        CompanyRepository $companyRepo,
        FinancialYearRepository $financialYearRepo,
        ConditionRepository $conditionRepo,
        VoucherRepository    $voucherRepo,
        ItemRepository    $itemRepo,
        ElementRepository $elementRepo,
        DestinationRepository $destinationRepo,
        PurchaseTypeRepository $purchaseTypeRepo,
        SaleTypeRepository $saleTypeRepo,
        GodownUnitLocationRepository $godownUnitLocationRepo,
        WeightLocationRepository $weightLocationRepo,
        TransporterRepository $transporterRepo,
        GodownRepository $godownRepo,
        BrokerRepository $brokerRepo,
        ChequeRepository $chequeRepo,
        RtgsRepository $rtgsRepo,
        VehicleRepository $vehicleRepo,
        TransportPartyRepository $transportPartyRepo,
    ) {
        $this->accountGroupRepo  = $accountGroupRepo;
        $this->countryRepo       = $countryRepo;
        $this->stateRepo         = $stateRepo;
        $this->taxCategoryRepo   = $taxCategoryRepo;
        $this->unitRepo          = $unitRepo;
        $this->accountRepo       = $accountRepo;
        $this->companyRepo       = $companyRepo;
        $this->financialYearRepo = $financialYearRepo;
        $this->conditionRepo     = $conditionRepo;
        $this->voucherRepo       = $voucherRepo;
        $this->itemRepo          = $itemRepo;
        $this->elementRepo       = $elementRepo;
        $this->destinationRepo   = $destinationRepo;
        $this->purchaseTypeRepo  = $purchaseTypeRepo;
        $this->saleTypeRepo      = $saleTypeRepo;
        $this->godownUnitLocationRepo = $godownUnitLocationRepo;
        $this->weightLocationRepo = $weightLocationRepo;
        $this->transporterRepo = $transporterRepo;
        $this->godownRepo = $godownRepo;
        $this->brokerRepo = $brokerRepo;
        $this->chequeRepo = $chequeRepo;
        $this->rtgsRepo = $rtgsRepo;
        $this->vehicleRepo = $vehicleRepo;
        $this->transportPartyRepo = $transportPartyRepo;
    }

    public function getPurchaseTypes(int $companyId, $columns = ['*'])
    {
        return $this->purchaseTypeRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getSaleTypes(int $companyId, $columns = ['*'])
    {
        return $this->saleTypeRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getAccountGroups(int $companyId, $columns = ['*'])
    {
        return $this->accountGroupRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getCountries($columns = ['*'])
    {
        return $this->countryRepo->all($columns);
    }

    public function getStates($columns = ['*'])
    {
        return $this->stateRepo->all($columns);
    }

    public function getTaxCategories(int $companyId, $columns = ['*'])
    {
        return $this->taxCategoryRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function accountGroupsForFieldValidation(int $companyId)
    {
        return $this->accountGroupRepo->accountGroupsForFieldValidation($companyId);
    }

    public function getUnits(int $companyId, $columns = ['*'])
    {
        return $this->unitRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getAccounts(int $companyId, $columns = ['*'])
    {
        return $this->accountRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getBankDetails()
    {
        $group = $this->accountGroupRepo->all(
            with: ['accounts.bankDetails'],
            filters: ['code' => 130],
        )->first();

        $bankDetails = $group->accounts
            ->flatMap->bankDetails
            ->sortBy('id')
            ->map(fn($b) => ['id' => $b->id, 'bank_name' => $b->bank_name])
            ->values();

        return $bankDetails->pluck('bank_name','id');

    }

    public function getCompanies($columns = ['*'],)
    {
        return $this->companyRepo->all(
            columns: $columns,
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'name'
        );

    }

    public function getUserCompanies($columns = ['*'])
    {
        return $this->companyRepo->all(
            columns: $columns,
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'name'
        );

    }

    public function getFinancialYears($columns = ['*'])
    {
        return $this->financialYearRepo->all(
            columns: $columns,
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'id'
        );
    }

    public function getCompany(int $companyId, array $columns = ['*'])
    {
        return $this->companyRepo->find(
            id: $companyId,
            with: [],
            filter: ['status' => true],
            columns: ['*'],
        );
    }

    public function getFinancialYear(int $financialYearId,array $columns = ['*'])
    {
        return $this->financialYearRepo->find(
            id: $financialYearId,
            with: [],
            filter: ['status' => true],
            columns: ['*'],
        );
    }

    public function getCompanyByUUID(string $uuid, array $columns = ['*'], array $with = [])
    {
        return $this->companyRepo->findByUuid(
            uuid: $uuid,
            with: $with,
            filter: ['status' => true],
            columns: ['*'],
        );
    }

    public function getBrokers(int $companyId, $columns = ['*'])
    {
        return $this->brokerRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getSuppliers(int $companyId, $columns = ['*'])
    {
        return $this->accountRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId, 'party_type' => ['supplier']],
            scopes: [],
            orderBy: 'name'
        );
    }


    public function getCustomers(int $companyId, $columns = ['*'])
    {
        return $this->accountRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId, 'party_type' => ['customer']],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getConditions(int $companyId, $columns = ['*'])
    {
        return $this->conditionRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getItems(int $companyId, $columns = ['*'])
    {
        return $this->itemRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getElement(int $companyId, $columns = ['*'])
    {
        return $this->elementRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getDestinations(int $companyId, $columns = ['*'])
    {
        return $this->destinationRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }

    public function getGodownUnits(int $companyId, $columns = ['*'])
    {
        return $this->godownRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'godown_name'
        );
    }

    public function getGodownsByDestination(int $destinationId, $columns = ['*'])
    {
        return $this->godownUnitLocationRepo->all(
            columns: $columns,
            with: [],
            filters: ['destination_id' => $destinationId],
            scopes: [],
            orderBy: 'godown_name'
        );
    }

    public function getWeightLocations(int $companyId, $columns = ['*'])
    {
        return $this->weightLocationRepo->all(
            columns: $columns,
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'godown_name'
        );
    }
    public function getTransporters(int $companyId, $columns = ['*'])
    {
        return $this->transporterRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
            orderBy: 'name'
        );
    }
    public function getGodowns(int $destinationId, $columns = ['*'])
    {
        return $this->godownRepo->all(
            columns: $columns,
            with: [],
            filters: ['destination_id' => $destinationId],
            scopes: [],
            orderBy: 'godown_name'
        );
    }
    
    public function getCheque(?int $companyId = null)
    {
        return $this->chequeRepo->all(
            columns: ['*'],
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'id'
        )->filter(function ($cheque) use ($companyId) {
            return $cheque->company_id == $companyId || $cheque->company_id == 0;
        })->values();
    }

    public function getRtgs()
    {
        return $this->rtgsRepo->all(
            columns: ['*'],
            with: [],
            filters: ['status' => true],
            scopes: [],
            orderBy: 'id'
        );
    }
    public function getVehicles(int $companyId, $columns = ['*'])
    {
        return $this->vehicleRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
        );
    }
    public function getTransportParty(int $companyId, $columns = ['*'])
    {
        return $this->transportPartyRepo->all(
            columns: $columns,
            with: [],
            filters: ['company_id' => $companyId],
            scopes: [],
        );
    }
}

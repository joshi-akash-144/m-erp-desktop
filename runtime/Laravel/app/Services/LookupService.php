<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BillSundry;
use App\Models\Broker;
use App\Models\Condition;
use App\Models\Contractor;
use App\Models\Vehicle;
use App\Repositories\AccountRepository;
use App\Repositories\BillSundryRepository;
use App\Repositories\CommonRepository;
use App\Repositories\ConditionRepository;
use App\Repositories\DestinationRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PurchaseTypeRepository;
use App\Repositories\SalesOrderRepository;
use App\Repositories\SaleTypeRepository;
use App\Repositories\TransportPartyRepository;
use App\Repositories\AccountGroupRepository;
use Illuminate\Support\Collection;
use App\Models\VoucherType;


class LookupService
{
    protected ItemRepository $itemRepository;
    protected AccountRepository $accountRepository;
    protected DestinationRepository $destinationRepository;
    protected ConditionRepository $conditionRepository;
    protected PurchaseTypeRepository $purchaseTypeRepository;
    protected BillSundryRepository $billSundryRepository;
    protected SaleTypeRepository $saleTypeRepository;
    protected SalesOrderRepository $salesOrderRepository;
    protected AccountGroupRepository $accountGroupRepo;
    protected AccountBalanceService $accountBalanceService;
    protected TransportPartyRepository $transportPartyRepository;


    public function __construct(ItemRepository $itemRepository, AccountRepository $accountRepository, DestinationRepository $destinationRepository, ConditionRepository $conditionRepository, PurchaseTypeRepository $purchaseTypeRepository, BillSundryRepository $billSundryRepository, SaleTypeRepository $saleTypeRepository, SalesOrderRepository $salesOrderRepository, AccountGroupRepository $accountGroupRepo, AccountBalanceService $accountBalanceService, TransportPartyRepository $transportPartyRepository)
    {
        $this->itemRepository           = $itemRepository;
        $this->accountRepository        = $accountRepository;
        $this->destinationRepository    = $destinationRepository;
        $this->conditionRepository      = $conditionRepository;
        $this->purchaseTypeRepository   = $purchaseTypeRepository;
        $this->billSundryRepository     = $billSundryRepository;
        $this->saleTypeRepository       = $saleTypeRepository;
        $this->salesOrderRepository     = $salesOrderRepository;
        $this->accountGroupRepo         = $accountGroupRepo;
        $this->accountBalanceService    = $accountBalanceService;
        $this->transportPartyRepository = $transportPartyRepository;
    }

    public function getParties(int $companyId, ?string $search = null): Collection
    {
        $query = $this->accountRepository->query()
            ->whereIn('party_type', ['supplier', 'vendor'])
            ->where('company_id', $companyId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        return $query
            ->selectRaw("
            id,
            CASE 
                WHEN city IS NOT NULL AND city != '' 
                THEN CONCAT(name, ' (', city, ')')
                ELSE name
            END AS name
        ")
            ->limit(20)
            ->get();
    }



    public function getPartyAccountDetails(int $companyId, int $id): ?array
    {
        $account = $this->accountRepository->find(
            id: $id,
            with: ['bankDetail', 'taxDetail', 'preference', 'currentYearBalance'],
            filter: ['company_id' => $companyId]
        );

        if (!$account) {
            return null;
        }

        return [
            'id'       => $account->id,
            'name'     => $account->name,
            'gst_type' => $account->gst_type,
            'city'     => $account->city,
            'is_billwise' => $account->is_billwise,
            'kms' => $account->preference->distance ?? 0,
            'party_type' => $account->party_type,
        ];
    }

    public function getBrokers(int $companyId, ?string $search = null): Collection
    {
        $query = Broker::where('company_id', $companyId)->where('status', true);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->orderBy('name')->limit(20)->get();
    }

    public function getVehicles(int $companyId, ?string $search = null): Collection
    {
        $query = Vehicle::query()
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(50)->get();
    }

    public function getAllVehicles(int $companyId): Collection
    {
        return Vehicle::query()
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getBrokerDetails(int $companyId, int $id): ?array
    {
        $broker = Broker::where('company_id', $companyId)->find($id);

        if (!$broker) {
            return null;
        }

        return [
            'id'   => $broker->id,
            'name' => $broker->name,
            'city' => $broker->city,
        ];
    }

    public function getItems(int $companyId, ?string $search = null): Collection
    {
        $query = $this->itemRepository->query()
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(20)->get();
    }

    public function getAllItems(int $companyId): Collection
    {
        return $this->itemRepository->query()
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getItemDetails(int $companyId, int $id): ?array
    {
        $item = $this->itemRepository->find(
            id: $id,
            with: ['taxCategory', 'unit'],
            filter: ['company_id' => $companyId]
        );

        if (!$item) {
            return null;
        }

        return [
            'name'                      => $item->name,
            'tax_category_name'         => $item->taxCategory->name ?? null,
            'cgst'                      => $item->taxCategory->cgst ?? 0,
            'sgst'                      => $item->taxCategory->sgst ?? 0,
            'igst'                      => $item->taxCategory->igst ?? 0,
            'unit_name'                 => $item->unit->name ?? null,
            'hsn_sac_code'              => $item->hsn_sac_code,
            'is_maintain_stock_balance' => (bool) $item->is_maintain_stock_balance,
        ];
    }

    public function getDestinations(int $companyId, ?string $search = null): Collection
    {
        $query = $this->destinationRepository->query()
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(20)->get();
    }

    public function getAllDestinations(int $companyId): Collection
    {
        return $this->destinationRepository->query()
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getDestinationDetails(int $companyId, int $id): ?array
    {
        $destination = $this->destinationRepository->find(
            id: $id,
            with: [],
            filter: ['company_id' => $companyId]
        );

        if (!$destination) {
            return null;
        }

        return [
            'id'   => $destination->id,
            'name' => $destination->name,
        ];
    }

    public function getConditions(int $companyId, ?string $search = null): Collection
    {
        $query = $this->conditionRepository->query()
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(20)->get();
    }

    public function getConditionDetails(int $companyId, int $id): ?array
    {
        $condition = $this->conditionRepository->find(
            id: $id,
            with: [],
            filter: ['company_id' => $companyId]
        );

        if (!$condition) {
            return null;
        }

        return [
            'id'   => $condition->id,
            'name' => $condition->name,
        ];
    }

    public function getPurchaseTypes(int $companyId, ?string $search = null, ?string $region = null): Collection
    {
        $query = $this->purchaseTypeRepository->query()
            ->where('company_id', $companyId)->when($region, fn($query) => $query->where('region', $region));

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(20)->get();
    }

    public function getPurchaseTypeDetails(int $companyId, int $id): ?array
    {
        $purchaseType = $this->purchaseTypeRepository->find(
            id: $id,
            with: ['account'],
            filter: ['company_id' => $companyId]
        );

        if (!$purchaseType) {
            return null;
        }

        return [
            'id'   => $purchaseType->id,
            'name' => $purchaseType->name,
            'region' => $purchaseType->region,
            'sgst' => $purchaseType->sgst,
            'cgst' => $purchaseType->cgst,
            'igst' => $purchaseType->igst,
            'account_id' => $purchaseType->account->id
        ];
    }

    public function getLedgerAccounts(int $companyId, ?string $search = null): Collection
    {
        $query = $this->accountRepository->query()
            ->where('company_id', $companyId)
            ->where('is_hidden', 0)
            ->orderBy('name');
    
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
    
        return $query->selectRaw("
                id,
                CASE 
                    WHEN city IS NOT NULL AND city != '' 
                    THEN CONCAT(name, ' (', city, ')')
                    ELSE name
                END AS name
            ")
            ->limit(20)
            ->get();
    }
    

    public function billSundries(int $companyId): Collection
    {
        return $this->billSundryRepository->query()->where('company_id', $companyId)->get();
    }

    public function voucherTypes(): Collection
    {
        return VoucherType::query()->whereIn(
            'id',
            [
                VoucherType::JOURNAL,
                VoucherType::PAYMENT,
                VoucherType::RECEIPT,
                VoucherType::SALE_INVOICE,
                VoucherType::PURCHASE_INVOICE,
                VoucherType::DEBIT_NOTE,
                VoucherType::CREDIT_NOTE,
                VoucherType::PURCHASE_RETURN,
                VoucherType::SALES_RETURN
            ]
        )->get();
    }

    public function getSaleTypeDetails(int $companyId, int $id): ?array
    {
        $saleType = $this->saleTypeRepository->find(
            id: $id,
            with: ['account'],
            filter: ['company_id' => $companyId]
        );

        if (!$saleType) {
            return null;
        }

        return [
            'id'   => $saleType->id,
            'name' => $saleType->name,
            'region' => $saleType->region,
            'sgst' => $saleType->sgst,
            'cgst' => $saleType->cgst,
            'igst' => $saleType->igst,
            'account_id' => $saleType->account->id
        ];
    }

    public function getSalesTypes(int $companyId, ?string $search = null, ?string $region = null): Collection
    {
        $query = $this->saleTypeRepository->query()
            ->where('company_id', $companyId)->when($region, fn($query) => $query->where('region', $region));

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->limit(20)->get();
    }


    public function getBanks(int $companyId, ?string $search = null): Collection
    {
        $bankAccountGroupId = $this->accountGroupRepo->getBankAccountGroupId($companyId); // e.g., 130
    
        $query = $this->accountRepository->query()
            ->where('account_group_id', $bankAccountGroupId)
            ->where('company_id', $companyId);
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }
    
        // Fetch the records
        $banks = $query->get(['id', 'name']); // only fetch id and name
    
        // Return as Collection (empty Collection if no records)
        return $banks;
    }


    public function getAccountBalance ($companyId, $financialYearId, $accountId) {
        $accountIds = [$accountId];
        return $this->accountBalanceService->getAccountClosingBalance($companyId, $financialYearId, $accountIds);
    }


    public function getPreloadLedgerAccounts(int $companyId)
    {
        $bankGroupId    = $this->accountGroupRepo->getBankAccountGroupId($companyId);
        $cashGroupId    = $this->accountGroupRepo->getCashAccountGroupId($companyId);
        $partyGroupIds  = $this->accountGroupRepo->getPartyAccountGroupIds($companyId);

        return $this->accountRepository->query()
            ->with(['taxDetail', 'taxDetail.tdsCategory'])
            ->where('company_id', $companyId)
            ->where('is_hidden', 0)
            ->orderBy('name')
            ->selectRaw("
                id,
                code,
                account_group_id,
                gst_type,
                party_type,
                is_billwise,
                name AS original_name,
                CASE
                    WHEN city IS NOT NULL AND city != ''
                    THEN CONCAT(name, ' (', city, ')')
                    ELSE name
                END AS name
            ")
            ->get()
            ->map(function ($row) use ($bankGroupId, $cashGroupId, $partyGroupIds) {
                return (object) [
                    'id'              => $row->id,
                    'code'            => $row->code,
                    'name'            => $row->name,
                    'original_name'   => $row->original_name,
                    'gst_type'        => $row->gst_type,
                    'type'            => $row->party_type,
                    'is_billwise'     => (bool) $row->is_billwise,
                    'is_bank_account' => $row->account_group_id === $bankGroupId,
                    'is_cash_account' => $row->account_group_id === $cashGroupId,
                    'is_party_account'=> $partyGroupIds->contains($row->account_group_id),
                    'payee_category'  => $row->taxDetail->payee_category_id ?? null,
                    'tds_category'    => $row->taxDetail->tds_category_id ?? null,
                    'allow_tds'       => !empty($row->taxDetail->tds_category_id),
                    'pan_no'          => $row->taxDetail->pan ?? null,
                    'tds_category_detail' => $row->taxDetail && $row->taxDetail->tdsCategory ? [
                        'id' => $row->taxDetail->tdsCategory->id,
                        'name' => $row->taxDetail->tdsCategory->category_name
                    ] : null,
                ];
            });
    }
    public function getTransportParties(int $companyId, ?string $search = null): Collection
    {
        $query = $this->transportPartyRepository->query()
            ->where('company_id', $companyId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name', 'city')->orderBy('name')->limit(50)->get();
    }

    public function getExpenseAccounts(int $companyId, ?string $search = null): Collection
    {
        $rootGroup = $this->accountGroupRepo->query()
            ->where('company_id', $companyId)
            ->where('code', 420)
            ->first();

        if (!$rootGroup) {
            return collect();
        }

        $groupIds = $this->collectGroupIds($rootGroup->id);

        $query = $this->accountRepository->query()
            ->where('company_id', $companyId)
            ->whereIn('account_group_id', $groupIds);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->select('id', 'name')->orderBy('name')->get();
    }

    private function collectGroupIds(int $parentId): array
    {
        $ids      = [$parentId];
        $children = $this->accountGroupRepo->query()
            ->where('parent_id', $parentId)
            ->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectGroupIds($childId));
        }

        return $ids;
    }

    public function getAllContractors(int $companyId, ?string $search = null): Collection
    {
        return Contractor::query()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->get();
    }


}

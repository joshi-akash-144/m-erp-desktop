<?php

namespace App\Services;

use App\DTOs\DeliveryChallanDTO;
use App\Helpers\TaxHelper;
use App\Models\SalesOrder;
use App\Models\DeliveryChallan;
use Illuminate\Support\Facades\DB;
use App\Services\LookupService;
use App\Repositories\SalesOrderRepository;
use App\Repositories\DeliveryChallanRepository;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class DeliveryChallanService
{
    public function __construct(
        public DeliveryChallanRepository $deliveryChallanRepo,
        public SalesOrderRepository $salesOrderRepo,
        public LookupService $lookupService
    ) {
    }

    /* -----------------------------------------
     | Create Data
     |------------------------------------------
     */
    public function createDeliveryChallan(array $data, int $companyId, int $financialYearId, ?int $creatorId = null): DeliveryChallan
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId, $creatorId) {

            [$masterData, $items] = $this->prepareChallanPayload($data, $companyId, $financialYearId, 'create', $creatorId);

            $deliveryChallan = $this->deliveryChallanRepo->create($masterData);
            // Only store items — do NOT update Sales Order status when creating a Delivery Challan.
            foreach ($items as $item) {
                $deliveryChallan->details()->create($item);
            }

            return $deliveryChallan->load('details');
        });
    }

    public function updateDeliveryChallan(array $data, int $companyId, int $financialYearId, int $challanId, ?int $updaterId = null): DeliveryChallan
    {
        return DB::transaction(function () use ($data, $challanId, $companyId, $financialYearId, $updaterId) {

            [$masterData, $items] = $this->prepareChallanPayload($data, $companyId, $financialYearId, 'update', $updaterId);

            /** @var DeliveryChallan $deliveryChallan */
            $deliveryChallan = $this->deliveryChallanRepo->find($challanId);

            if (!$deliveryChallan) {
                throw new \Exception("Delivery Challan not found");
            }

            $deliveryChallan->details()->delete();

            $deliveryChallan = $this->deliveryChallanRepo->update($deliveryChallan, $masterData);

            // Insert new items
            foreach ($items as $item) {
                $deliveryChallan->details()->create($item);
            }

            return $deliveryChallan->load('details');
        });
    }
    public function prepareChallanPayload(array $data, int $companyId, int $financialYearId, string $mode = 'create', ?int $userId = null): array
    {
        $base = $this->prepareBaseChallanData($data, $companyId);

        if ($mode === 'create') {
            $serialInfo = $this->getNextVoucherNumber($companyId, $financialYearId);
            $base = array_merge($base, [
                'uuid' => $data['uuid'],
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'challan_serial' => $serialInfo->challan_serial,
                'challan_number' => $serialInfo->challan_number,
                'challan_status' => DeliveryChallan::STATUS_OPEN,
                'created_by' => $userId,
            ]);
        }

        if ($mode === 'update') {
            $base = array_merge($base, [
                'updated_by' => $userId,
            ]);
        }

        if (empty($data['items'])) {
            return [$base, []];
        }

        $items = $this->prepareItemData($data['items'], $companyId, $base['gst_type']);
        $totals = $this->calculateTotals($items);

        return [array_merge($base, $totals), $items];
    }

    // ---------------------------------------------
    // LOW LEVEL REUSABLE HELPERS
    // ---------------------------------------------

    protected function prepareBaseChallanData(array $data, int $companyId): array
    {
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);

        return [
            'challan_date' => $data['challan_date'],
            'challan_in_date' => $data['challan_in_date'] ?? null,
            'challan_out_date' => $data['challan_out_date'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
            'broker_id' => $data['broker_id'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'gst_type' => $accountDetail['gst_type'],
            'reference_number' => $data['reference_number'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'gross_weight' => $data['gross_weight'] ?? 0,
            'tare_weight' => $data['tare_weight'] ?? 0,
            'net_weight' => $this->net_weight($data['gross_weight'], $data['tare_weight']),
            'bag_count' => (int) ($data['bag_count'] ?? 0),
            'bag_type' => $data['bag_type'] ?? null,
            'net_weight_wt_bag' => $this->net_weight_without_bags(
                $data['gross_weight'] ?? 0,
                $data['tare_weight'] ?? 0,
                $data['bag_type'] ?? null,
                $data['bag_count'] ?? 0
            ),
        ];
    }

    protected function prepareItemData(array $items, int $companyId, string $accountType): array
    {
        return collect($items)->map(function ($item) use ($companyId, $accountType) {
            $itemDetail = $this->lookupService->getItemDetails($companyId, $item['item_id']);

            [$cgst, $sgst, $igst] = match ($accountType) {
                DeliveryChallan::TAX_LOCAL => [$itemDetail['cgst'], $itemDetail['sgst'], 0],
                default => [0, 0, $itemDetail['igst']],
            };

            $tax = TaxHelper::calculateInclusiveRate(
                rate: $item['rate'] ?? 0,
                cgstPercent: $cgst,
                sgstPercent: $sgst,
                igstPercent: $igst,
                accountType: $accountType,
                quantity: $item['quantity'] ?? 0
            );

            return [
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'] ?? 0,
                'party_quantity' => $item['party_quantity'] ?? 0,
                'rate' => $item['rate'] ?? 0,
                'inclusive_rate' => $item['inclusive_rate'] ?? 0,
                'tax_amount' => $tax['tax_amount'] ?? 0,
                'amount' => $item['amount'] ?? 0,
                'net_amount' => $tax['total_amount'] ?? 0,
                'grand_total' => $tax['total_amount'] ?? 0,
                'bag_count' => $item['bag_count'] ?? 0,
                'cgst_rate' => $cgst,
                'sgst_rate' => $sgst,
                'igst_rate' => $igst,
                'cgst_amount' => $tax['cgst_amount'],
                'sgst_amount' => $tax['sgst_amount'],
                'igst_amount' => $tax['igst_amount'],
                'taxable_amount' => $tax['taxable_amount'],
                'condition_id' => !empty($item['condition_id']) ? (int) $item['condition_id'] : null,
                'destination_id' => !empty($item['destination_id']) ? (int) $item['destination_id'] : null,
                'sales_order_id' => !empty($item['sales_order_id']) ? (int) $item['sales_order_id'] : null,
                'sales_order_item_id' => (!empty($item['sales_order_id']) && !empty($item['sales_order_item_id'])) ? (int) $item['sales_order_item_id'] : null,
                'sales_order_serial' => (!empty($item['sales_order_id']) && !empty($item['sales_order_serial'])) ? (int) $item['sales_order_serial'] : null,
            ];
        })->toArray();
    }

    protected function calculateTotals(array $items): array
    {
        return [
            'grand_total' => array_sum(array_column($items, 'net_amount')),
            'total_tax' => array_sum(array_column($items, 'tax_amount')),
            'sub_total' => array_sum(array_column($items, 'amount')),
            'total_quantity' => array_sum(array_column($items, 'quantity')),
        ];
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId): DeliveryChallanDTO
    {
        $lastSerial = $this->deliveryChallanRepo->getNextVoucherSerial($companyId, $financialYearId);

        $nextSerial = $lastSerial + 1;

        $prefix = 'DC';

        $financialYearName = financial_year_name() ?? now()->format('Y');

        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);

        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }

        return new DeliveryChallanDTO(
            challan_serial: $nextSerial,
            challan_number: sprintf("%s-%s-%05d", $prefix, $year, $nextSerial)
        );
    }

    public function net_weight(?float $gross, ?float $tare): ?float
    {
        if (empty($gross) || empty($tare) || $gross <= 0 || $tare <= 0) {
            return 0;
        }
        return $gross - $tare;
    }

    public function net_weight_without_bags(
        ?float $gross,
        ?float $tare,
        ?string $bagType,
        ?int $bagCount
    ): ?float {

        if ($gross === null || $tare === null) {
            return 0;
        }

        $net = $this->net_weight($gross, $tare) ?? 0;

        if ($net <= 0 || $bagCount === null || $bagCount <= 0) {
            return $net;
        }

        $bagWeight = match ($bagType) {
            DeliveryChallan::BAG_GUNNY => 1 * $bagCount, // assuming 1kg per gunny bag
            DeliveryChallan::BAG_PLASTIC => 0.2 * $bagCount, // assuming 0.2kg per plastic bag
            default => 0
        };
        return $net - $bagWeight;
    }

    public function isDuplicateReference(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $id = null): bool
    {
        return $this->deliveryChallanRepo->existsByReferenceNumberAndAccount(
            companyId: $companyId,
            financialYearId: $financialYearId,
            referenceNumber: $referenceNumber,
            accountId: $accountId,
            id  : $id
        );
    }

    public function getByReferenceNumberAndAccount(
        int $companyId,
        int $financialYearId,
        string $referenceNumber,
        int $accountId,
        ?int $id = null
    ): ?int {

        $record = $this->deliveryChallanRepo->findByReferenceNumberAndAccount([
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'reference_number'  => $referenceNumber,
            'account_id'        => $accountId,
        ]);

        return $record?->id;
    }
    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function list(int $companyId, int $financialYearId, ?array $filters = []): array
    {
        $paginator = $this->deliveryChallanRepo->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'delivery_challan.view',
            'delivery_challan.update',
            'delivery_challan.print',
            // 'delivery_challan.delete',
        ], true);

        return [
            'data' => $paginator->items(),
            'total' => ($filters['page'] ?? 1) * ($filters['size'] ?? 10) + ($paginator->hasMorePages() ? 1 : 0),
            'last_page' => $paginator->hasMorePages() ? ($filters['page'] ?? 1) + 1 : ($filters['page'] ?? 1),
            'current_page' => $filters['page'] ?? 1,
            'permissions' => $permissions
        ];
    }
}


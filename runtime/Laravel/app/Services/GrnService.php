<?php

namespace App\Services;

use App\DTOs\GrnDTO;
use App\Helpers\TaxHelper;
use App\Models\Grn;
use App\Models\Penalty;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Reference;
use App\Repositories\PurchaseOrderRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\GrnRepository;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Collection;

class GrnService
{
    protected VoucherService $voucherService;
    protected PurchaseOrderRepository $purchaseOrderRepo;
    protected GrnRepository $grnRepo;
    protected LookupService $lookupService;

    public function __construct(
        VoucherService $voucherService,
        PurchaseOrderRepository $purchaseOrderRepo,
        GrnRepository $grnRepo,
        LookupService $lookupService
    ) {
        $this->voucherService = $voucherService;
        $this->lookupService = $lookupService;
        $this->purchaseOrderRepo = $purchaseOrderRepo;
        $this->grnRepo = $grnRepo;
    }

    /* -----------------------------------------
     | Create Data
     |------------------------------------------
     */
    public function createGrn(array $data, int $companyId, int $financialYearId): Grn
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            // Idempotency check — if this UUID was already processed, return the existing GRN

            $checkUUID = Grn::where('uuid', $data['uuid'])->exists();
            if ($checkUUID) {
                throw new \Exception("This GRN already exists. Please refresh and try again.");
            }

            [$masterData, $items] = $this->prepareGrnPayload($data, $companyId, $financialYearId);

            $grn = $this->grnRepo->create($masterData);

            $purchaseOrdersToCheck = []; // collect affected PO IDs

            foreach ($items as $item) {

                if ($item['purchase_order_id'] && $item['purchase_order_item_id']) {

                    /** @var PurchaseOrder $purchaseOrder */
                    $purchaseOrder = $this->purchaseOrderRepo->find($item['purchase_order_id']);
                    $detail = $purchaseOrder->details()->find($item['purchase_order_item_id']);

                    $newReceived = $detail->received_qty + $item['quantity'];

                    $detail->received_qty = $newReceived;
                    $detail->is_closed    = $newReceived >= $detail->ordered_qty;
                    $detail->save();

                    $purchaseOrdersToCheck[] = $item['purchase_order_id'];
                }

                $grn->details()->create($item);
            }

            $purchaseOrdersToCheck = array_unique($purchaseOrdersToCheck);

            $this->purchaseOrderRepo->updatePurchaseOrderStatus($purchaseOrdersToCheck);

            $penalty = $this->calculatePenalty($grn->load('details'), $companyId);
            $grn->update(['penalty' => $penalty]);

            return $grn->load('details');
        });
    }

    public function updateGrn(array $data, int $companyId, int $financialYearId, int $grnId): Grn
    {
        return DB::transaction(function () use ($data, $grnId, $companyId, $financialYearId) {

            // If Purchase Bill is completed with grn than no update GRN
            if ($this->checkGrnStatusForPurchaseBillPaid($grnId)) {
                throw new \Exception("Purchase Bill is already paid against this GRN");
            }

            [$masterData, $items] = $this->prepareGrnPayload($data, $companyId, $financialYearId, 'update');
            /** @var Grn $grn */
            $grn = $this->grnRepo->find($grnId);

            if (!$grn) {
                throw new \Exception("Purchase GRN not found");
            }

            $oldItems = $grn->details()->get();

            $purchaseOrdersToCheck = [];

            // Revert old items
            foreach ($oldItems as $old) {
                if ($old->purchase_order_id && $old->purchase_order_item_id) {

                    $po = $this->purchaseOrderRepo->find($old->purchase_order_id);

                    $detail = $po->details()->find($old->purchase_order_item_id);
                    if ($detail) {
                        $detail->received_qty -= $old->quantity;
                        if ($detail->received_qty < 0) $detail->received_qty = 0;

                        $detail->is_closed = $detail->received_qty >= $detail->ordered_qty;
                        $detail->save();
                    }
                    $purchaseOrdersToCheck[] = $old->purchase_order_id;
                }
            }

            $grn->details()->delete();


            $grn = $this->grnRepo->update($grn, $masterData);

            // Insert new items
            foreach ($items as $item) {

                // insert GRN detail row
                $detail = $grn->details()->create($item);

                // update PO qty
                if ($item['purchase_order_id'] && $item['purchase_order_item_id']) {

                    $po = $this->purchaseOrderRepo->find($item['purchase_order_id']);
                    $poItem = $po->details()->find($item['purchase_order_item_id']);

                    if ($poItem) {
                        $poItem->received_qty += $item['quantity'];
                        $poItem->is_closed = $poItem->received_qty >= $poItem->ordered_qty;
                        $poItem->save();
                    }
                    $purchaseOrdersToCheck[] = $item['purchase_order_id'];
                }
            }

            $penalty = $this->calculatePenalty($grn->load('details'), $companyId);
            $grn->update(['penalty' => $penalty]);

            $purchaseOrdersToCheck = array_unique($purchaseOrdersToCheck);
            $this->purchaseOrderRepo->updatePurchaseOrderStatus($purchaseOrdersToCheck);

            return $grn->load('details');
        });
    }

public function revertGrnQtyFromPOs(array $grnIds): void
{
    if (empty($grnIds)) {
        return;
    }
    
    $grns = Grn::select('id','uuid','company_id', 'financial_year_id')->with(['details' => function ($query) {
        $query->select('id', 'grn_id', 'purchase_order_id', 'purchase_order_item_id', 'quantity');
    }])
    ->whereIn('id', $grnIds)
    ->get();
   
    // Sum quantity to revert per PO item (handles duplicates across GRNs)
    $qtyByPoItemId = [];
    foreach ($grns as $grn) {        
        foreach ($grn->details as $old) {
            if ($old->purchase_order_id && $old->purchase_order_item_id) {               
                $qtyByPoItemId[$old->purchase_order_item_id] = ($qtyByPoItemId[$old->purchase_order_item_id] ?? 0) + $old->quantity;
            }
        }
    }
   
    if (empty($qtyByPoItemId)) {
        return;
    }

    $purchaseOrderIds = $grns->pluck('details')
        ->flatten()
        ->pluck('purchase_order_id')
        ->filter()
        ->unique()
        ->values()
        ->all();
    // dd($purchaseOrderIds);
    // One query for all POs + their details instead of find() per row
    $purchaseOrders = PurchaseOrder::select('id','uuid','company_id','financial_year_id')->with(['details' => function ($query) {
            $query->select('id', 'purchase_order_id', 'received_qty', 'ordered_qty', 'is_closed');
    }])
    ->whereIn('id', $purchaseOrderIds)
    ->get();
   
    foreach ($purchaseOrders as $po) {
        foreach ($po->details as $detail) {
            if (!isset($qtyByPoItemId[$detail->id])) {
                continue;
            }

            $detail->received_qty = max(0, $detail->received_qty - $qtyByPoItemId[$detail->id]);
            $detail->is_closed = $detail->received_qty >= $detail->ordered_qty;
            $detail->save();
        }
    }

    $this->purchaseOrderRepo->updatePurchaseOrderStatus($purchaseOrderIds);
}

    protected function calculatePenalty(Grn $grn, int $companyId): float
    {
        if ($grn->entry_from !== Grn::ENTRY_FROM_OFFICE) {
            return 0;
        }

        $purchaseOrderIds = $grn->details
            ->pluck('purchase_order_id')
            ->filter()
            ->unique()
            ->values();

        if ($purchaseOrderIds->isEmpty()) {
            return 0;
        }

        $purchaseOrders = PurchaseOrder::whereIn('id', $purchaseOrderIds)
            ->get(['id', 'due_date'])
            ->keyBy('id');

        $grnDate = Carbon::parse($grn->grn_date);

        // Build item+days pairs for overdue details
        $overdueMap = [];
        foreach ($grn->details as $detail) {
            if (!$detail->purchase_order_id) {
                continue;
            }
            $order = $purchaseOrders->get($detail->purchase_order_id);
            if (!$order?->due_date) {
                continue;
            }
            $dueDate = Carbon::parse($order->due_date);
            if ($grnDate->gt($dueDate)) {
                $daysOverdue = (int) $grnDate->diffInDays($dueDate);
                $overdueMap[$detail->id] = ['item_id' => $detail->item_id, 'days' => $daysOverdue];
            }
        }

        if (empty($overdueMap)) {
            return 0;
        }

        $penalties = Penalty::where('company_id', $companyId)
            ->where(function ($query) use ($overdueMap) {
                foreach ($overdueMap as $pair) {
                    $query->orWhere(fn($q) => $q->where('item_id', $pair['item_id'])->where('days', $pair['days']));
                }
            })
            ->get()
            ->keyBy(fn($p) => $p->item_id . '_' . $p->days);

        $total = 0;
        foreach ($overdueMap as $pair) {
            $match = $penalties->get($pair['item_id'] . '_' . $pair['days']);
            if ($match) {
                $total += (float) $match->amount;
            }
        }

        return $total;
    }
    

    public function prepareGrnPayload(array $data, int $companyId, int $financialYearId, string $mode = 'create'): array
    {
        $base = $this->prepareBaseGrnData($data, $companyId);

        if ($mode === 'create') {
            $serialInfo = $this->getNextVoucherNumber($companyId, $financialYearId);
            $base = array_merge($base, [
                'uuid'              => $data['uuid'],
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'grn_serial'        => $serialInfo->grn_serial,
                'grn_number'        => $serialInfo->grn_number,
                'order_status'      => Grn::STATUS_OPEN,
                'created_by'        => current_user_id(),
            ]);
        }else{
            $base = array_merge($base, ['updated_by' => current_user_id()]);
        }

        // If no item data → return GRN-only
        if (empty($data['items'])) {
            return [$base, []];
        }

        $items  = $this->prepareItemData($data['items'], $companyId, $base['gst_type']);
        $totals = $this->calculateTotals($items);

        return [array_merge($base, $totals), $items];
    }

    // ---------------------------------------------
    // LOW LEVEL REUSABLE HELPERS
    // ---------------------------------------------

    protected function prepareBaseGrnData(array $data, int $companyId): array
    {
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);

        return [
            'grn_date'         => $data['grn_date'],
            'grn_in_date'      => $data['grn_in_date'] ?? null,
            'grn_out_date'     => $data['grn_out_date'] ?? null,
            'contract_number'  => $data['contract_number'] ?? null,
            'broker_id'        => $data['broker_id'] ?? null,
            'account_id'       => $data['account_id'] ?? null,
            'gst_type'         => $accountDetail['gst_type'],
            'reference_number' => $data['reference_number'] ?? null,
            'vehicle_number'   => $data['vehicle_number'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'gross_weight'     => $data['gross_weight'] ?? 0,
            'tare_weight'      => $data['tare_weight'] ?? 0,
            'net_weight'       => $this->net_weight($data['gross_weight'] ?? 0, $data['tare_weight'] ?? 0),
            'bag_count'        => (int)($data['bag_count'] ?? 0),
            'bag_type'         => $data['bag_type'] ?? null,
            'net_weight_wt_bag' => $this->net_weight_without_bags(
                $data['gross_weight'] ?? 0,
                $data['tare_weight'] ?? 0,
                $data['bag_type'] ?? null,
                $data['bag_count'] ?? 0
            ),
            'party_type' => $accountDetail['party_type'],
            'party_bill_date' => $data['party_bill_date'] ?? null,
            'url_path' => $data['url_path'] ?? null,
            'entry_from' => $data['entry_from'] ?? Grn::ENTRY_FROM_OFFICE,
        ];
    }

    protected function prepareItemData(array $items, int $companyId, string $accountType): array
    {
        return collect($items)->map(function ($item) use ($companyId, $accountType) {
            $itemDetail = $this->lookupService->getItemDetails($companyId, $item['item_id']);

            [$cgst, $sgst, $igst] = match ($accountType) {
                Grn::TAX_LOCAL => [$itemDetail['cgst'], $itemDetail['sgst'], 0],
                default        => [0, 0, $itemDetail['igst']],
            };

            $tax = TaxHelper::calculateInclusiveRate(
                rate: $item['rate'],
                cgstPercent: $cgst,
                sgstPercent: $sgst,
                igstPercent: $igst,
                accountType: $accountType,
                quantity: $item['quantity'] ?? 0
            );
            return [
                'item_id'                   => $item['item_id'],
                'quantity'                  => $item['quantity'] ?? 0,
                'party_quantity'            => $item['party_quantity'] ?? 0,
                'rate'                      => $item['rate'] ?? 0,
                'inclusive_rate'            => $item['inclusive_rate'] ?? 0,
                'tax_amount'                => $tax['tax_amount'] ?? 0,
                'amount'                    => $item['amount'] ?? 0,
                'net_amount'                => $tax['total_amount'] ?? 0,
                'bag_count'                 => $item['bag_count'] ?? 0,
                'cgst_rate'                 => $cgst,
                'sgst_rate'                 => $sgst,
                'igst_rate'                 => $igst,
                'cgst_amount'               => $tax['cgst_amount'],
                'sgst_amount'               => $tax['sgst_amount'],
                'igst_amount'               => $tax['igst_amount'],
                'taxable_amount'            => $tax['taxable_amount'],
                'condition_id'              => $item['condition_id'] ?? null,
                'destination_id'            => $item['destination_id'] ?? null,
                'purchase_order_serial'     => $item['purchase_order_serial'] ?? null,
                'purchase_order_id'         => $item['purchase_order_id'] ?? null,
                'purchase_order_item_id'    => $item['purchase_order_item_id'] ?? null,
            ];
        })->toArray();
    }

    public function calculateTotals(array $items): array
    {
        return [
            'grand_total'    => array_sum(array_column($items, 'net_amount')),
            'total_tax'      => array_sum(array_column($items, 'tax_amount')),
            'sub_total'      => array_sum(array_column($items, 'amount')),
            'total_quantity' => array_sum(array_column($items, 'quantity')),
        ];
    }


    /* -----------------------------------------
     | Edit Data
     |------------------------------------------
     */

    public function getEditData(int $grnId, int $companyId, int $financialYearId): ?Grn
    {
        return $this->grnRepo->getEditData($grnId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | View Data
     |------------------------------------------
     */

    public function getViewData(int $grnId, int $companyId, int $financialYearId): ?Grn
    {
        return $this->grnRepo->getViewData($grnId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */

    public function fetchPendingPurchaseOrders(int $companyId, int $financialYearId, ?array $filters = []): Collection
    {
        $mainSelect = ['id', 'order_serial', 'destination_id', 'contract_number', 'order_date', 'due_date', 'status', 'delivery_days', 'order_number', 'broker_id'];
        $detailsSelect = ['id', 'purchase_order_id', 'item_id', 'ordered_qty', 'received_qty', 'rate', 'inclusive_rate', 'cgst_rate', 'sgst_rate', 'igst_rate', 'condition_id'];

        return $this->purchaseOrderRepo->fetchPendingPurchaseOrders($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);
    }



    public function fetchPurchaseOrderItems(int $orderId, array $detailIds): Collection
    {

        $mainSelect = ['id', 'order_serial'];
        $detailsSelect = ['sgst', 'igst', 'cgst', 'received_qty', 'inclusive_rate', 'rate'];

        //Add also hear grn item wise row work remaining

        return $this->purchaseOrderRepo->fetchPurchaseOrderItems($orderId, $detailIds, $mainSelect, $detailsSelect);
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId): GrnDTO
    {
        return $this->voucherService->getNextGrnVoucherNumber($companyId, $financialYearId);
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

        // Base net weight

        $net = $this->net_weight($gross, $tare) ?? 0;

        if ($net <= 0 || $bagCount === null || $bagCount <= 0) {
            return $net;
        }

        // Gunny = 1 kg per bag
        // Plastic = 0.2 kg per bag
        $bagWeight = match ($bagType) {
            GRN::BAG_GUNNY   => GRN::BAG_GUNNY_WEIGHT * $bagCount,
            GRN::BAG_PLASTIC => GRN::BAG_PLASTIC_WEIGHT * $bagCount,
            default     => 0
        };
        return $net - $bagWeight;
    }

    public function isDuplicateReference(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $id = null): bool
    {
        return $this->grnRepo->existsByReferenceNumberAndAccount(
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

        $record = $this->grnRepo->findByReferenceNumberAndAccount([
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'reference_number'  => $referenceNumber,
            'account_id'        => $accountId,
        ]);

        return $record?->id;
    }

    public function getGrnSerial(int $companyId, int $financialYearId, ?string $entryFrom = null, ?array $selectedIds = []): Collection
    {
        return $this->grnRepo->getGrnSerial($companyId, $financialYearId, $entryFrom, $selectedIds);
    }

    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function grnList(int $companyId, int $financialYearId, ?array $filters=[]): array
    {
        $paginator = $this->grnRepo->list($companyId, $financialYearId, $filters);
        
        $permissions = userPermissions([
            'grn.view',
            'grn.update',
            'grn.print',
            // 'grn.delete',
        ], true);

        $filteredTotal = $this->grnRepo->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->grnRepo->countAll($companyId, $financialYearId);

        return [
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $filters['size']),
            'current_page' => $filters['page'],
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions
        ];
    }
    /* ------------------------------------------
     | List for Printing and Exporting
     |-------------------------------------------
    */
    public function grnListAll(int $companyId, int $financialYearId, ?array $filters=[]): Collection
    {
        return $this->grnRepo->listAll($companyId, $financialYearId, $filters);
    }

    public function checkGrnStatusForPurchaseBillPaid(int $grnId): bool
    {

        $purchaseBill = PurchaseInvoice::where('grn_id', $grnId)->first();
        if (empty($purchaseBill)) {
            return false;
        }

        $isPaid = Reference::where('voucher_id', $purchaseBill->voucher_id)
            ->where('is_closed', true)
            ->where('company_id', $purchaseBill->company_id)
            ->where('financial_year_id', $purchaseBill->financial_year_id)
            ->exists();


        return $isPaid;
    }
}

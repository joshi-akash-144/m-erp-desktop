<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\PurchaseOrder;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\GrnItem;

class PurchaseOrderRepository extends BaseRepository
{
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        parent::__construct($purchaseOrder);
    }

    public function getNextVoucherSerial(int $companyId, int $financialYearId): int
    {
        $lastOrder = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('is_skip_serial_generation', false)
            ->orderByDesc('order_serial')
            ->first();

        return $lastOrder ? $lastOrder->order_serial : 0;
    }

    /**
     * Fetch pending purchase orders.
     *
     * @param int $companyId The company ID to fetch orders for.
     * @param int|null $financialYearId The financial year ID to fetch orders for (optional).
     * @param int|null $accountId The account ID to fetch orders for (optional).
     * @param int|null $brokerId The broker ID to fetch orders for (optional).
     * @param int|null $itemId The item ID to fetch orders for (optional).
     * @return Collection The collection of pending purchase orders.
     */
    public function fetchPendingPurchaseOrders(
        int $companyId,
        ?int $financialYearId = null,
        ?array $filters = [],
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ): Collection {
        return $this->query()
            ->select($mainSelect)
            ->with([
                'details' => function ($q) use ($detailsSelect) {
                    if ($detailsSelect !== ['*']) {
                        $q->select($detailsSelect)->with('item:id,name')->with('condition:id,name');
                    }
                },
                'account:id,name,city',
                'broker:id,name,city',
                'destination:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($financialYearId, fn($q) => $q->where('financial_year_id', $financialYearId))
            ->when($filters['account_id'] ?? null, fn($q) => $q->where('account_id', $filters['account_id']))
            ->when($filters['broker_id'] ?? null, fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(!empty($filters['purchase_order_ids']), fn($q) => $q->whereNotIn('id', $filters['purchase_order_ids']))
            ->when(
                $filters['item_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->where('order_status', 'open')
            ->get();
    }

    public function fetchPurchaseOrderItems(
        int $orderId,
        array $detailIds,
        ?array $filters = [],
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ): Collection {
        return $this->query()
            ->where('id', $orderId)
            ->select($mainSelect)
            ->with([
                'details' => function ($q) use ($detailsSelect, $detailIds) {
                    if ($detailsSelect !== ['*']) {
                        $q->select($detailsSelect)->with('item:id,name')->whereIn('id', $detailIds);
                    }
                },
                'account:id,name,city',
                'broker:id,name,city',
                'destination:id,name',
            ])
            ->when($filters['account_id'] ?? null, fn($q) => $q->where('account_id', $filters['account_id']))
            ->when($filters['broker_id'] ?? null, fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(
                $filters['item_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->where('order_status', 'open')
            ->get();
        return $this->model->select('id', 'order_number')->get();
    }

    public function updatePurchaseOrderStatus(array $orderIds, ?string $status = null): void
    {

        foreach ($orderIds as $poId) {

            $po = $this->model->find($poId);

            $allClosed = $po->details()->where('is_closed', false)->doesntExist();

            if ($allClosed) {
                $po->update(['order_status' => PurchaseOrder::STATUS_CLOSE]);
            } else {
                $po->update(['order_status' => PurchaseOrder::STATUS_OPEN]);
            }
        }
    }

    public function getEditData(int $purchaseOrderId, int $companyId, int $financialYearId): ?PurchaseOrder
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $purchaseOrderId)
            ->with([
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'destination:id,name',
                'details:id,purchase_order_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,ordered_qty',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'order_serial', 'order_date', 'due_date', 'account_id', 'broker_id', 'destination_id', 'contract_number', 'delivery_days', 'total_quantity', 'sub_total as total_amount', 'order_status', 'gst_type','remarks'])
            ->first();
    }
    public function getViewData(int $purchaseOrderId, int $companyId, int $financialYearId): ?PurchaseOrder
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $purchaseOrderId)
            ->with([
                'account:id,city,name',
                'broker:id,city,name',
                'destination:id,name',
                'details:id,purchase_order_id,item_id,condition_id,rate,inclusive_rate,received_qty,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,ordered_qty',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',
                'purchase_order_items:purchase_order_id,received_qty',
            ])
            ->select(['id', 'order_serial', 'order_date', 'due_date', 'account_id', 'broker_id', 'destination_id', 'contract_number', 'delivery_days', 'total_quantity', 'sub_total as total_amount', 'order_status', 'gst_type', 'remarks'])
            ->first();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = [])
    {
        $mainSelect = ['id', 'order_serial', 'account_id', 'broker_id', 'destination_id', 'contract_number', 'order_date', 'due_date', 'status', 'updated_by', 'created_by', 'order_status'];
        $detailsSelect = ['purchase_order_id', 'item_id', 'ordered_qty', 'received_qty', 'rate', 'inclusive_rate', 'condition_id'];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);

        return $query->simplePaginate(
            $filters['size'],
            ['*'],
            'page',
            $filters['page']
        );
    }

    public function countFiltered(int $companyId, int $financialYearId, array $filters): int
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->count();
    }

    public function countAll(int $companyId, int $financialYearId): int
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function listAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->get();
    }

    public function getFilteredQuery(
        int $companyId,
        int $financialYearId,
        array $filters,
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ) {
        return $this->model->query()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'account:id,name,city',
                'broker:id,name,city',
                'destination:id,name',
                'details.condition:id,name',
                'details' => function ($q) use ($detailsSelect) {
                    $q->select($detailsSelect);
                },
                'details.item:id,name,unit_id',
                'details.unit:id,name',
                'details.condition:id,name'
            ])
            ->where([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->when(!empty($filters['po_id']), fn($q) => $q->where('id', $filters['po_id']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(!empty($filters['destination_id']), fn($q) => $q->where('destination_id', $filters['destination_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['condition_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id'])))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {

                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');

            $q->whereBetween('order_date', [$start, $end]);
            })
            ->when(($filters['order_status'] ?? 'all') !== 'all', function ($q) use ($filters) {
                $q->where('order_status', $filters['order_status']);
            })
            ->when(isset($filters['due_status']) && $filters['due_status'] !== '', function ($q) use ($filters) {
                if ($filters['due_status'] == 1) { // Yes (Due)
                    $q->where('order_status', PurchaseOrder::STATUS_OPEN)
                        ->whereDate('due_date', '<', Carbon::now()->format('Y-m-d'));
                } elseif ($filters['due_status'] == 0) { // No (Not Due)
                    $q->where('order_status', PurchaseOrder::STATUS_OPEN)
                        ->whereDate('due_date', '>=', Carbon::now()->format('Y-m-d'));
                }
            })->orderBy('order_serial', 'desc')
            ->select($mainSelect);
    }

    public function deleteDetails(PurchaseOrder $po): void
    {
        $po->details()->delete();
    }

    public function getReceivedQtyItemWise(PurchaseOrder $po): array
    {
        return $po->details()->pluck('received_qty', 'item_id')->toArray();
    }
    public function createDetails(PurchaseOrder $po, array $items): void
    {
        foreach ($items as $item) {
            $po->details()->create($item);
        }
    }
    public function isOrderOpen(PurchaseOrder $po): bool
    {
        return $po->details()->whereColumn('ordered_qty', '>', 'received_qty')->exists();
    }

    public function getOrdersSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId])->orderBy('id', 'desc')->get(['order_serial', 'id']);
    }
    public function getPurchaseOrderGrnDetails(int $companyId, int $financialYearId, ?array $filters=[]) {
        
        $purchaseOrderData = $this->model->query()
            ->select('id', 'order_serial', 'order_date', 'account_id', 'broker_id', 'destination_id', 'order_status')
            ->with([
                'account:id,name,city',
                'broker:id,name,city',
                'destination:id,name',
                'details:id,purchase_order_id,item_id,rate,inclusive_rate,ordered_qty,received_qty',
                'details.item:id,name',
            ])
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end = Carbon::parse($filters['end_date'])->format('Y-m-d');
                $q->whereBetween('order_date', [$start, $end]);
            })
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['destination_id']), fn($q) => $q->where('destination_id', $filters['destination_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(($filters['order_status'] ?? 'all') !== 'all', function ($q) use ($filters) {
                if (($filters['order_status'] ?? 'open') === PurchaseOrder::STATUS_DUE) {
                    $q->where('order_status', PurchaseOrder::STATUS_OPEN)
                        ->whereDate('due_date', '<', Carbon::now()->format('Y-m-d'));
                } else {
                    $q->where('order_status', $filters['order_status'] ?? 'open');
                }
            })
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();
            // dd($purchaseOrderData->toArray());
        return $purchaseOrderData;
    }
    
}

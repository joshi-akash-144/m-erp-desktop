<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\SalesOrder;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class SalesOrderRepository extends BaseRepository
{
    public function __construct(SalesOrder $salesOrder)
    {
        parent::__construct($salesOrder);
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
     * Fetch pending sales orders.
     *
     * @param int $companyId The company ID to fetch orders for.
     * @param int|null $financialYearId The financial year ID to fetch orders for (optional).
     * @param int|null $accountId The account ID to fetch orders for (optional).
     * @param int|null $brokerId The broker ID to fetch orders for (optional).
     * @param int|null $itemId The item ID to fetch orders for (optional).
     * @return Collection The collection of pending sales orders.
     */
    public function fetchPendingSalesOrders(
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
            ])
            ->where('company_id', $companyId)
            ->when($financialYearId, fn($q) => $q->where('financial_year_id', $financialYearId))
            ->when($filters['account_id'] ?? null, fn($q) => $q->where('account_id', $filters['account_id']))
            ->when($filters['broker_id'] ?? null, fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(
                $filters['item_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->when(
                $filters['destination_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id']))
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

    public function updateSalesOrderStatus(array $orderIds, ?string $status = null): void
    {

        foreach ($orderIds as $poId) {

            $po = $this->model->find($poId);

            $allClosed = $po->details()->where('is_closed', false)->doesntExist();

            if ($allClosed) {
                $po->update(['order_status' => SalesOrder::STATUS_CLOSE]);
            }
        }
    }

    public function getEditData(int $salesOrderId, int $companyId, int $financialYearId): ?SalesOrder
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $salesOrderId)
            ->with([
                'account:id,city,gst_type,name',
                'broker:id,city,name',

                'details:id,sales_order_id,item_id,destination_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,ordered_qty',

                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.destination:id,name',
                'details.condition:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',

                'details.item.saleTypeLocal:id,name,region,cgst,sgst,igst',
                'details.item.saleTypeInterstate:id,name,region,cgst,sgst,igst',

                'sales_order_items:sales_order_id,received_qty',
            ])
            ->select(['id', 'order_serial', 'purchase_order_number', 'purchase_order_date', 'delivery_date', 'due_date', 'account_id', 'broker_id', 'delivery_days', 'total_quantity', 'sub_total as total_amount', 'order_status', 'gst_type', 'remarks'])
            ->first();
    }
    public function getViewData(int $salesOrderId, int $companyId, int $financialYearId): ?SalesOrder
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $salesOrderId)
            ->with([
                'account:id,city,name',
                'broker:id,city,name',
                'details:id,sales_order_id,item_id,destination_id,condition_id,rate,received_qty,inclusive_rate,received_qty,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,ordered_qty',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.destination:id,name',
                'details.condition:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',
                'details.item.saleTypeLocal:id,name,region,cgst,sgst,igst',
                'details.item.saleTypeInterstate:id,name,region,cgst,sgst,igst',

                'sales_order_items:sales_order_id,received_qty',
            ])
            ->select(['id', 'order_serial', 'purchase_order_number', 'purchase_order_date', 'delivery_date', 'due_date', 'account_id', 'broker_id', 'delivery_days', 'total_quantity', 'sub_total as total_amount', 'order_status', 'gst_type', 'remarks'])
            ->first();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = [])
    {
        $mainSelect = ['id', 'order_serial', 'account_id', 'purchase_order_number', 'purchase_order_date', 'broker_id', 'purchase_order_number', 'purchase_order_date', 'delivery_date', 'due_date', 'status', 'updated_by', 'created_by', 'order_status', 'gst_type', 'remarks'];
        $detailsSelect = ['sales_order_id', 'item_id', 'ordered_qty', 'received_qty', 'rate', 'inclusive_rate', 'destination_id', 'condition_id'];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect)->orderByDesc('order_serial');
        // dd('query', $query->toSql());

        return $query->simplePaginate(
            $filters['size'],
            ['*'],
            'page',
            $filters['page']
        );
    }

    public function listAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->get();
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
                'details' => function ($q) use ($detailsSelect) {
                    $q->select($detailsSelect);
                },
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name'
            ])
            ->where([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->when(!empty($filters['so_id']), fn($q) => $q->where('id', $filters['so_id']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['destination_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id'])))
            ->when(!empty($filters['condition_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id'])))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');
                $q->whereBetween('purchase_order_date', [$start, $end]);
            })
            ->when(($filters['order_status'] ?? 'all') !== 'all', function ($q) use ($filters) {
                $q->where('order_status', $filters['order_status']);
            })
             ->when(isset($filters['due_status']) && $filters['due_status'] !== '', function ($q) use ($filters) {
                if ($filters['due_status'] == 1) { // Yes (Due)
                    $q->where('order_status', SalesOrder::STATUS_OPEN)
                        ->whereDate('due_date', '<', Carbon::now()->format('Y-m-d'));
                } elseif ($filters['due_status'] == 0) { // No (Not Due)
                    $q->where('order_status', SalesOrder::STATUS_OPEN)
                        ->whereDate('due_date', '>=', Carbon::now()->format('Y-m-d'));
                }
            })
            ->select($mainSelect);
    }

    public function deleteDetails(SalesOrder $so): void
    {
        $so->details()->delete();
    }

    public function getReceivedQtyItemWise(SalesOrder $so): array
    {
        return $so->details()->pluck('received_qty', 'item_id')->toArray();
    }
    public function createDetails(SalesOrder $so, array $items): void
    {
        foreach ($items as $item) {
            $so->details()->create($item);
        }
    }
    public function isOrderOpen(SalesOrder $so): bool
    {
        return $so->details()->whereColumn('ordered_qty', '>', 'received_qty')->exists();
    }

    public function getOrdersSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId,'is_skip_serial_generation' => false])->orderBy('id', 'desc')->get(['order_serial', 'id']);
    }

    function getSalesOrderSerialAndNumber(?int $id = null): array
    {
        $data = $this->model->query()->where('id', $id)->select('order_serial', 'order_number')->first();
        return $data ? $data->toArray() : [
            "order_serial" => null,
            "order_number" => null,
        ];
    }

    public function getSalesOrderBillDetails(int $companyId, int $financialYearId, ?array $filters = [])
    {
        $salesOrderData = $this->model->query()
            ->select('id', 'order_serial', 'purchase_order_date', 'account_id', 'broker_id', 'order_status','purchase_order_number')
            ->with([
                'account:id,name,city',
                'broker:id,name,city',
                'details:id,sales_order_id,item_id,destination_id,rate,inclusive_rate,ordered_qty,received_qty',
                'details.item:id,name',
                'details.destination:id,name',
            ])
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end = Carbon::parse($filters['end_date'])->format('Y-m-d');
                $q->whereBetween('purchase_order_date', [$start, $end]);
            })
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['destination_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id'])))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(($filters['order_status'] ?? 'all') !== 'all', function ($q) use ($filters) {
                $q->where('order_status', $filters['order_status'] ?? 'open');
            })
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        return $salesOrderData;
    }
}

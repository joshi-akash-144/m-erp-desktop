<?php

namespace App\Services;

use App\DTOs\PurchaseOrderNumberDTO;
use App\Helpers\TaxHelper;
use App\Models\PurchaseOrder;
use App\Repositories\PurchaseOrderRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Models\GrnItem;
class PurchaseOrderService
{
    protected VoucherService $voucherService;
    protected PurchaseOrderRepository $purchaseOrderRepo;
    protected LookupService $lookupService;

    public function __construct(VoucherService $voucherService, PurchaseOrderRepository $purchaseOrderRepo, LookupService $lookupService)
    {
        $this->voucherService = $voucherService;
        $this->lookupService = $lookupService;
        $this->purchaseOrderRepo = $purchaseOrderRepo;
    }

    public function createPurchaseOrder(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            [$masterData, $items] = $this->prepareOrderData($data, $companyId, $financialYearId);

            $purchaseOrder = $this->purchaseOrderRepo->create($masterData);


            // Attach purchase order id to each item
            $items = array_map(fn($item) => array_merge($item, [
                'received_qty' => $item['received_qty'] ?? 0,
            ]), $items);

            foreach ($items as $key => $item) {
                $purchaseOrder->details()->create($item);
            }

            return $purchaseOrder->load('details');
        });
    }

    public function updatePurchaseOrder(int $orderId, array $data, int $companyId, int $financialYearId): ?PurchaseOrder
    {
        return DB::transaction(function () use ($orderId, $data, $companyId, $financialYearId) {

            [$orderData, $orderItems] = $this->prepareOrderData(
                $data,
                $companyId,
                $financialYearId,
                'update'
            );

            $purchaseOrder = $this->purchaseOrderRepo->find($orderId);
            if (!$purchaseOrder) {
                throw new \Exception("Purchase order not found");
            }


            $this->purchaseOrderRepo->update($purchaseOrder, $orderData);


            $oldQty = $this->purchaseOrderRepo->getReceivedQtyItemWise($purchaseOrder);


            $this->purchaseOrderRepo->deleteDetails($purchaseOrder);


            foreach ($orderItems as &$item) {
                $item['received_qty'] = $oldQty[$item['item_id']] ?? 0;
                $item['is_closed'] = $item['ordered_qty'] <= $item['received_qty'];
            }

            $this->purchaseOrderRepo->createDetails($purchaseOrder, $orderItems);

            $purchaseOrder->order_status =
                $this->purchaseOrderRepo->isOrderOpen($purchaseOrder)
                ? PurchaseOrder::STATUS_OPEN
                : PurchaseOrder::STATUS_CLOSE;

            $purchaseOrder->save();

            return $purchaseOrder->load('details');
        });
    }


    public function prepareOrderData($data, $companyId, $financialYearId, string $mode = 'create'): array
    {
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
        $accountType   = $accountDetail['gst_type'];

        $orderData = [
            'account_id'      => $data['account_id'],
            'gst_type'        => $accountType,
            'broker_id'       => $data['broker_id'] ?? null,
            'destination_id'  => $data['destination_id'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
            'delivery_days'   => $data['delivery_days'] ?? 0,
            'order_date'      => $data['order_date'],
            'due_date'        => $this->calculateDueDate($data['order_date'], (int) ($data['delivery_days'] ?? 0)),
            'remarks'         => $data['remarks'] ?? null,
        ];

        if ($mode === 'create') {
            $orderInfo = $this->getNextVoucherNumber($companyId, $financialYearId);
            $orderData += [
                'uuid'              => $data['uuid'],
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'order_serial'      => $orderInfo->order_serial,
                'order_number'      => $orderInfo->order_number,
                'order_status'      => PurchaseOrder::STATUS_OPEN,
                'created_by'             => current_user_id()
            ];
        }else{
            $orderData['updated_by']     = current_user_id();
        }

        $totals = [
            'grand_total' => 0,
            'total_tax'   => 0,
            'sub_total'   => 0,
            'total_qty'   => 0,
        ];

        $orderItems = collect($data['items'])->map(function ($item) use ($companyId, $accountType, &$totals) {
            $itemDetail = $this->lookupService->getItemDetails($companyId, $item['item_id']);

            [$cgstPercent, $sgstPercent, $igstPercent] = match ($accountType) {
                PurchaseOrder::TAX_LOCAL => [$itemDetail['cgst'], $itemDetail['sgst'], 0],
                default => [0, 0, $itemDetail['igst']],
            };

            $taxDetails = TaxHelper::calculateInclusiveRate(
                rate: $item['rate'],
                cgstPercent: $cgstPercent,
                sgstPercent: $sgstPercent,
                igstPercent: $igstPercent,
                accountType: $accountType,
                quantity: $item['quantity']
            );

            $totals['grand_total'] += $taxDetails['total_amount'];
            $totals['total_tax']   += $taxDetails['tax_amount'];
            $totals['sub_total']   += $taxDetails['amount'];
            $totals['total_qty']   += $item['quantity'];

            return [
                'item_id'         => $item['item_id'],
                'condition_id'    => $item['condition_id'] ?? null,
                'ordered_qty'     => $item['quantity'],
                'rate'            => $item['rate'],
                'inclusive_rate'  => $item['inclusive_rate'],
                'taxable_amount'  => $taxDetails['taxable_amount'],
                'cgst_rate'       => $cgstPercent,
                'sgst_rate'       => $sgstPercent,
                'igst_rate'       => $igstPercent,
                'cgst_amount'     => $taxDetails['cgst_amount'],
                'sgst_amount'     => $taxDetails['sgst_amount'],
                'igst_amount'     => $taxDetails['igst_amount'],
                'tax_amount'      => $taxDetails['tax_amount'],
                'amount'          => $item['amount'],
                'net_amount'      => $taxDetails['total_amount'],
            ];
        })->toArray();

        return [array_merge($orderData, [
            'grand_total'    => $totals['grand_total'],
            'total_tax'      => $totals['total_tax'],
            'total_quantity' => $totals['total_qty'],
            'sub_total'      => $totals['sub_total'],
        ]), $orderItems];
    }

    /* -----------------------------------------
     | Edit Data
     |------------------------------------------
     */

    public function getEditData(int $purchaseOrderId, int $companyId, int $financialYearId): PurchaseOrder | null
    {
        return $this->purchaseOrderRepo->getEditData($purchaseOrderId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | View Data
     |------------------------------------------
     */

    public function getViewData(int $purchaseOrderId, int $companyId, int $financialYearId): PurchaseOrder | null
    {
        return $this->purchaseOrderRepo->getViewData($purchaseOrderId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function purchaseOrderList(int $companyId, int $financialYearId, array $filters): array
    {
        $paginator = $this->purchaseOrderRepo->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'purchase_order.view',
            'purchase_order.update',
            'purchase_order.delete',
        ], true);

        $filteredTotal = $this->purchaseOrderRepo->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->purchaseOrderRepo->countAll($companyId, $financialYearId);

        return [
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $filters['size']),
            'current_page' => $filters['page'],
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions
        ];
    }

    public function purchaseOrderListAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->purchaseOrderRepo->listAll($companyId, $financialYearId, $filters);
    }

    public function calculateDueDate(string $orderDate, int $deliveryDays): string
    {
        return Carbon::parse($orderDate)
            ->addDays($deliveryDays)
            ->format('Y-m-d');
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId): PurchaseOrderNumberDTO
    {
        $lastSerial = $this->purchaseOrderRepo->getNextVoucherSerial($companyId, $financialYearId);

        $nextSerial = $lastSerial + 1;

        $prefix = 'PO';

        $financialYearName = financial_year_name() ?? now()->format('Y');

        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);

        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }

        return new PurchaseOrderNumberDTO(
            order_serial: $nextSerial,
            order_number: sprintf("%s-%s-%04d", $prefix, $year, $nextSerial)
        );
    }

    public function getOrdersSerial($companyId, $financialYearId): Collection
    {
        return $this->purchaseOrderRepo->getOrdersSerial($companyId, $financialYearId);
    }
    /* -----------------------------------------
    | List (Data Grid)
    |------------------------------------------
    */
    public function purchaseOrderDetailList(int $companyId, int $financialYearId, ?array $filters=[]): array
    {
        $purchaseOrderData = $this->purchaseOrderRepo->getPurchaseOrderGrnDetails(companyId: $companyId,financialYearId: $financialYearId,filters: $filters);
        // Early return if no data
        if ($purchaseOrderData->isEmpty()) {
            return [
                'data' => [],
                'footerData' => [
                    'order_qty_total' => '0.000',
                    'rec_qty_total' => '0.000',
                    'rem_qty_total' => '0.000',
                ]
            ];
        }

        // Fetch related GRN data
        $purchaseOrderIds = $purchaseOrderData->pluck('id');
        
        $grnData = GrnItem::with(['grn:id,grn_number,grn_serial,grn_date,reference_number,vehicle_number'])
            ->select('id', 'purchase_order_id', 'grn_id','bag_count', 'rate', 'party_quantity', 'quantity')
            ->whereIn('purchase_order_id', $purchaseOrderIds)
            ->whereHas('grn', fn($q) => $q->where('company_id', $companyId)->where('financial_year_id', $financialYearId))
            ->get();
        // dd($grnData->toArray());

        // Transform purchase orders into indexed structure
        $purchaseOrders = [];

        foreach ($purchaseOrderData as $po) {
            $orderId = $po['id'];
            
            foreach ($po['details'] as $detail) {
                $purchaseOrders[$orderId] = [
                    'po_number' => $po['order_serial'],
                    'po_date' => $po['order_date'],
                    'supplier_name' => $po['account']['name'],
                    'supplier_city' => $po['account']['city'],
                    'product_name' => $detail['item']['name'],
                    'broker_name' => $po['broker']['name'],
                    'remaining_qty' => (float) $detail['remaining_qty'],
                    'destination_name' => $po['destination']['name'],
                    'rate' => (float) $detail['rate'],
                    'ordered_qty' => (float) $detail['ordered_qty'],
                ];
            }
        }
        // dd($purchaseOrders);
        // Transform GRN data into indexed structure
        // dd($grnData->toArray());
        $grnsByOrder = [];
        foreach ($grnData as $grn) {
            $orderId = $grn['purchase_order_id'];
            // dd($grn['purchaseOrder']['order_number']);
            $grnsByOrder[$orderId][] = [
                'grn_date' => $grn['grn']['grn_date'],
                'reference_number' => $grn['grn']['reference_number'] ?? '',
                'grn_number' => $grn['grn']['grn_serial'],
                'vehicle_number' => $grn['grn']['vehicle_number'],
                'bags' => $grn['bag_count'],
                'party_qty' => (float) $grn['party_quantity'],
                'received_qty' => (float) $grn['quantity'],
            ];
        }

        // dd($grnsByOrder);
        // Build result data
        $result = [];
        $totalOrderQty = 0.0;
        $totalReceivedQty = 0.0;
        $totalRemainingQty = 0.0;

        foreach ($purchaseOrders as $orderId => $poData) {
            $hasGrns = isset($grnsByOrder[$orderId]) && count($grnsByOrder[$orderId]) > 0;
            
            if ($hasGrns) {
                $orderReceivedQty = 0.0;
                
                foreach ($grnsByOrder[$orderId] as $index => $grnData) {
                    $isFirstRow = ($index === 0);
                    
                    $result[] = [
                        'po_no' => $isFirstRow ? $poData['po_number'] : '',
                        'po_date' => $isFirstRow ? $poData['po_date'] : '',
                        'supplier_name' => $isFirstRow ? $poData['supplier_name'] : '',
                        'supplier_city' => $isFirstRow ? $poData['supplier_city'] : '',
                        'product_name' => $isFirstRow ? $poData['product_name'] : '',
                        'broker_name' => $isFirstRow ? $poData['broker_name'] : '',
                        'remaining_qty' => $isFirstRow ? number_format($poData['remaining_qty'], 3, '.', '') : '',
                        'destination_name' => $isFirstRow ? $poData['destination_name'] : '',
                        'rate' => $isFirstRow ? number_format($poData['rate'], 2, '.', '') : '',
                        'qty' => $isFirstRow ? number_format($poData['ordered_qty'], 3, '.', '') : '',
                        'grn_date' => $grnData['grn_date'],
                        'bill_no' => $grnData['reference_number'],
                        'grn_no' => $grnData['grn_number'],
                        'vehicle_no' => $grnData['vehicle_number'],
                        'bags' => $grnData['bags'],
                        'p_qty' => number_format($grnData['party_qty'], 3, '.', ''),
                        'rec_qty' => number_format($grnData['received_qty'], 3, '.', ''),
                    ];
                    
                    $orderReceivedQty += $grnData['received_qty'];
                    
                    if ($isFirstRow) {
                        $totalOrderQty += $poData['ordered_qty'];
                        $totalRemainingQty += $poData['remaining_qty'];
                    }
                }
                
                $totalReceivedQty += $orderReceivedQty;
                
                // Add subtotal row
                $result[] = $this->buildSubtotalRow(
                    $poData['ordered_qty'],
                    $poData['remaining_qty'],
                    $orderReceivedQty
                );
            } else {
                // Add PO row with no GRN data
                $result[] = [
                    'po_no' => $poData['po_number'],
                    'po_date' => $poData['po_date'],
                    'supplier_name' => $poData['supplier_name'],
                    'supplier_city' => $poData['supplier_city'],
                    'product_name' => $poData['product_name'],
                    'broker_name' => $poData['broker_name'],
                    'remaining_qty' => number_format($poData['remaining_qty'], 3, '.', ''),
                    'destination_name' => $poData['destination_name'],
                    'rate' => number_format($poData['rate'], 2, '.', ''),
                    'qty' => number_format($poData['ordered_qty'], 3, '.', ''),
                    'grn_date' => '-',
                    'bill_no' => '-',
                    'grn_no' => '-',
                    'vehicle_no' => '-',
                    'bags' => '-',
                    'p_qty' => '0.000',
                    'rec_qty' => '0.000',
                ];
                
                // Add subtotal row
                $result[] = $this->buildSubtotalRow(
                    $poData['ordered_qty'],
                    $poData['remaining_qty'],
                    0.0
                );
                
                $totalOrderQty += $poData['ordered_qty'];
                $totalRemainingQty += $poData['remaining_qty'];
            }
        }

        return [
            'data' => $result,            
            'footerData' => [
                'order_qty_total' => number_format($totalOrderQty, 3, '.', ''),
                'rec_qty_total' => number_format($totalReceivedQty, 3, '.', ''),
                'rem_qty_total' => number_format($totalRemainingQty, 3, '.', ''),
            ]
        ];
    }
    private function buildSubtotalRow($orderedQty, $remainingQty, $receivedQty)
    {
        return [
            'po_no' => '',
            'po_date' => '',
            'supplier_name' => '',
            'supplier_city' => '',
            'product_name' => '',
            'broker_name' => '',
            'remaining_qty' => number_format($remainingQty, 3, '.', ''),
            'destination_name' => '',
            'rate' => 'Total',
            'qty' => number_format($orderedQty, 3, '.', ''),
            'grn_date' => '',
            'bill_no' => '',
            'grn_no' => '',
            'vehicle_no' => '',
            'bags' => '',
            'p_qty' => '',
            'rec_qty' => number_format($receivedQty, 3, '.', ''),
        ];
    }
}

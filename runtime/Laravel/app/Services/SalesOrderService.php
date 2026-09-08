<?php

namespace App\Services;

use App\DTOs\SalesOrderNumberDTO;
use App\Helpers\TaxHelper;
use App\Models\SalesOrder;
use App\Models\SalesInvoiceItem;
use App\Repositories\SalesOrderRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class SalesOrderService
{
    protected VoucherService $voucherService;
    protected SalesOrderRepository $salesOrderRepository;
    protected LookupService $lookupService;

    public function __construct(VoucherService $voucherService, SalesOrderRepository $salesOrderRepository, LookupService $lookupService)
    {
        $this->voucherService = $voucherService;
        $this->lookupService = $lookupService;
        $this->salesOrderRepository = $salesOrderRepository;
    }

    public function createSalesOrder(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            [$masterData, $items] = $this->prepareOrderData($data, $companyId, $financialYearId);
            $salesOrder = $this->salesOrderRepository->create($masterData);
            // Attach sales order id to each item
            $items = array_map(fn($item) => array_merge($item, [
                'received_qty' => $item['received_qty'] ?? 0,
            ]), $items);

            foreach ($items as $key => $item) {
                $salesOrder->details()->create($item);
            }
            return $salesOrder->load('details');
        });
    }

    public function updateSalesOrder(int $orderId, array $data, int $companyId, int $financialYearId): ?SalesOrder
    {
        // dd($data);
        return DB::transaction(function () use ($orderId, $data, $companyId, $financialYearId) {
            [$orderData, $orderItems] = $this->prepareOrderData(
                $data,
                $companyId,
                $financialYearId,
                'update'
            );
            $salesOrder = $this->salesOrderRepository->find($orderId);
            // dd('salesOrder', $salesOrder->toArray());
            if (!$salesOrder) {
                throw new \Exception("Sales order not found");
            }
            $this->salesOrderRepository->update($salesOrder, $orderData);
            $oldQty = $this->salesOrderRepository->getReceivedQtyItemWise($salesOrder);
            $this->salesOrderRepository->deleteDetails($salesOrder);
            foreach ($orderItems as &$item) {
                $item['received_qty'] = $oldQty[$item['item_id']] ?? 0;
                $item['is_closed'] = $item['ordered_qty'] <= $item['received_qty'];
            }
            $this->salesOrderRepository->createDetails($salesOrder, $orderItems);
            $salesOrder->order_status =
                $this->salesOrderRepository->isOrderOpen($salesOrder)
                ? SalesOrder::STATUS_OPEN
                : SalesOrder::STATUS_CLOSE;

            $salesOrder->save();

            return $salesOrder->load('details');
        });
    }


    public function prepareOrderData($data, $companyId, $financialYearId, string $mode = 'create'): array
    {
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
        $accountType   = $accountDetail['gst_type'];

        $orderData = [
            'account_id'      => $accountDetail['id'],
            'purchase_order_number' => $data['purchase_order_number'] ?? null,
            'purchase_order_date' => $data['purchase_order_date'] ?? null,
            'gst_type'        => $accountType,
            'broker_id'       => $data['broker_id'] ?? null,
            'delivery_days'   => $data['delivery_days'] ?? 0,
            'delivery_date'      => $data['delivery_date'],
            'due_date'        => $this->calculateDueDate($data['delivery_date'], (int) ($data['delivery_days'] ?? 0)),
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
                'order_status'      => SalesOrder::STATUS_OPEN,
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
                SalesOrder::TAX_LOCAL => [$itemDetail['cgst'], $itemDetail['sgst'], 0],
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
                'destination_id'  => $item['destination_id'],
                'condition_id'    => $item['condition_id'],
                'ordered_qty'     => $item['quantity'],
                'rate'            => $taxDetails['rate'],
                'inclusive_rate'  => $taxDetails['inclusive_rate'],
                'taxable_amount'  => $taxDetails['taxable_amount'],
                'cgst_rate'       => $cgstPercent,
                'sgst_rate'       => $sgstPercent,
                'igst_rate'       => $igstPercent,
                'cgst_amount'     => $taxDetails['cgst_amount'],
                'sgst_amount'     => $taxDetails['sgst_amount'],
                'igst_amount'     => $taxDetails['igst_amount'],
                'tax_amount'      => $taxDetails['tax_amount'],
                'amount'          => $taxDetails['amount'],
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

    public function getEditData(int $salesOrderId, int $companyId, int $financialYearId): SalesOrder | null
    {
        return $this->salesOrderRepository->getEditData($salesOrderId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | View Data
     |------------------------------------------
     */

    public function getViewData(int $salesOrderId, int $companyId, int $financialYearId): SalesOrder | null
    {
        return $this->salesOrderRepository->getViewData($salesOrderId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function salesOrderList(int $companyId, int $financialYearId, array $filters): array
    {
        // dd('filters', $filters);
        $paginator = $this->salesOrderRepository->list($companyId, $financialYearId, $filters);
        // dd($paginator->toArray());
        $permissions = userPermissions([
            'sales_order.view',
            'sales_order.update',
            'sales_order.delete',
        ], true);
        // dd($permissions);
        $filteredTotal = $this->salesOrderRepository->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->salesOrderRepository->countAll($companyId, $financialYearId);

        return [
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $filters['size']),
            'current_page' => $filters['page'],
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions
        ];
    }

    public function salesOrderListAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->salesOrderRepository->listAll($companyId, $financialYearId, $filters);
    }

    public function calculateDueDate(string $orderDate, int $deliveryDays): string
    {
        return Carbon::parse($orderDate)
            ->addDays($deliveryDays)
            ->format('Y-m-d');
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId): SalesOrderNumberDTO
    {
        $lastSerial = $this->salesOrderRepository->getNextVoucherSerial($companyId, $financialYearId);
        $nextSerial = $lastSerial + 1;
        $prefix = 'SO';
        $financialYearName = financial_year_name() ?? now()->format('Y');
        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);
        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }
        return new SalesOrderNumberDTO(
            order_serial: $nextSerial,
            order_number: sprintf("%s-%s-%04d", $prefix, $year, $nextSerial)
        );
    }

    public function getOrdersSerial($companyId, $financialYearId): Collection
    {
        return $this->salesOrderRepository->getOrdersSerial($companyId, $financialYearId);
    }

    /* -----------------------------------------
    | List (Data Grid)
    |------------------------------------------
    */
    public function salesOrderDetailList(int $companyId, int $financialYearId, ?array $filters = []): array
    {
        $salesOrderData = $this->salesOrderRepository->getSalesOrderBillDetails(companyId: $companyId, financialYearId: $financialYearId, filters: $filters);
        // Early return if no data
        if ($salesOrderData->isEmpty()) {
            return [
                'data' => [],
                'footerData' => [
                    'order_qty_total' => '0.000',
                    'rec_qty_total' => '0.000',
                    'rem_qty_total' => '0.000',
                ]
            ];
        }

        // Fetch related bill (sales invoice) data
        $salesOrderIds = $salesOrderData->pluck('id');

        $billData = SalesInvoiceItem::with(['salesInvoice:id,sales_order_id,invoice_serial,invoice_date,grn_number,vehicle_number'])
            ->select('id', 'sales_invoice_id', 'bag_count', 'rate', 'party_quantity', 'quantity')
            ->whereHas('salesInvoice', fn($q) => $q->whereIn('sales_order_id', $salesOrderIds)
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId))
            ->get();

        // Transform sales orders into indexed structure
        $salesOrders = [];

        foreach ($salesOrderData as $so) {
            $orderId = $so['id'];

            foreach ($so['details'] as $detail) {
                $salesOrders[$orderId] = [
                    'so_number'           => $so['order_serial'],
                    'so_date'             => $so['purchase_order_date'],
                    'purchase_order_number' => $so['purchase_order_number'] ?? '',
                    'customer_name'       => $so['account']['name'],
                    'customer_city'       => $so['account']['city'],
                    'product_name'        => $detail['item']['name'],
                    'broker_name'         => $so['broker']['name'] ?? '',
                    'remaining_qty'       => (float) $detail['remaining_qty'],
                    'destination_name'    => $detail['destination']['name'] ?? '',
                    'rate'                => (float) $detail['rate'],
                    'ordered_qty'         => (float) $detail['ordered_qty'],
                ];
            }
        }

        // Transform bill data into indexed structure keyed by sales order id
        $billsByOrder = [];
        foreach ($billData as $item) {
            $invoice = $item['salesInvoice'];
            if (!$invoice || !$invoice['sales_order_id']) {
                continue;
            }
            $orderId = $invoice['sales_order_id'];

            $billsByOrder[$orderId][] = [
                'invoice_date' => $invoice['invoice_date'],
                'grn_number' => $invoice['grn_number'] ?? '',
                'invoice_number' => $invoice['invoice_serial'],
                'vehicle_number' => $invoice['vehicle_number'],
                'bags' => $item['bag_count'],
                'party_qty' => (float) $item['party_quantity'],
                'billed_qty' => (float) $item['quantity'],
            ];
        }

        // Build result data
        $result = [];
        $totalOrderQty = 0.0;
        $totalBilledQty = 0.0;
        $totalRemainingQty = 0.0;

        foreach ($salesOrders as $orderId => $soData) {
            $hasBills = isset($billsByOrder[$orderId]) && count($billsByOrder[$orderId]) > 0;

            if ($hasBills) {
                $orderBilledQty = 0.0;

                foreach ($billsByOrder[$orderId] as $index => $bill) {
                    $isFirstRow = ($index === 0);

                    $result[] = [
                        'so_no'                => $isFirstRow ? $soData['so_number'] : '',
                        'so_date'              => $isFirstRow ? $soData['so_date'] : '',
                        'purchase_order_number' => $isFirstRow ? $soData['purchase_order_number'] : '',
                        'customer_name'        => $isFirstRow ? $soData['customer_name'] : '',
                        'customer_city'        => $isFirstRow ? $soData['customer_city'] : '',
                        'product_name'         => $isFirstRow ? $soData['product_name'] : '',
                        'broker_name'          => $isFirstRow ? $soData['broker_name'] : '',
                        'remaining_qty'        => $isFirstRow ? number_format($soData['remaining_qty'], 3, '.', '') : '',
                        'destination_name'     => $isFirstRow ? $soData['destination_name'] : '',
                        'rate'                 => $isFirstRow ? number_format($soData['rate'], 2, '.', '') : '',
                        'qty'                  => $isFirstRow ? number_format($soData['ordered_qty'], 3, '.', '') : '',
                        'invoice_date'         => $bill['invoice_date'],
                        'grn_no'               => $bill['grn_number'],
                        'invoice_no'           => $bill['invoice_number'],
                        'vehicle_no'           => $bill['vehicle_number'],
                        'bags'                 => $bill['bags'],
                        'p_qty'                => number_format($bill['party_qty'], 3, '.', ''),
                        'rec_qty'              => number_format($bill['billed_qty'], 3, '.', ''),
                    ];

                    $orderBilledQty += $bill['billed_qty'];

                    if ($isFirstRow) {
                        $totalOrderQty += $soData['ordered_qty'];
                        $totalRemainingQty += $soData['remaining_qty'];
                    }
                }

                $totalBilledQty += $orderBilledQty;

                // Add subtotal row
                $result[] = $this->buildSalesOrderSubtotalRow(
                    $soData['ordered_qty'],
                    $soData['remaining_qty'],
                    $orderBilledQty
                );
            } else {
                // Add SO row with no bill data
                $result[] = [
                    'so_no'                => $soData['so_number'],
                    'so_date'              => $soData['so_date'],
                    'purchase_order_number' => $soData['purchase_order_number'],
                    'customer_name'        => $soData['customer_name'],
                    'customer_city'        => $soData['customer_city'],
                    'product_name'         => $soData['product_name'],
                    'broker_name'          => $soData['broker_name'],
                    'remaining_qty'        => number_format($soData['remaining_qty'], 3, '.', ''),
                    'destination_name'     => $soData['destination_name'],
                    'rate'                 => number_format($soData['rate'], 2, '.', ''),
                    'qty'                  => number_format($soData['ordered_qty'], 3, '.', ''),
                    'invoice_date'         => '-',
                    'grn_no'               => '-',
                    'invoice_no'           => '-',
                    'vehicle_no'           => '-',
                    'bags'                 => '-',
                    'p_qty'                => '0.000',
                    'rec_qty'              => '0.000',
                ];

                // Add subtotal row
                $result[] = $this->buildSalesOrderSubtotalRow(
                    $soData['ordered_qty'],
                    $soData['remaining_qty'],
                    0.0
                );

                $totalOrderQty += $soData['ordered_qty'];
                $totalRemainingQty += $soData['remaining_qty'];
            }
        }

        return [
            'data' => $result,
            'footerData' => [
                'order_qty_total' => number_format($totalOrderQty, 3, '.', ''),
                'rec_qty_total' => number_format($totalBilledQty, 3, '.', ''),
                'rem_qty_total' => number_format($totalRemainingQty, 3, '.', ''),
            ]
        ];
    }

    private function buildSalesOrderSubtotalRow($orderedQty, $remainingQty, $billedQty)
    {
        return [
            'so_no' => '',
            'so_date' => '',
            'customer_name' => '',
            'customer_city' => '',
            'product_name' => '',
            'broker_name' => '',
            'remaining_qty' => number_format($remainingQty, 3, '.', ''),
            'destination_name' => '',
            'rate' => 'Total',
            'qty' => number_format($orderedQty, 3, '.', ''),
            'invoice_date' => '',
            'grn_no' => '',
            'invoice_no' => '',
            'vehicle_no' => '',
            'bags' => '',
            'p_qty' => '',
            'rec_qty' => number_format($billedQty, 3, '.', ''),
        ];
    }
}

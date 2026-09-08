<?php

namespace App\Http\Controllers;

use App\Services\MasterDataService;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Models\SalesOrder;
use App\Services\LookupService;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use Throwable;

class SalesOrderController extends Controller
{
    protected SalesOrderService $service;
    protected MasterDataService $masterService;
    protected LookupService $lookupService;

    public function __construct(SalesOrderService $service, MasterDataService $masterService, LookupService $lookupService)
    {
        $this->middleware('permission:sales_order.create')->only(['create', 'store']);
        $this->middleware('permission:sales_order.update')->only(['edit', 'update']);
        $this->middleware('permission:sales_order.delete')->only('destroy');
        $this->middleware('permission:sales_order.restore')->only('restore');
        $this->service = $service;
        $this->masterService = $masterService;
        $this->lookupService = $lookupService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $filters = $request->only([
                'so_id',
                'start_date',
                'end_date',
                'account_id',
                'broker_id',
                'item_id',
                'order_status',
                'condition_id',
                'due_status'
            ]);
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
            $result = $this->service->salesOrderList(companyId: company_id(), financialYearId: financial_year_id(), filters: $filters);
            // dd($result['data']);
            return response()->json($result);
        }
        $masterData   = $this->masterService->salesOrderMasterData(company_id());
        $orderSerials = $this->service->getOrdersSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        extract($this->extractMasterData($masterData));
        return view('company.pages.sales-order.index', compact(
            'customers',
            'brokers',
            'conditions',
            'items',
            'destinations',
            'orderSerials',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $masterData = $this->masterService->salesOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));
        $defaultDeliveryDays = SalesOrder::DEFAULT_DELIVERY_DAYS;

        $orderInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        return view('company.pages.sales-order.create', [
            'customers'          => $customers,
            'brokers'            => $brokers,
            'conditions'         => $conditions,
            'defaultDeliveryDays' => $defaultDeliveryDays,
            'orderSerial'        => $orderInfo->order_serial,
            'orderNumber'        => $orderInfo->order_number,
            'items'              => $items,
            'destinations'       => $destinations
        ]);
    }

    /**
     * Store a newly created sales order in storage.
     */
    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $companyId = company_id();
        $brokers = $this->lookupService->getBrokers($companyId, 'self');

//         $request->merge([
//             'broker_id' => $brokers[0]->id
//         ]);
// dd($request);

//         $validated = $request->safe()->merge([
//             'broker_id' => $brokers[0]->id
//         ])->all();

        // dd($validated, $request->validated());

        try {
            $salesOrder = $this->service->createSalesOrder(
                $request->validated(),
                // data: $validated,
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: "Sales Order Number <b class='text-primary'>{$salesOrder->order_serial}</b> has been created successfully.",
                data: $salesOrder
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_order.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        if ($request->ajax()) {
            $id = $salesOrder->id;
            if ($id) {
                $data = $this->service->getViewData(
                    salesOrderId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$data) {
                return AjaxResponse::error('Sales order not found.', code: 404);
            }
            return AjaxResponse::success(
                message: __('messages.sales_order.fetched'),
                data: $data
            );
        }

        return AjaxResponse::error(message: __('messages.request.type'));
    }

    /**
     * Edit the form for editing the specified resource.
     */
    public function edit(Request $request, ?int $salesOrderId = null): View|JsonResponse
    {
        if ($request->ajax() && $request->has('sales_order_id')) {
            // dd('edit', $request->all());
            $id = $request->sales_order_id;
            if ($id) {
                $salesOrder = $this->service->getEditData(
                    salesOrderId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }

            // dd('salesOrder', $salesOrder->toArray());

            if (!$salesOrder) {
                return AjaxResponse::error('Sales order not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.sales_order.fetched'),
                data: $salesOrder
            );
        }

        $orderSerials = $this->service->getOrdersSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $masterData = $this->masterService->salesOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));
        return view('company.pages.sales-order.edit', compact(
            'customers',
            'brokers',
            'conditions',
            'items',
            'destinations',
            'orderSerials',
            'salesOrderId'
        ));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $companyId = company_id();
        $brokers = $this->lookupService->getBrokers($companyId, 'self');

        $request->merge([
            'broker_id' => $brokers[0]->id
        ]);

        $validated = $request->safe()->merge([
            'broker_id' => $brokers[0]->id
        ])->all();

        // dd($validated, $request->validated());
        try {
            if (!$salesOrder) {
                return AjaxResponse::error(
                    message: __('messages.sales_order.not_found'),
                    code: 404
                );
            }
            $updatedOrder = $this->service->updateSalesOrder(
                orderId: $salesOrder->id,
                // data: $request->validated(),
                data: $validated,
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: __('messages.sales_order.updated'),
                data: $updatedOrder
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_order.throwable_error'),
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        try {
            // $this->service->deleteSalesOrder($salesOrder);
            return AjaxResponse::success(
                message: __('messages.sales_order.deleted')
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_order.throwable_error'),
                code: 500
            );
        }
    }
    
     public function closeMultiple(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return AjaxResponse::error(message: 'No sales orders selected for closing.', code: 400);
            }

            // Update only rows matching current company for safety
            SalesOrder::whereIn('id', $ids)
                ->where('company_id', company_id())
                ->update(['order_status' => SalesOrder::STATUS_CLOSE]);

            return AjaxResponse::success(message: 'Selected sales orders have been closed.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_order.throwable_error'),
                code: 500
            );
        }
    }


    public function SalesOrderDetailWithBill(Request $request): View|JsonResponse{
        if ($request->ajax()) {

            $filters = $request->only([
                "start_date",
                "end_date",
                "account_id",
                "item_id",
                "broker_id",
                "destination_id",
                "order_status",
            ]);

            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);

            $result = $this->service->salesOrderDetailList(companyId: company_id(), financialYearId: financial_year_id(), filters: $filters);
            return response()->json($result);
        }

        $masterData = $this->masterService->salesOrderMasterData(company_id());

        extract($this->extractMasterData($masterData));

        return view('company.pages.sales-order.sales-order-detail-with-bill', compact(
            'customers',
            'brokers',
            'items',
            'destinations',
            'conditions',
        ));
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'customers'    => $masterData['customers'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
        ];
    }
}

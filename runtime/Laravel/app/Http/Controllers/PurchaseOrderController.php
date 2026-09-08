<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\MailService;
use App\Services\MasterDataService;
use App\Services\PurchaseOrderService;
use App\Helpers\AjaxResponse;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use Throwable;

class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $service;
    protected MasterDataService $masterService;

    public function __construct(PurchaseOrderService $service, MasterDataService $masterService)
    {
        $this->middleware('permission:purchase_order.create')->only(['create', 'store']);
        $this->middleware('permission:purchase_order.update')->only(['edit', 'update']);
        $this->middleware('permission:purchase_order.delete')->only('destroy');
        $this->middleware('permission:purchase_order.restore')->only('restore');

        $this->service = $service;
        $this->masterService = $masterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $filters = $request->only([
                'po_id',
                'account_id',
                'broker_id',
                'item_id',
                'destination_id',
                'start_date',
                'end_date',
                'order_status',
                'condition_id',
                'due_status'
            ]);

            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);

            $result = $this->service->purchaseOrderList(companyId: company_id(), financialYearId: financial_year_id(), filters: $filters);

            return response()->json($result);
        }


        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        $orderSerials = $this->service->getOrdersSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        extract($this->extractMasterData($masterData));

        return view('company.pages.purchase-order.index', compact(
            'accounts',
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
        $masterData = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));

        $defaultDeliveryDays = PurchaseOrder::DEFAULT_DELIVERY_DAYS;

        $orderInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        return view('company.pages.purchase-order.create', [
            'accounts'           => $accounts,
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
     * Store a newly created purchase order in storage.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        // dd("Pass Validation");
        try {
            $purchaseOrder = $this->service->createPurchaseOrder(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            session(['broker_id' => $purchaseOrder->broker_id . '_' . $purchaseOrder->company_id]);

            return AjaxResponse::success(
                message: "Purchase Order Number <b class='text-primary'>{$purchaseOrder->order_serial}</b> has been created successfully.",
                data: $purchaseOrder
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.purchase_order.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {

        // if (!$request->ajax()) {
        //     return AjaxResponse::error(message: __('messages.request.type'));
        // }
        // dd($purchaseOrder);
        if ($request->ajax()) {
            $id = $purchaseOrder->id;
            if ($id) {
                $data = $this->service->getViewData(
                    purchaseOrderId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$data) {
                return AjaxResponse::error('Purchase order not found.', code: 404);
            }
            // dd($data->toArray());
            return AjaxResponse::success(
                message: __('messages.purchase_order.fetched'),
                data: $data
            );
        }

        return AjaxResponse::error(message: __('messages.request.type'));
    }

    /**
     * Edit the form for editing the specified resource.
     */
    public function edit(Request $request, ?int $purchaseOrderId = null): View|JsonResponse
    {

        if ($request->ajax() && $request->has('purchase_order_id')) {
            $id = $request->purchase_order_id;
            if ($id) {
                $purchaseOrder = $this->service->getEditData(
                    purchaseOrderId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$purchaseOrder) {
                return AjaxResponse::error('Purchase order not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.purchase_order.fetched'),
                data: $purchaseOrder
            );
        }

        $orderSerials = $this->service->getOrdersSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $masterData = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));
        return view('company.pages.purchase-order.edit', compact(
            'orderSerials',
            'accounts',
            'brokers',
            'conditions',
            'items',
            'destinations',
            'purchaseOrderId'
        ));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {

            if (!$purchaseOrder) {
                return AjaxResponse::error(
                    message: __('messages.purchase_order.not_found'),
                    code: 404
                );
            }

            $updatedOrder = $this->service->updatePurchaseOrder(orderId: $purchaseOrder->id, data: $request->validated(), companyId: company_id(), financialYearId: financial_year_id());

            return AjaxResponse::success(
                message: __('messages.purchase_order.updated'),
                data: $updatedOrder
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.purchase_order.throwable_error'),
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            // Example: Uncomment when service method exists
            // $this->service->deletePurchaseOrder($purchaseOrder);

            return AjaxResponse::success(
                message: __('messages.purchase_order.deleted')
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.purchase_order.throwable_error'),
                code: 500
            );
        }
    }

    public function closeMultiple(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return AjaxResponse::error(message: 'No purchase orders selected for closing.', code: 400);
            }

            // Update only rows matching current company for safety
            PurchaseOrder::whereIn('id', $ids)
                ->where('company_id', company_id())
                ->update(['order_status' => PurchaseOrder::STATUS_CLOSE]);

            return AjaxResponse::success(message: 'Selected purchase orders have been closed.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.purchase_order.throwable_error'),
                code: 500
            );
        }
    }
    public function PurchaseOrderDetailWithGrn(Request $request): View|JsonResponse
    {
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

            $result = $this->service->purchaseOrderDetailList(companyId: company_id(), financialYearId: financial_year_id(), filters: $filters);
            // $result=$this->repository->getAllPoGrn(companyId: company_id(),financialYearId: financial_year_id());
            // dd($result);
            return response()->json($result);
        }

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());

        extract($this->extractMasterData($masterData));

        return view('company.pages.purchase-order.purchase-order-detail-with-grn', compact(
            'accounts',
            'brokers',
            'items',
            'destinations',
            'conditions',
        ));
    }

    public function checkContractUnique(Request $request): JsonResponse
    {
        $contractNumber = $request->input('contract_number');
        $brokerId = $request->input('broker_id');
        $purchaseOrderId = $request->input('purchase_order_id');

        if (!$contractNumber || !$brokerId) {
            return AjaxResponse::success(data: ['is_unique' => true]);
        }

        $query = PurchaseOrder::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where('contract_number', $contractNumber)
            ->where('broker_id', $brokerId);

        if ($purchaseOrderId) {
            $query->where('id', '!=', $purchaseOrderId);
        }

        $exists = $query->exists();

        if ($exists) {
            return AjaxResponse::error('Contract number already exists for this broker.', code: 422);
        }

        return AjaxResponse::success(data: ['is_unique' => true]);
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'accounts'     => $masterData['accounts'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
        ];
    }
}

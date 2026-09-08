<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\DeliveryChallan;
use App\Services\DeliveryChallanService;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreDeliveryChallanRequest;
use App\Http\Requests\UpdateDeliveryChallanRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class DeliveryChallanController extends Controller
{
    protected DeliveryChallanService $service;
    protected MasterDataService $masterService;

    public function __construct(DeliveryChallanService $service, MasterDataService $masterService)
    {
        $this->middleware('permission:delivery_challan.create')->only(['create', 'store']);
        $this->middleware('permission:delivery_challan.update')->only(['edit', 'update']);
        $this->middleware('permission:delivery_challan.delete')->only('destroy');

        $this->service = $service;
        $this->masterService = $masterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $filters = $request->all();
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
            $data = $this->service->list(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                filters: $filters
            );

            return response()->json($data);
        }

        $masterData = $this->masterService->deliveryChallanMasterData(company_id());
        extract($this->extractMasterData($masterData));

        $challanSerials = $this->service->deliveryChallanRepo->getChallanSerial(company_id(), financial_year_id());

        return view('company.pages.delivery-challan.index', [
            'customers'      => $customers,
            'brokers'        => $brokers,
            'items'          => $items,
            'destinations'   => $destinations,
            'conditions'     => $conditions,
            'challanSerials' => $challanSerials,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $serialInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $masterData = $this->masterService->deliveryChallanMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.delivery-challan.create', [
            'challanSerial' => $serialInfo->challan_serial,
            'challanNumber' => $serialInfo->challan_number,
            'customers'     => $customers,
            'brokers'       => $brokers,
            'items'         => $items,
            'destinations'  => $destinations,
            'conditions'    => $conditions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeliveryChallanRequest $request): JsonResponse
    {
        try {
            $data = $request->all();

            $challan = $this->service->createDeliveryChallan(
                $data,
                companyId: company_id(),
                financialYearId: financial_year_id(),
                creatorId: Auth::user()->id
            );

            return AjaxResponse::success(
                message: "Delivery Challan Number <b class='text-primary'>{$challan->challan_serial}</b> has been created successfully.",
                data: $challan
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.delivery_challan.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $challan = $this->service->deliveryChallanRepo->getViewData(
                $id,
                company_id(),
                financial_year_id()
            );

            if (!$challan) {
                return AjaxResponse::error(message: "Delivery Challan not found", code: 404);
            }

            return AjaxResponse::success(data: $challan);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: $e->getMessage(), code: 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ?int $id = null): View|JsonResponse
    {
        if ($request->ajax()) {
            $id = $id ?: $request->challan_id;
            if ($id) {
                $challan = $this->service->deliveryChallanRepo->getEditData(
                    $id,
                    company_id(),
                    financial_year_id()
                );
                
                if ($challan) {
                    return AjaxResponse::success(data: $challan);
                }
            }
            return AjaxResponse::error(message: "Delivery Challan not found", code: 404);
        }

        $challan = null;
        if ($id) {
            $challan = $this->service->deliveryChallanRepo->getEditData(
                $id,
                company_id(),
                financial_year_id()
            );

            if (!$challan) {
                abort(404, "Delivery Challan not found");
            }
        }

        $masterData = $this->masterService->deliveryChallanMasterData(company_id());
        extract($this->extractMasterData($masterData));

        $challanSerials = $this->service->deliveryChallanRepo->getChallanSerial(company_id(), financial_year_id());

        return view('company.pages.delivery-challan.edit', [
            'formMode'      => 'edit',
            'challan'       => $challan,
            'challanId'     => $id,
            'challanSerials' => $challanSerials,
            'customers'     => $customers,
            'brokers'       => $brokers,
            'items'         => $items,
            'destinations'  => $destinations,
            'conditions'    => $conditions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeliveryChallanRequest $request, DeliveryChallan $deliveryChallan): JsonResponse
    {
        try {
            $data = $request->all();

            $this->service->updateDeliveryChallan(
                $data,
                companyId: company_id(),
                financialYearId: financial_year_id(),
                updaterId: Auth::user()->id,
                challanId: $deliveryChallan->id
            );
            return AjaxResponse::success(
                message: 'Delivery Challan updated successfully.',
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.delivery_challan.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeliveryChallan $delivery_challan)
    {
        // Implementation for destroy
    }
  public function checkDuplicateReference(Request $request)
    {
        $referenceNumber = $request->query('reference_number');
        $accountId = $request->query('account_id');
        $id = $request->query('grn_id') ?? null;

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $exists = $this->service->isDuplicateReference(
            companyId: $companyId,
            financialYearId: $financialYearId,
            referenceNumber: $referenceNumber,
            accountId: $accountId,
            id : $id
        );

        $url = null;

        if ($exists) {
            $grnId = $this->service->getByReferenceNumberAndAccount(
                companyId: $companyId,
                financialYearId: $financialYearId,
                referenceNumber: $referenceNumber,
                accountId: $accountId
            );

            if ($grnId) {
                $url = route('grns.show', $grnId);
            }
        }

        return AjaxResponse::success(
            message: 'Reference check completed.',
            data: [
                'is_duplicate' => $exists,
                'url'          => $url,
            ]
        );
    }
    /**
     * Get the list of pending Sales Orders
     */
    public function fetchPendingSalesOrders(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $filters = [
                'account_id'      => $request->account_id,
                'broker_id'       => $request->broker_id,
                'item_id'         => $request->item_id,
                'destination_id'  => $request->destination_id,
                'sales_order_ids' => $request->sales_order_id
            ];

            // Implement fetchPendingSalesOrders in SalesOrderRepo/Service if not present
            $salesOrders = $this->service->salesOrderRepo->fetchPendingSalesOrders(
                $companyId,
                $financialYearId,
                $filters,
                ['id', 'order_serial', 'order_number', 'account_id', 'broker_id', 'delivery_date', 'due_date', 'order_status'],
                ['id', 'sales_order_id', 'item_id', 'destination_id', 'condition_id', 'ordered_qty', 'received_qty', 'rate', 'inclusive_rate', 'cgst_rate', 'sgst_rate', 'igst_rate']
            );

            return AjaxResponse::success(
                message: "Sales Orders Fetched Successfully",
                data: $salesOrders
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.delivery_challan.throwable_error'),
                code: 500
            );
        }
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
<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\PendingPurchaseOrderRequest;
use App\Http\Requests\StoreGrnRequest;
use App\Http\Requests\UpdateGrnRequest;
use App\Models\Account;
use App\Models\Grn;
use App\Services\GrnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;
use App\Services\MasterDataService;

class GrnController extends Controller
{
    protected GrnService $service;
    protected MasterDataService $masterService;
    public function __construct(GrnService $service, MasterDataService $masterService)
    {
        $this->middleware('permission:grn.create')->only(['create', 'store']);
        $this->middleware('permission:grn.update')->only(['edit', 'update']);
        $this->middleware('permission:grn.delete')->only('destroy');
        $this->middleware('permission:grn.restore')->only('restore');

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
                'grn_no',
                'grn_serial',
                'qc_status',
                'start_date',
                'end_date',
                'account_id',
                'item_id',
                'broker_id',
                'condition_id',
                'grn_status',
                'destination_id',
                'reference_number'
            ]);
    
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
    
            $result = $this->service->grnList(companyId: company_id(),financialYearId: financial_year_id(),filters: $filters);
            // dd($result);
            return response()->json($result);
        }
    

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        // dd($grnSerials->toArray());
        extract($this->extractMasterData($masterData));
        
        return view('company.pages.grn.index', compact(
            'accounts',
            'brokers',
            'items',
            'destinations',
            'conditions',
            'grnSerials',

            
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $grnInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.grn.create',[  
            'grnSerial' => $grnInfo->grn_serial,
            'grnNumber' => $grnInfo->grn_number,
            'accounts' => $accounts,
            'brokers' => $brokers,
            'items' => $items,
            'destinations' => $destinations,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGrnRequest $request): JsonResponse
    {
        try {

            $grn = $this->service->createGrn(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            return AjaxResponse::success(
                message: "GRN Number <b class='text-primary'>{$grn->grn_serial}</b> has been created successfully.",
                data: $grn
            );
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.grn.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, ?int $grnId = null): JsonResponse{
        
        if ($request->ajax() && $request->has('grn_id')) {
            $id = $request->grn_id;
            if ($id) {
                $grn = $this->service->getViewData(
                    grnId : $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$grn) {
                return AjaxResponse::error('Grn not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.grn.fetched'),
                data: $grn
            );
        }

        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        // dd($grnSerials->toArray(),$grnId,$grn);
        // return view('company.pages.grn.view', compact('grnSerials','grnId'));
        return AjaxResponse::success(
            message: __('messages.grn.fetched'),
            data: $grnSerials
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ?int $grnId = null): View|JsonResponse
    {
        
        if ($request->ajax() && $request->has('grn_id')) {
            $id = $request->grn_id;
            if ($id) {
                $grn = $this->service->getEditData(
                    grnId : $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$grn) {
                return AjaxResponse::error('Grn not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.grn.fetched'),
                data: $grn
            );
        }

        $supplierIds = Account::where('party_type', 'supplier')->where('company_id', company_id())->pluck('id')->toArray();

        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            entryFrom : '',
            selectedIds : $supplierIds
        );

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.grn.edit', [
            'grnSerials' => $grnSerials,
            'accounts' => $accounts,
            'brokers' => $brokers,
            'items' => $items,
            'destinations' => $destinations,
            'conditions' => $conditions,
            'grnId' => $grnId,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGrnRequest $request, Grn $grn)
    {
        $data            = $request->validated();
        $financialYearId = financial_year_id();
        $companyId       = company_id();
    
        try {
    
            $data = $this->service->updateGrn($data, $companyId, $financialYearId, $grn->id);
    
            return AjaxResponse::success(
                message: __('messages.grn.updated'),
                data:$data
            );
    
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            $msg = $e->getMessage();            
            $isKnownError = in_array($msg, [
                "Purchase Bill is already paid against this GRN",
                "Purchase GRN not found"
            ]); 

            return AjaxResponse::error(
                message: $isKnownError ? $msg : __('messages.grn.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Grn $grn)
    {
        try {
            if ($grn->company_id !== company_id()) {
                return AjaxResponse::error('Unauthorized access.', code: 403);
            }

            if ($grn->grn_status === Grn::STATUS_BILLED) {
                return AjaxResponse::error('Cannot delete GRN because it has already been billed.');
            }

            $this->service->revertGrnQtyFromPOs([$grn->id]);
            
            $grn->delete();
            return AjaxResponse::success(message: 'GRN deleted successfully.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: 'Failed to delete GRN.');
        }
    }

    public function deleteSelected(Request $request): JsonResponse
    {
        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return AjaxResponse::error('No records selected for deletion.');
        }

        try {
            $hasBilledGrn = Grn::whereIn('id', $ids)
                ->where('company_id', company_id())
                ->where('grn_status', Grn::STATUS_BILLED)
                ->exists();

            if ($hasBilledGrn) {
                return AjaxResponse::error('Cannot delete selected GRNs because one or more have already been billed.');
            }

            $this->service->revertGrnQtyFromPOs($ids);

            Grn::whereIn('id', $ids)
                ->where('company_id', company_id())
                ->delete();

            return AjaxResponse::success(message: 'Selected GRNs have been deleted successfully.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: 'Failed to delete selected GRNs.');
        }
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
     * Get the list of pending POs
     * @param Request $request
     * @return void
     */

    public function fetchPendingPurchaseOrders(PendingPurchaseOrderRequest $request)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $filters = [
                'account_id' => $request->account_id,
                'broker_id'  => $request->broker_id,
                'item_id'    => $request->item_id,
                'purchase_order_ids'  => $request->purchase_order_id
            ];

            $purchaseOrders = $this->service->fetchPendingPurchaseOrders(companyId: $companyId, financialYearId: $financialYearId, filters: $filters);

            return AjaxResponse::success(
                message: "Purchase Orders Fetched Successfully",
                data: $purchaseOrders
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.grn.throwable_error'),
                code: 500
            );
        }
    }

    /**
     * Get the last GRN ID for printing
     */
    public function getLastGrnId(Request $request): JsonResponse
    {
        $lastGrn = Grn::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->orderBy('id', 'desc')
            ->first();

        return response()->json(['id' => $lastGrn->id ?? null]);
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'accounts'      => $masterData['accounts'] ?? [],
            'brokers'       => $masterData['brokers'] ?? [],
            'conditions'    => $masterData['conditions'] ?? [],
            'items'         => $masterData['items'] ?? [],
            'destinations'  => $masterData['destinations'] ?? [],
        ];
    }

}

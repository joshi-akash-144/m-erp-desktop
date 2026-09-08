<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductInSelfRequest;
use App\Http\Requests\UpdateProductOutSelfRequest;
use App\Services\GodownModuleService;
use App\Services\MasterDataService;
use App\Repositories\GodownModuleRepository;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreGodownModuleRequest;
use App\Http\Requests\StoreProductInRequest;
use App\Http\Requests\StoreProductOutRequest;
use App\Http\Requests\UpdateProductInRequest;
use App\Http\Requests\UpdateProductOutRequest;
use App\Models\GodownModule;
use App\Models\Grn;
use App\Models\DeliveryChallan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;

class GodownModuleController extends Controller
{
    protected GodownModuleRepository $godownRepository;
    protected GodownModuleService $godownService;
    protected MasterDataService $masterService;

    public function __construct(GodownModuleRepository $godownRepository, GodownModuleService $godownService, MasterDataService $masterService)
    {
        $this->middleware('permission:godown.create')->only(['create', 'store']);
        $this->middleware('permission:godown.update')->only(['edit', 'update']);
        $this->middleware('permission:godown.delete')->only('destroy');
        $this->middleware('permission:godown.restore')->only('restore');

        $this->godownRepository = $godownRepository;
        $this->godownService = $godownService;
        $this->masterService = $masterService;
    }

    public function index(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $filters = $request->all();
        if ($request->ajax()) {
            // $response = $this->godownService->getGodowns($companyId, $financialYearId, $filters);
            $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
            $godownData = $godownDataResponse['godownData'];
            $permissions = $godownDataResponse['permissions'];
            return response()->json([
                'data' => $godownData,
                'permissions' => $permissions,
            ]);
        }

        $data = [
            'filters'      => $filters,
            'accounts'     => $this->masterService->getCreditors($companyId)->merge($this->masterService->getDebtors($companyId)),
            'items'        => $this->masterService->get('items', $companyId),
            'destinations' => $this->masterService->get('destinations', $companyId),
            'godownUnits'  => $this->masterService->get('godown_units', $companyId),
            'grns'         => Grn::where('company_id', $companyId)->where('financial_year_id', $financialYearId)->get(['id', 'grn_serial']),
            'challans'     => DeliveryChallan::where('company_id', $companyId)->where('financial_year_id', $financialYearId)->get(['id', 'challan_serial']),
        ];

        return view('company.pages.godown.index', $data);
    }
    /**
     * Display the Product In page
     */
    public function productIn(): View
    {
        return $this->renderView('company.pages.godown.product-in.index');
    }

    /**
     * Display the Product Out page
     */
    public function productOut(): View
    {
        return $this->renderView('company.pages.godown.product-out.index');
    }

    /**
     * Display the Product In Manual page
     */
    public function productInManual(): View
    {
        return $this->renderView('company.pages.godown.product-in-manual.index');
    }

    /**
     * Display the Product Out Manual page
     */
    public function productOutManual(): View
    {
        return $this->renderView('company.pages.godown.product-out-manual.index');
    }

    /**
     * Helper to render godown views with necessary data
     */
    private function renderView(string $viewPath): View
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $type = str_contains($viewPath, 'product-out') ? 'out' : 'in';
        $grnInfo = $this->godownService->getNextVoucherNumber($companyId, $financialYearId);
        $vehicles = $this->godownService->getVehiclesForGodown($companyId, $financialYearId, $type);
        $deliveryChallanInfo = $this->godownService->getNextDeliveryChallanVoucherNumber($companyId, $financialYearId);

        $lastGodownEntry = GodownModule::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('in_out_status', $type)
            ->latest('id')
            ->first();

        $data = [
            'brokers'          => $this->masterService->get('brokers', $companyId),
            'items'            => $this->masterService->get('items', $companyId),
            'suppliers'        => $this->masterService->get('suppliers', $companyId),
            'customers'        => $this->masterService->get('customers', $companyId,['id', 'name','city']),
            'destinations'     => $this->masterService->get('destinations', $companyId),
            'transporters'     => $this->masterService->get('transporters', $companyId),
            'weight_locations' => $this->masterService->get('weight_locations'),
            'last_grn_serial'  => $grnInfo->grn_serial ?? '000',
            'last_challan_serial' => $deliveryChallanInfo->challan_serial ?? '000',
            'vehicles'         => $vehicles,
            'last_godown_entry_id'      => $lastGodownEntry?->id,
            'accounts' => $this->masterService->get('suppliers',$companyId,['id', 'name','city'])->merge($this->masterService->get('customers',$companyId,['id', 'name','city']))->sortBy('name'),
        ];

        if (str_contains($viewPath, 'product-in-self')) {
            $data['grn_serial'] = Grn::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereIn('id', function($query) use ($type) {
                    $query->select('grn_id')
                          ->from('godown_modules')
                          ->where('in_out_status', $type);
                })
                ->pluck('grn_serial', 'grn_serial');
        }

        if (str_contains($viewPath, 'product-out-self')) {
            $data['grn_serial'] = Grn::where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->whereIn('id', function($query) use ($type) {
                    $query->select('grn_id')
                          ->from('godown_modules')
                          ->where('in_out_status', $type);
                })
                ->pluck('grn_serial', 'grn_serial');
        }

        return view($viewPath, $data);
    }

    /**
     * AJAX endpoint to fetch godowns based on destination ID
     */
    public function getGodowns(Request $request): JsonResponse
    {
        $godownId = $request->input('godown_id') ?? $request->input('destination_id');
        if (!$godownId) return response()->json([], 200);
    
        return response()->json($this->masterService->godownsMasterData((int) $godownId));
    }

    /**
     * AJAX endpoint to fetch GRN details by serial
     */
    public function fetchGrnDetails(Request $request): JsonResponse
    {
        $grnSerial = $request->input('grn_serial');
        $type = $request->input('type');
        if (!$grnSerial) return response()->json(['error' => 'GRN serial is required'], 400);

        $grn = $this->godownService->getGrnBySerial((int)$grnSerial, company_id(), financial_year_id());
        
        if (!$grn) return response()->json(['error' => 'GRN not found'], 404);

        $grn->godown_module = GodownModule::where('grn_id', $grn->id)
            ->when($type, function ($q) use ($type) {
                $q->where('in_out_status', $type);
            })
            ->with('transporter:id,name')
            ->first();

        return response()->json($grn);
    }

    public function fetchDeliveryChallanDetails(Request $request): JsonResponse
    {
        $challanSerial = $request->input('challan_serial');
        if (!$challanSerial) return response()->json(['error' => 'Challan serial is required'], 400);

        $deliveryChallan = $this->godownService->getDeliveryChallanBySerial((int)$challanSerial, company_id(), financial_year_id());
        
        if (!$deliveryChallan) return response()->json(['error' => 'Delivery Challan not found'], 404);
// dd($deliveryChallan);
        return response()->json($deliveryChallan);
    }
    public function checkDuplicateLrNumber(Request $request): JsonResponse
    {
        $lrNumber = $request->query('lr_number');
        $transporterId = $request->query('transporter_id');
        $id = $request->query('godown_id') ?? null;

        // Use strlen() instead of !$lrNumber because PHP treats "0" as falsy,
        // which would incorrectly skip the duplicate check for lr_number = 0.
        if (strlen((string) $lrNumber) === 0 || !$transporterId) {
            return response()->json(['data' => ['is_duplicate' => false]]);
        }

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $exists = $this->godownService->isDuplicateLrNumber(
            companyId: $companyId,
            financialYearId: $financialYearId,
            lrNumber: $lrNumber,
            transporterId: (int) $transporterId,
            id: $id ? (int) $id : null
        );

        return response()->json([
            'data' => [
                'is_duplicate' => false,
            ]
        ]);
    }

    public function storeProductIn(StoreProductInRequest $request)
    {
        $data = $request->validated();
        try {
            $record = $this->godownService->storeProductIn(
                $data,
                companyId: company_id(),
                financialYearId: financial_year_id(),
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.created'),
                'data'    => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        // response return
    }
    public function updateProductIn(UpdateProductInRequest $request, GodownModule $godownModule)
    {
        $data = $request->validated();
        try {
            $record = $this->godownService->updateProductIn(
                $godownModule,
                $data,
                company_id(),
                financial_year_id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Product In updated successfully',
                'data'    => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function storeProductOut(StoreProductOutRequest $request)
    {
        $data = $request->validated();
        try {
            $record = $this->godownService->storeProductOut(
                $data,
                companyId: company_id(),
                financialYearId: financial_year_id(),
            );
// dd($record);
            $responseData = $record->toArray();
            $responseData['grn_serial'] = $record->grn_serial ?? '';

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.created'),
                'data'    => $responseData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        // response return
    }
    public function updateProductOut(UpdateProductOutRequest $request, GodownModule $godownModule)
    {
        $data = $request->validated();
        try {
            $record = $this->godownService->updateProductOut(
                $godownModule,
                $data,
                company_id(),
                financial_year_id()
            );

            $responseData = $record->toArray();
            $responseData['grn_serial'] = $record->grn_serial ?? '';

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.updated'),
                'data'    => $responseData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created godown module record.
     */
    public function store(StoreGodownModuleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Manual Vehicle Change Validation
            if ($request->input('is_set_vehicle') == 1) {
                $vehicleNo = $request->input('vehicle_number');
                $referenceNo = $request->input('reference_number');
                $grnId = $request->input('grn_id');
                $deliveryChallanId = $request->input('delivery_challan_id');
                $inOutStatus = $request->input('in_out_status');

                if ($inOutStatus === 'out') {
                    $exists = DeliveryChallan::where('id', $deliveryChallanId)
                        ->where('vehicle_number', $vehicleNo)
                        ->where('reference_number', $referenceNo)
                        ->exists();
                } else {
                    $exists = Grn::where('id', $grnId)
                        ->where('vehicle_number', $vehicleNo)
                        ->where('reference_number', $referenceNo)
                        ->exists();
                }

                if (!$exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vehicle Number Not Found.'
                    ], 422);
                }
            }

            // Create record via service
            $record = $this->godownService->store(
                $data,
                companyId: company_id(),
                financialYearId: financial_year_id(),
                userId: auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.created'),
                'data'    => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.godown_module.throwable_error')
            ], 500);
        }
    }

    public function destroy(GodownModule $godown)
    {
        // if ($godown->in_out_status === 'out' && $godown->deliveryChallan && $godown->deliveryChallan->challan_status === 'close') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Delivery challan is in use and cannot be deleted.'
        //     ], 400);
        // }

       if ($godown->in_out_status === 'out' && $godown->grn && $godown->grn->grn_status === 'close') {
            return response()->json([
                'success' => false,
                'message' => 'GRN is in use and cannot be deleted.'
            ], 400);
        }
        

        try {
            DB::transaction(function () use ($godown) {
                if ($godown->in_out_status === 'out' && $godown->grn) {
                    $godown->grn->update(['deleted_by' => auth()->id()]);
                    $godown->grn->delete();
                }

                $this->godownService->delete($godown);
            });

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.deleted')
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => __('messages.godown_module.throwable_error'),
                'errors' => $exception->getMessage()
            ], 500);
        }
    }
    public function transporterList(Request $request)
    {
        if ($request->ajax()) {
            $result     = $this->godownRepository->getTransporterListData(company_id(), financial_year_id(), $request->all());
            $grandTotal = $this->godownRepository->countAllTransporters(company_id(), financial_year_id());
            return response()->json([
                'data'        => $result['data'],
                'total'       => $result['total'],
                'last_page'   => $result['last_page'],
                'grand_total' => $grandTotal,
            ]);
        }

        $transporters = $this->masterService->get('transporters', company_id());

        return view('company.pages.godown.transporter.index', compact('transporters'));
    }

    public function productInSelf()
    {
        return $this->renderView('company.pages.godown.product-in-self.index');
    }

    public function updateProductInSelf(UpdateProductInSelfRequest $request,GodownModule $godownModule)
    {
            $data = $request->validated();
        try {
            $record = $this->godownService->updateProductInSelf(
                $godownModule,
                $data,
                company_id(),
                financial_year_id()
            );

            $responseData = $record->toArray();
            $responseData['grn_serial'] = $record->grn_serial ?? '';

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.updated'),
                'data'    => $responseData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
    public function productOutSelf()
    {
        return $this->renderView('company.pages.godown.product-out-self.index');
    }
    public function updateProductOutSelf(UpdateProductOutSelfRequest $request,GodownModule $godownModule)
    {
            $data = $request->validated();
        try {
            $record = $this->godownService->updateProductOutSelf(
                $godownModule,
                $data,
                company_id(),
                financial_year_id()
            );

            $responseData = $record->toArray();
            $responseData['grn_serial'] = $record->grn_serial ?? '';

            return response()->json([
                'success' => true,
                'message' => __('messages.godown_module.updated'),
                'data'    => $responseData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
    public function checkDuplicateLrNo(Request $request): JsonResponse
    {
        $lrNumber = $request->query('lr_number');
        $id = $request->query('godown_id') ?? null;
        // $inOutStatus = $request->query('in_out_status');
        $transporterId = $request->query('transporter_id');

        // Use strlen() instead of !$lrNumber because PHP treats "0" as falsy,
        // which would incorrectly skip the duplicate check for lr_number = 0.
        if (strlen((string) $lrNumber) === 0 || ! $transporterId) {
            return response()->json(['data' => ['is_duplicate' => false]]);
        }

        // Only check for duplicates if in_out_status is 'out'
        // if ($inOutStatus !== 'out') {
        //     return response()->json(['data' => ['is_duplicate' => false]]);
        // }

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $exists = $this->godownService->isDuplicateLrNo(
            companyId: $companyId,
            financialYearId: $financialYearId,
            lrNumber: $lrNumber,
            transporterId: (int) $transporterId,
            id: $id ? (int) $id : null
        );

        return response()->json([
            'data' => [
                'is_duplicate' => $exists,
            ]
        ]);
    }
}

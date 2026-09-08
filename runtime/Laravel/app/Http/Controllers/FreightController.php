<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFreightRequest;
use App\Http\Requests\UpdateFreightRequest;
use App\Models\Freight;
use App\Models\VoucherType;
use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Services\FreightService;
use App\Services\VoucherService;

class FreightController extends Controller
{
    protected MasterDataService $masterDataService;
    protected FreightService $freightService;
    protected VoucherService $voucherService;

    public function __construct(MasterDataService $masterDataService, FreightService $freightService, VoucherService $voucherService)
    {
        $this->masterDataService = $masterDataService;
        $this->freightService = $freightService;
        $this->voucherService = $voucherService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',
                'item_id',
                'vehicle_id',
                'from_destination_id',
                'to_destination_id',
                'grn_id',
                'lr_number_id',
                'consignor_id',
                'consignee_id',
                'bill_id',
            ]);

            $filters['company_id'] = company_id();
            $filters['financial_year_id'] = financial_year_id();
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);

            $result = $this->freightService->freightList($filters);
            return response()->json($result);
        }

        $companies = $this->masterDataService->getDebtors(company_id());
        $items = $this->masterDataService->get('items', company_id());
        $destinations = $this->masterDataService->get('destinations', company_id());
        $vehicles = $this->masterDataService->get('vehicles', company_id());
        $consignors = $this->masterDataService->get('transportParties', company_id(),['id','name','city']);
        $consignees = $this->masterDataService->get('transportParties', company_id(),['id','name','city']);

        $bills = Freight::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($bill) {
                if ($bill->prefix === 'FRI -') {
                    $bill->bill_number = (string) $bill->reference_number;
                } else {
                    $bill->bill_number = $bill->prefix . $bill->reference_number;
                }
                return $bill;
            });

        return view('company.pages.freight.index', compact('companies', 'items', 'destinations', 'vehicles', 'consignors', 'consignees', 'bills'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = $this->masterDataService->getDebtors(company_id());
        $items = $this->masterDataService->get('items', company_id());
        $destinations = $this->masterDataService->get('destinations', company_id());
        $vehicles = $this->masterDataService->get('vehicles', company_id());
        $transportParties = $this->masterDataService->get('transportParties', company_id(), ['id', 'name', 'city']);
// dd($transportParties->toArray());
        $serialInfo = $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, company_id(), financial_year_id());

        return view('company.pages.freight.create', compact('accounts', 'items', 'destinations', 'vehicles', 'transportParties', 'serialInfo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFreightRequest $request)
    {
        try {
            $freight = $this->freightService->store($request->validated(), company_id(), financial_year_id());
            $freight->load('voucher');

            return response()->json([
                'success' => true,
                'data' => $freight,
                'message' => 'Freight created successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $accounts = $this->masterDataService->getDebtors(company_id());
        $items = $this->masterDataService->get('items', company_id());
        $destinations = $this->masterDataService->get('destinations', company_id());
        $vehicles = $this->masterDataService->get('vehicles', company_id());
        $transportParties = $this->masterDataService->get('transportParties', company_id());

        $freight = Freight::with(['items.item', 'voucher', 'fromDestination', 'toDestination', 'consignor', 'consignee'])->findOrFail($id);

        $serialInfo = (object) [
            'serial' => $freight->invoice_serial,
            'voucher_number' => $freight->invoice_number
        ];

        return view('company.pages.freight.edit', compact('accounts', 'items', 'destinations', 'vehicles', 'transportParties', 'freight', 'serialInfo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFreightRequest $request, string $id)
    {
        try {
            $freight = $this->freightService->update($id, $request->validated(), company_id(), financial_year_id());

            return response()->json([
                'success' => true,
                'data' => $freight,
                'message' => 'Freight updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    /**
     * Get Grn data.
     * Resolves the GodownModule company from the 'account_id' (Bill To)
     * using the config/prefix.php 'account_company' map.
     */
    public function getGrnData(string $grnSerial)
    {
        $accountId = request()->query('account_id');
        $clientCompanyId = config('prefix.account_company.' . $accountId);

        $grn = $this->freightService->getGrnBySerial((int) $grnSerial, $clientCompanyId, financial_year_id());

        if ($grn) {
            return response()->json([
                'success' => true,
                'data' => $grn
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'GRN not found'
        ]);
    }

    /**
     * Get GodownModule data by LR Number.
     * Resolves the GodownModule company from the 'account_id' (Bill To)
     * using the config/prefix.php 'account_company' map.
     */
    public function getLrNumberData(string $lrNumber)
    {
        $accountId = request()->query('account_id');
        $clientCompanyId = config('prefix.account_company.' . $accountId);

        $data = $this->freightService->getByLrNumber($lrNumber, $clientCompanyId, financial_year_id());

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'LR Number not found'
        ]);
    }

    /**
     * Get next bill number for the selected company.
     */
    public function getNextBillNumber(int $billedToCompanyId)
    {
        $result = $this->freightService->getNextBillNumber($billedToCompanyId, company_id(), financial_year_id());

        return response()->json([
            'success'        => true,
            'bill_number'    => $result['bill_number'],    // full format stored in DB
            'display_number' => $result['display_number'], // digits-only for no-prefix companies (UI)
            'serial'         => $result['serial'],
        ]);
    }

    /**
     * Print an individual freight record.
     */
    public function freightPrint(string $id)
    {
        $freight = Freight::with([
            'company.state',
            'consignor',
            'consignee',
            'toDestination',
            'fromDestination',
            'vehicle',
            'items.item'
        ])->findOrFail($id);

        $company_data = $freight->company;
        $freight_data = collect([$freight]);
        // dd($freight_data->toArray());
        $html = view('company.pages.freight.freight-print', compact('freight_data', 'company_data'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function checkDuplicateLRNumber(Request $request)
    {
        $lrNumber = $request->query('lr_number');
        $excludeId = $request->query('exclude_id');
        
        if (strlen((string) $lrNumber) === 0) {
            return response()->json(['data' => ['is_duplicate' => false]]);
        }

        $isDuplicate = $this->freightService->checkDuplicateLRNumber($lrNumber, company_id(), financial_year_id(), $excludeId);
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_duplicate' => $isDuplicate
            ]
        ]);
    }
}

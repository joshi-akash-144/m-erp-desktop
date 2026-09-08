<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DieselItem;
use App\Models\Driver;
use App\Models\Diesel;
use App\Models\FinancialYear;
use App\Models\Voucher;
use App\Services\DieselService;
use Carbon\Carbon;
use \Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\MasterDataService;

class DieselController extends Controller
{

    protected MasterDataService $masterDataService;

    public function __construct(MasterDataService $masterDataService)
    {
        $this->masterDataService = $masterDataService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = company_id();
        $accounts = $this->masterDataService->getCreditorAndDebtor($companyId);
        $vehicles = $this->masterDataService->get('vehicles', $companyId);

        $drivers = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();


        $vouchers = [];

        return view('company.pages.diesel.index', compact('accounts', 'vehicles', 'drivers', 'vouchers'));
    }

    public function list(Request $request, DieselService $dieselService)
    {
        $filters = $request->only([
            'account_id',
            'vehicle_id',
            'from_date',
            'to_date',
            'voucher_serial',
            'page',
            'size'
        ]);

        $filters['company_id'] = company_id();
        $filters['financial_year_id'] = financial_year_id();

        $result = $dieselService->getRegisterList($filters);

        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId       = company_id();

        $accounts = $this->masterDataService->getCreditorAndDebtor($companyId);

        $vehicles = $this->masterDataService->get('vehicles', $companyId);

        $drivers = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();


        return view("company.pages.diesel.create", compact("accounts", "vehicles", "drivers"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DieselService $dieselService)
    {
        $data = $request->validate([
            'uuid'         => 'required|string',
            'voucher_date' => 'required|date_format:d-m-Y',
            'account_id'   => 'required|integer|exists:accounts,id',
            // 'diesel_rate'  => 'required|numeric',
            'narration'    => 'nullable|string|max:500',
            'items'        => 'required|array',
            'items.*.challan_number' => 'required|string',
            'items.*.vehicle_id'     => 'required|integer|exists:vehicles,id',
            'items.*.driver_id'      => 'required|integer|exists:drivers,id',
            'items.*.last_date'      => 'nullable|string', // Dates might come as d-m-Y
            'items.*.today_date'     => 'nullable|string',
            'items.*.rate'           => 'required|numeric',
            'items.*.diesel'         => 'required|numeric',
            'items.*.old_km'         => 'nullable|numeric',
            'items.*.new_km'         => 'nullable|numeric',
            'items.*.amount'         => 'required|numeric',
            'items.*.diff'           => 'nullable|numeric',
            'items.*.average'        => 'nullable|numeric',
            'items.*.remark'         => 'nullable|string|max:255',
        ]);

        // Convert the voucher_date to Y-m-d format
        $data['voucher_date'] = Carbon::createFromFormat('d-m-Y', $data['voucher_date'])->format('Y-m-d');

        $challanNumbers = array_column($data['items'], 'challan_number');
        $duplicateChallan = \App\Models\DieselItem::whereIn('challan_number', $challanNumbers)
            ->whereHas('diesel', function ($q) {
                $q->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id());
            })->first();

        if ($duplicateChallan) {
            return response()->json(['message' => "Challan No {$duplicateChallan->challan_number} already exists in database."], 422);
        }

        try {
            $dieselService->store($data, company_id(), financial_year_id());
            return response()->json([
                'success' => true,
                'message' => 'Diesel entry saved successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $diesel = Diesel::with([
                'account:id,name',
                'details' => function($query) {
                    $query->with([
                        'vehicle:id,name',
                        'driver.account:id,name'
                    ])->orderBy('challan_number', 'asc');
                }
            ])->where('company_id', company_id())
              ->findOrFail($id);

            // Fetch the payment voucher serial for each item
            $voucherIds = $diesel->details->pluck('closed_by_voucher_id')->filter()->unique();
            $vouchers = Voucher::whereIn('id', $voucherIds)->pluck('voucher_serial', 'id');

            foreach ($diesel->details as $item) {
                $item->voucher_serial = $item->closed_by_voucher_id ? ($vouchers[$item->closed_by_voucher_id] ?? '-') : '-';
            }

            return response()->json([
                'success' => true,
                'data' => $diesel
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found or access denied.'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $companyId = company_id();
        $diesel = Diesel::with('details.vehicle', 'details.driver.account')->where('company_id', $companyId)->findOrFail($id);
        
        if ($diesel->details->every(function($item) { return $item->is_closed == 1; })) {
            abort(403, 'All items in this voucher are closed and cannot be edited.');
        }

        $accounts = $this->masterDataService->getCreditorAndDebtor($companyId);
        $vehicles = $this->masterDataService->get('vehicles', $companyId);
        $drivers = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();
            
        $items = $diesel->details;

        return view('company.pages.diesel.edit', compact('diesel', 'items', 'accounts', 'vehicles', 'drivers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id, DieselService $dieselService)
    {
        $diesel = Diesel::where('company_id', company_id())->findOrFail($id);
        
        if ($diesel->details->every(function($item) { return $item->is_closed == 1; })) {
            return response()->json(['message' => 'All items in this voucher are closed and cannot be updated.'], 403);
        }

        $data = $request->validate([
            'voucher_date' => 'required|date_format:d-m-Y',
            'account_id'   => 'required|integer|exists:accounts,id',
            'narration'    => 'nullable|string|max:500',
            'items'        => 'required|array',
            'items.*.challan_number' => 'required|string',
            'items.*.vehicle_id'     => 'required|integer|exists:vehicles,id',
            'items.*.driver_id'      => 'required|integer|exists:drivers,id',
            'items.*.last_date'      => 'nullable|string', 
            'items.*.today_date'     => 'nullable|string',
            'items.*.rate'           => 'required|numeric',
            'items.*.diesel'         => 'required|numeric',
            'items.*.old_km'         => 'nullable|numeric',
            'items.*.new_km'         => 'nullable|numeric',
            'items.*.amount'         => 'required|numeric',
            'items.*.diff'           => 'nullable|numeric',
            'items.*.average'        => 'nullable|numeric',
            'items.*.remark'         => 'nullable|string|max:255',
        ]);

        $data['voucher_date'] = Carbon::createFromFormat('d-m-Y', $data['voucher_date'])->format('Y-m-d');

        $challanNumbers = array_column($data['items'], 'challan_number');
        $duplicateChallan = \App\Models\DieselItem::whereIn('challan_number', $challanNumbers)
            ->where('diesel_id', '!=', $diesel->id)
            ->whereHas('diesel', function ($q) {
                $q->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id());
            })->first();

        if ($duplicateChallan) {
            return response()->json(['message' => "Challan No {$duplicateChallan->challan_number} already exists in database."], 422);
        }

        try {
            $dieselService->update($diesel, $data);
            return response()->json([
                'success' => true,
                'message' => 'Diesel entry updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $diesel = Diesel::where('id', $id)
                ->where('company_id', company_id())
                ->firstOrFail();

            if ($diesel->details()->where('is_closed', 1)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete diesel voucher because it contains one or more closed challans.',
                ], 403);
            }

            DB::transaction(function () use ($diesel) {
                $diesel->details()->delete();
                $diesel->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Diesel voucher deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Diesel voucher not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete diesel voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the latest diesel item data for a specific vehicle.
     */
    public function getVehicleLatestData(Request $request)
    {
        $vehicleId = $request->input('vehicle_id');

        $latestItem = DieselItem::where('vehicle_id', $vehicleId)
            ->whereHas('diesel', function ($query) {
                $query->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id());
            })
            ->orderBy('id', 'desc')
            ->first();

        if ($latestItem) {
            return response()->json([
                'success' => true,
                'data' => [
                    'last_date' => $latestItem->today_date ? Carbon::parse($latestItem->today_date)->format('d-m-Y') : null,
                    'old_km'    => $latestItem->new_km,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'data' => null
        ]);
    }

    public function registerPrint(Request $request, DieselService $dieselService)
    {
        if (!$request->filled('from_date') || !$request->filled('to_date')) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a date range to print this register.'
            ]);
        }

        $filters = $request->only([
            'account_id',
            'vehicle_id',
            'from_date',
            'to_date',
            'voucher_serial',
            'page',
            'size'
        ]);

        $filters['company_id'] = company_id();
        $filters['financial_year_id'] = financial_year_id();

        // Fetch all Diesel items that match the filters
        $query = DieselItem::with([
            'diesel.account',
            'vehicle',
            'driver.account'
        ])->whereHas('diesel', function($q) use ($filters) {
            $q->where('company_id', $filters['company_id'])
              ->where('financial_year_id', $filters['financial_year_id']);
              
            if (!empty($filters['account_id'])) {
                $q->where('account_id', $filters['account_id']);
            }
            if (!empty($filters['from_date'])) {
                $q->whereDate('voucher_date', '>=', $filters['from_date']);
            }
            if (!empty($filters['to_date'])) {
                $q->whereDate('voucher_date', '<=', $filters['to_date']);
            }
        });

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        $items = $query->get()->sortBy(function($item) {
            $dieselModel = $item->getRelation('diesel');
            return ($dieselModel?->account?->name ?? '') . '-' . str_pad($item->challan_number ?? '', 10, '0', STR_PAD_LEFT);
        })->values();

        $company       = Company::find(company_id());
        $financialYear = FinancialYear::find(financial_year_id());
        $fromDate      = $request->input('from_date') ?? $financialYear?->start_date;
        $toDate        = $request->input('to_date')   ?? $financialYear?->end_date;

        $html = view('company.pages.diesel.register-print', compact('items', 'company', 'fromDate', 'toDate'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function printVoucher(Diesel $diesel)
    {
        abort_unless($diesel->company_id === company_id(), 403);

        $expense = Diesel::with([
            'account:id,name',
            'details.vehicle:id,name',
            'details.driver.account:id,name',
        ])->findOrFail($diesel->id);

        // Sort details by challan_number ascending (handling numeric strings properly)
        $expense->details = $expense->details->sortBy(function ($item) {
            return (int) $item->challan_number;
        })->values();

        $company = Company::find(company_id());

        $html = view('company.pages.diesel.print', compact('expense', 'company'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}

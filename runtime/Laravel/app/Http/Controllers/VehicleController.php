<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreBrokerRequest;
use App\Http\Requests\UpdateBrokerRequest;
use App\Models\Broker;
use App\Repositories\CommonRepository;
use App\Services\DataTables\BrokerDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use App\Models\Vehicle;
use App\Models\VehicleOwner;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Services\DataTables\VehicleDataTable;
use App\Models\Driver;


class VehicleController extends Controller
{
    protected CommonRepository $commonRepository;   
    protected VehicleDataTable $dataTable;
    protected int $companyId;

    public function __construct(CommonRepository $commonRepository, VehicleDataTable $dataTable)
    {
        $this->middleware('permission:vehicle.list')->only(['index']);
        $this->middleware('permission:vehicle.create')->only(['create', 'store']);
        $this->middleware('permission:vehicle.update')->only(['edit', 'update']);
        $this->middleware('permission:vehicle.delete')->only('destroy');
        $this->middleware('permission:vehicle.restore')->only('restore');

        $this->commonRepository = $commonRepository;        
        $this->dataTable = $dataTable;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Vehicle::query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.vehicle.index');
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => __('titles.vehicle.add'),
            'uuid'          => Str::uuid(),
            'data'          => null,
            'form_mode'     => 'create',
            'vehicleOwners' => VehicleOwner::where('company_id', session('company_id'))->get(),
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'drivers' => Driver::with('account:id,name')->select('id','account_id')->where('company_id', session('company_id'))->get(),  
        ];       
         return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.vehicle._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();            

            $vehicle = Vehicle::create($validated);

            return AjaxResponse::success(message: __('messages.vehicle.created'), data: $vehicle, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    
    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => __('titles.vehicle.view'),
            'uuid'          => $vehicle->uuid,
            'data'          => $vehicle,
            'form_mode'     => 'view',
            'vehicleOwners' => VehicleOwner::where('company_id', session('company_id'))->get(),
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'drivers' => Driver::with('account:id,name')->select('id','account_id')->where('company_id', session('company_id'))->get(),            
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.vehicle._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function edit(Request $request, Vehicle $vehicle): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => __('titles.vehicle.edit'),
            'uuid'          => $vehicle->uuid,
            'data'          => $vehicle,
            'form_mode'     => 'edit',
            'vehicleOwners' => VehicleOwner::where('company_id', session('company_id'))->get(),
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'drivers' => Driver::with('account:id,name')->select('id','account_id')->where('company_id', session('company_id'))->get(),            
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.vehicle._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $vehicle->update($validated);

            return AjaxResponse::success(message: __('messages.vehicle.updated'), data: $vehicle, code: 200);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $hasIncome = \Illuminate\Support\Facades\DB::table('vehicle_incomes')
            ->where('vehicle_id', $vehicle->id)
            ->exists();
        if ($hasIncome) {
            return AjaxResponse::error(message: __('messages.vehicle.has_income'));
        }

        $hasExpense = \Illuminate\Support\Facades\DB::table('vehicle_expenses')
            ->where('vehicle_id', $vehicle->id)
            ->exists();
        if ($hasExpense) {
            return AjaxResponse::error(message: __('messages.vehicle.has_expense'));
        }

        $hasFreight = \Illuminate\Support\Facades\DB::table('freight_invoice_items')
            ->where('vehicle_id', $vehicle->id)
            ->exists();
        if ($hasFreight) {
            return AjaxResponse::error(message: __('messages.vehicle.has_freight'));
        }

        try {
            $vehicle->deleted_by = current_user_id();
            $vehicle->save();
            $vehicle->delete();

            return AjaxResponse::success(message: __('messages.vehicle.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    
}

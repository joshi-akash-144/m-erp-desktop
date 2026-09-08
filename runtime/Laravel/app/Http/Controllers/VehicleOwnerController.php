<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use App\Services\DataTables\VehicleOwnerDataTable;
use App\Models\VehicleOwner;
use App\Http\Requests\StoreVehicleOwnerRequest;
use App\Repositories\VehicleOwnerRepository;
use App\Http\Requests\UpdateVehicleOwnerRequest;

class VehicleOwnerController extends Controller
{
    protected CommonRepository $commonRepository;
    protected VehicleOwnerDataTable $dataTable;
    protected int $companyId;
    protected VehicleOwnerRepository $repository;

    public function __construct(VehicleOwnerDataTable $dataTable, CommonRepository $commonRepository, VehicleOwnerRepository $repository)
    {
        $this->middleware('permission:vehicle_owner.list')->only(['index']);
        $this->middleware('permission:vehicle_owner.create')->only(['create', 'store']);
        $this->middleware('permission:vehicle_owner.update')->only(['edit', 'update']);
        $this->middleware('permission:vehicle_owner.delete')->only('destroy');
        $this->middleware('permission:vehicle_owner.restore')->only('restore');

        $this->commonRepository = $commonRepository;
        $this->dataTable = $dataTable;
        $this->repository = $repository;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = VehicleOwner::query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.vehicle-owner.index');
    }

     public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.vehicle_owner.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];
         return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.vehicle-owner._modal', compact('modalData'))->render()],
            code: 200
        );
    }

 public function store(StoreVehicleOwnerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();           

            $vehicleOwner = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.vehicle_owner.created'),data: $vehicleOwner,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

    public function show(Request $request, VehicleOwner $vehicleOwner): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.vehicle_owner.view'),
            'uuid'      => null,
            'data'      => $vehicleOwner,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.vehicle-owner._modal', compact('modalData'))->render()
        ]);
    }
    /** Load Edit Modal */
    public function edit(Request $request, VehicleOwner $vehicleOwner): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.vehicle_owner.edit'),
            'uuid'      => $vehicleOwner->uuid,
            'data'      => $vehicleOwner,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.vehicle-owner._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdateVehicleOwnerRequest $request, VehicleOwner $vehicleOwner): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($vehicleOwner, $validated);

            return AjaxResponse::success(message: __('messages.vehicle_owner.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, VehicleOwner $vehicleOwner): JsonResponse
    {
        dd('working...');
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        if ($this->repository->canDelete($vehicleOwner)) {
            return AjaxResponse::error(message: __('messages.vehicle_owner.delete_error'));
        }
        try {
            $vehicleOwner->deleted_by = current_user_id();
            $vehicleOwner->save();
            $this->repository->delete($vehicleOwner);
            return AjaxResponse::success(message: __('messages.vehicle_owner.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
   
}

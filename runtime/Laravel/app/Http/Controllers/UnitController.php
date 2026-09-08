<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use App\Services\DataTables\UnitDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    protected UnitRepository $repository;
    protected UnitDataTable $dataTable;
    protected int $companyId;

    public function __construct(UnitRepository $repository, UnitDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:unit.list')->only(['index']);
        $this->middleware('permission:unit.create')->only(['create', 'store']);
        $this->middleware('permission:unit.update')->only(['edit', 'update']);
        $this->middleware('permission:unit.delete')->only('destroy');
        $this->middleware('permission:unit.restore')->only('restore');

        $this->repository = $repository;
        $this->dataTable = $dataTable;

        // Get company ID from session
        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    /** Index Page */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $units = Unit::select('uqc', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.unit.index', compact('units'));
    }

        /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.unit.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];
        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.unit._modal', compact('modalData'))->render(),
        ]);
    }

      /** Store */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $validated['code'] = $this->repository->nextCode($this->companyId);

            $unit = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.unit.created'),data: $unit,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, Unit $unit): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.unit.view'),
            'uuid'      => null,
            'data'      => $unit,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.unit._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, Unit $unit): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.unit.edit'),
            'uuid'      => $unit->uuid,
            'data'      => $unit,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.unit._modal', compact('modalData'))->render(),
        ]);
    }

   /** Update */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($unit, $validated);

            return AjaxResponse::success(message: __('messages.unit.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, Unit $unit): JsonResponse
    {
        dd('Work In Progress');
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        if ($this->repository->canDelete($unit)) {
            return AjaxResponse::error(message: __('messages.unit.has_unit_conversion'));
        }

        try {
            $unit->deleted_by = current_user_id();
            $unit->save();
            $this->repository->delete($unit);
            return AjaxResponse::success(message: __('messages.unit.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
}

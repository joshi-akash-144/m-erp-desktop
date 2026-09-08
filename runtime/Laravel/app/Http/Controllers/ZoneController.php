<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Models\Zone;
use App\Repositories\CommonRepository;
use App\Services\DataTables\ZoneDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ZoneController extends Controller
{
    protected CommonRepository $commonRepository;
    protected ZoneDataTable $dataTable;
    protected int $companyId;

    public function __construct(ZoneDataTable $dataTable, CommonRepository $commonRepository)
    {
        $this->middleware('permission:zone.list')->only(['index']);
        $this->middleware('permission:zone.create')->only(['create', 'store']);
        $this->middleware('permission:zone.update')->only(['edit', 'update']);
        $this->middleware('permission:zone.delete')->only('destroy');
        $this->middleware('permission:zone.restore')->only('restore');

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
            $query = Zone::query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        $zones = Zone::where('company_id', company_id())->get();

        return view('company.pages.masters.zone.index', compact('zones'));
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => 'Add New Zone',
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.zone._modal', compact('modalData'))->render(),
        ]);
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $zone = Zone::create($validated);

            return AjaxResponse::success(message: __('messages.zone.created'), data: $zone, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function show(Request $request, Zone $zone): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => "Zone View",
            'uuid'      => null,
            'data'      => $zone,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.zone._modal', compact('modalData'))->render(),
        ]);
    }

    public function edit(Request $request, Zone $zone): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => 'Edit Zone',
            'uuid'      => $zone->uuid,
            'data'      => $zone,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.zone._modal', compact('modalData'))->render(),
        ]);
    }

    public function update(UpdateZoneRequest $request, Zone $zone): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $zone->update($validated);

            return AjaxResponse::success(message: __('messages.zone.updated'), data: $zone);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Request $request, Zone $zone): JsonResponse
    {
        dd('working...');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $zone->update(['deleted_by' => current_user_id()]);
            $zone->delete();

            return AjaxResponse::success(message: __('messages.zone.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

}

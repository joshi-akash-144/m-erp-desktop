<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreUnitConversionRequest;
use App\Http\Requests\UpdateUnitConversionRequest;
use App\Repositories\CommonRepository;
use App\Models\UnitConversion;
use App\Repositories\UnitConversionRepository;
use App\Services\DataTables\UnitConversionDataTable;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitConversionController extends Controller
{
    protected UnitConversionRepository $repository;
    protected CommonRepository $commonRepository;
    protected UnitConversionDataTable $dataTable;
    protected int $companyId;

    public function __construct(UnitConversionRepository $repository, UnitConversionDataTable $dataTable,  CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:unit_conversion.list')->only(['index']);
        $this->middleware('permission:unit_conversion.create')->only(['create', 'store']);
        $this->middleware('permission:unit_conversion.update')->only(['edit', 'update']);
        $this->middleware('permission:unit_conversion.delete')->only('destroy');
        $this->middleware('permission:unit_conversion.restore')->only('restore');

        $this->repository = $repository;
        $this->commonRepository = $commonRepository;
        $this->dataTable = $dataTable;

        // Get company ID from session
        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

        /** Index Page */
    public function index(Request $request ): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $unitConversions = UnitConversion::select('main_unit_id', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.unit-conversion.index', compact('unitConversions'));
    }

        /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $unitConversions = $this->commonRepository->getUnits($this->companyId,['name','id']);

        $modalData = [
            'title'     => __('titles.unit_conversion.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'unitConversions' => $unitConversions,
            'form_mode' => 'create',
        ];
        return AjaxResponse::success(message:__('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.unit-conversion._modal', compact('modalData'))->render(),
        ]);
    }

      /** Store */
    public function store(StoreUnitConversionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = current_user_id();
            $validated['company_id'] = $this->companyId;

            $unitConversion = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.unit_conversion.created'),data: $unitConversion,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, UnitConversion $unitConversion): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $unitConversions = $this->commonRepository->getUnits($this->companyId,['name','id']);

        $modalData = [
            'title'     => __('titles.unit_conversion.view'),
            'uuid'      => null,
            'data'      => $unitConversion,
            'unitConversions'     => $unitConversions,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.unit-conversion._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, UnitConversion $unitConversion): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $unitConversions = $this->commonRepository->getUnits($this->companyId,['name','id']);

        $modalData = [
            'title'     => __('titles.unit_conversion.edit'),
            'uuid'      => $unitConversion->uuid,
            'data'      => $unitConversion,
            'unitConversions'     => $unitConversions,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.unit-conversion._modal', compact('modalData'))->render(),
        ]);
    }

   /** Update */
    public function update(UpdateUnitConversionRequest $request, UnitConversion $unitConversion): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($unitConversion, $validated);

            return AjaxResponse::success(message: __('messages.unit_conversion.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, UnitConversion $unitConversion): JsonResponse
    {
        dd('Work In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // if ($this->repository->hasAccounts($unitConversion)) {
        //     return AjaxResponse::error(__('messages.tax_category.has_accounts'));
        // }

        try {
            $unitConversion->deleted_by = current_user_id();
            $unitConversion->save();
            $this->repository->delete($unitConversion);
            return AjaxResponse::success(message:__('messages.unit_conversion.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

}

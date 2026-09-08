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

class BrokerController extends Controller
{
    protected CommonRepository $commonRepository;
    protected BrokerDataTable $dataTable;
    protected int $companyId;

    public function __construct(BrokerDataTable $dataTable, CommonRepository $commonRepository)
    {
        $this->middleware('permission:broker.list')->only(['index']);
        $this->middleware('permission:broker.create')->only(['create', 'store']);
        $this->middleware('permission:broker.update')->only(['edit', 'update']);
        $this->middleware('permission:broker.delete')->only('destroy');
        $this->middleware('permission:broker.restore')->only('restore');

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
            $query = Broker::query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.broker.index');
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states    = $this->commonRepository->getStates(['name', 'id']);

        $modalData = [
            'title'     => __('titles.broker.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'countries' => $countries,
            'states'    => $states,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.broker._modal', compact('modalData'))->render(),
        ]);
    }

    public function store(StoreBrokerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $broker = Broker::create($validated);

            return AjaxResponse::success(message: __('messages.broker.created'), data: $broker, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function show(Request $request, Broker $broker): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states    = $this->commonRepository->getStates(['name', 'id']);

        $modalData = [
            'title'     => __('titles.broker.view'),
            'uuid'      => null,
            'data'      => $broker,
            'form_mode' => 'view',
            'countries' => $countries,
            'states'    => $states,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.broker._modal', compact('modalData'))->render(),
        ]);
    }

    public function edit(Request $request, Broker $broker): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states    = $this->commonRepository->getStates(['name', 'id']);

        $modalData = [
            'title'     => __('titles.broker.edit'),
            'uuid'      => $broker->uuid,
            'data'      => $broker,
            'form_mode' => 'edit',
            'countries' => $countries,
            'states'    => $states,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.broker._modal', compact('modalData'))->render(),
        ]);
    }

    public function update(UpdateBrokerRequest $request, Broker $broker): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $broker->update($validated);

            return AjaxResponse::success(message: __('messages.broker.updated'), data: $broker);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Request $request, Broker $broker): JsonResponse
    {
        dd('Work In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $broker->deleted_by = current_user_id();
            $broker->save();
            $broker->delete();

            return AjaxResponse::success(message: __('messages.broker.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function restore(Request $request, Broker $broker): JsonResponse
    {
        try {
            $broker->restore();

            return AjaxResponse::success(message: __('messages.broker.restored'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }
}

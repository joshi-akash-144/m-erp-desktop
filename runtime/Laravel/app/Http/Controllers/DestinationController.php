<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use App\Repositories\DestinationRepository;
use App\Services\DataTables\DestinationDataTable;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DestinationController extends Controller
{
    protected DestinationRepository $repository;
    protected CommonRepository $commonRepository;
    protected DestinationDataTable $dataTable;
    protected int $companyId;

    public function __construct(DestinationRepository $repository, DestinationDataTable $dataTable,  CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:destination.list')->only(['index']);
        $this->middleware('permission:destination.create')->only(['create', 'store']);
        $this->middleware('permission:destination.update')->only(['edit', 'update']);
        $this->middleware('permission:destination.delete')->only('destroy');
        $this->middleware('permission:destination.restore')->only('restore');

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
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        $groups  =  Destination::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.destination.index', compact('groups'));        
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);

        $modalData = [
            'title'     => __('titles.destination.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'countries' => $countries,
            'states' => $states,
        ];
        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.destination._modal', compact('modalData'))->render(),
        ]);
    }

    /** Store */
    public function store(StoreDestinationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $destination = $this->repository->create($validated);


            return AjaxResponse::success(message: __('messages.destination.created'), data: $destination, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, Destination $destination): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);        
       
        $modalData = [
            'title'     => __('titles.destination.view'),
            'uuid'      => null,
            'data'      => $destination,
            'form_mode' => 'view',
            'countries' => $countries,
            'states' => $states,           
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.destination._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, Destination $destination): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);

        $modalData = [
            'title'     => __('titles.destination.edit'),
            'uuid'      => $destination->uuid,
            'data'      => $destination,
            'form_mode' => 'edit',
            'countries' => $countries,
            'states' => $states,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.destination._modal', compact('modalData'))->render(),
        ]);
    }

    /** Update */
    public function update(UpdateDestinationRequest $request, Destination $destination): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($destination, $validated);

            return AjaxResponse::success(message: __('messages.destination.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Delete */
    public function destroy(Request $request, Destination $destination): JsonResponse
    {
        dd('Working In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        if ($this->repository->canDelete($destination)) {
            return AjaxResponse::error(message: __('messages.destination.has_destination'));
        }

        try {
            $destination->deleted_by = current_user_id();
            $destination->save();
            $this->repository->delete($destination);
            return AjaxResponse::success(message: __('messages.destination.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
       
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreGodownRequest;
use App\Http\Requests\UpdateGodownRequest;
use App\Models\Godown;
use App\Repositories\GodownRepository;
use App\Repositories\CommonRepository;
use App\Services\DataTables\GodownDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GodownController extends Controller
{
    protected GodownRepository $repository;
    protected CommonRepository $commonRepository;
    protected GodownDataTable $dataTable;
    protected int $companyId;

    public function __construct(GodownRepository $repository, GodownDataTable $dataTable,  CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:godown.list')->only(['index']);
        $this->middleware('permission:godown.create')->only(['create', 'store']);
        $this->middleware('permission:godown.update')->only(['edit', 'update']);
        $this->middleware('permission:godown.delete')->only('destroy');
        $this->middleware('permission:godown.restore')->only('restore');

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
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $groups  =  Godown::select('godown_name', 'id')->where('company_id', company_id())->get();
        return view('company.pages.masters.godown.index', compact('groups'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $destination_id = $this->commonRepository->getDestinations($this->companyId);

        $modalData = [
            'title'     => __('titles.godown.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'destination_id' => $destination_id,         
        ];
        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.godown._modal', compact('modalData'))->render(),
        ]);
    }

    /** Store */
    public function store(StoreGodownRequest $request): JsonResponse
    {        
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;       
            $validated['created_by'] = current_user_id();
            $godown = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.godown.created'), data: $godown, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, Godown $godown): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $destination_id = $this->commonRepository->getDestinations($this->companyId);

        $modalData = [
            'title'     => __('titles.godown.view'),
            'uuid'      => null,
            'data'      => $godown,
            'form_mode' => 'view',
            'destination_id' => $destination_id,          
        ];
        // dd($modalData);

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.godown._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, Godown $godown): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $destination_id = $this->commonRepository->getDestinations($this->companyId);

        $modalData = [
            'title'     => __('titles.godown.edit'),
            'uuid'      => $godown->uuid,
            'data'      => $godown,
            'form_mode' => 'edit',
            'destination_id' => $destination_id,           
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.godown._modal', compact('modalData'))->render(),
        ]);
    }

    /** Update */
    public function update(UpdateGodownRequest $request, Godown $godown): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($godown, $validated);

            return AjaxResponse::success(message: __('messages.godown.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Delete */
    public function destroy(Request $request, Godown $godown): JsonResponse
    {
        dd('Work In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // if ($this->repository->canDelete($godown)) {
        //     return AjaxResponse::error(message: __('messages.godown.delete_error'));
        // }

        try {
            $godown->deleted_by = current_user_id();
            $godown->save();
            $this->repository->delete($godown);
            return AjaxResponse::success(message: __('messages.godown.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    
}

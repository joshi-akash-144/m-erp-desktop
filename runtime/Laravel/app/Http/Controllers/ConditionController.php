<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreConditionRequest;
use App\Http\Requests\UpdateConditionRequest;
use App\Models\Condition;
use App\Repositories\ConditionRepository;
use App\Services\DataTables\ConditionDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ConditionController extends Controller
{
    protected ConditionRepository $repository;
    protected ConditionDataTable $dataTable;
    protected int $companyId;

    public function __construct(ConditionRepository $repository, ConditionDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:condition.list')->only(['index']);
        $this->middleware('permission:condition.create')->only(['create', 'store']);
        $this->middleware('permission:condition.update')->only(['edit', 'update']);
        $this->middleware('permission:condition.delete')->only('destroy');

        $this->repository = $repository;
        $this->dataTable = $dataTable;

    }

    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $condition  =  Condition::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.condition.index', compact('condition'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.condition.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];
         return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.condition._modal', compact('modalData'))->render()],
            code: 200
        );
    }
    /** Store */
    public function store(StoreConditionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();

            $condition = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.condition.created'),data: $condition,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
    /** Load View Modal */
    public function show(Request $request, Condition $condition): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.condition.view'),
            'uuid'      => null,
            'data'      => $condition,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.condition._modal', compact('modalData'))->render()
        ]);
    }
    /** Load Edit Modal */
    public function edit(Request $request, Condition $condition): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.condition.edit'),
            'uuid'      => $condition->uuid,
            'data'      => $condition,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.condition._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdateConditionRequest $request, Condition $condition): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($condition, $validated);

            return AjaxResponse::success(message: __('messages.condition.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, Condition $condition): JsonResponse
    {
        dd('Work In Progress');
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        if ($this->repository->canDelete($condition)) {
            return AjaxResponse::error(message: __('messages.condition.delete_error'));
        }
        try {
            $condition->deleted_by = current_user_id();
            $condition->save();
            $this->repository->delete($condition);
            return AjaxResponse::success(message: __('messages.condition.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

}

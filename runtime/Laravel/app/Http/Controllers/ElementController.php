<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreElementRequest;
use App\Http\Requests\UpdateElementRequest;
use App\Models\Element;
use App\Repositories\ElementRepository;
use App\Services\DataTables\ElementDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElementController extends Controller
{
    protected ElementRepository $repository;
    protected ElementDataTable $dataTable;
    protected int $companyId;

    public function __construct(ElementRepository $repository, ElementDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:element.list')->only(['index']);
        $this->middleware('permission:element.create')->only(['create', 'store']);
        $this->middleware('permission:element.update')->only(['edit', 'update']);
        $this->middleware('permission:element.delete')->only('destroy');
        $this->middleware('permission:element.restore')->only('restore');

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

        $element  =  Element::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.element.index', compact('element'));
    }
    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.element.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];
        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.element._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(StoreElementRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();
            $element = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.element.created'),data: $element,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }


    /** Load View Modal */
    public function show(Request $request, Element $element): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.element.view'),
            'uuid'      => null,
            'data'      => $element,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.element._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, Element $element): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.element.edit'),
            'uuid'      => $element->uuid,
            'data'      => $element,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.element._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdateElementRequest $request, Element $element): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($element, $validated);
            return AjaxResponse::success(message: __('messages.element.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
    /** Delete */
    public function destroy(Request $request, Element $element): JsonResponse
    {
        dd('Work In Progress');
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        if ($this->repository->canDelete($element)) {
            return AjaxResponse::error(message: __('messages.element.delete_error'));
        }
        try {
            $element->deleted_by = current_user_id();
            $element->save();
            $this->repository->delete($element);
            return AjaxResponse::success(message: __('messages.element.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

}

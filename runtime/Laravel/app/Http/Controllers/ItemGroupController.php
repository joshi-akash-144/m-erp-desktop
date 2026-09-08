<?php

namespace App\Http\Controllers;

use App\Models\ItemGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\AjaxResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use App\Http\Requests\StoreItemGroupRequest;
use App\Http\Requests\UpdateItemGroupRequest;
use App\Repositories\ItemGroupRepository;
use App\Services\DataTables\ItemGroupDataTable;
use Exception;
use Illuminate\Routing\Controller;

class ItemGroupController extends Controller
{
    protected ItemGroupRepository $repository;
    protected ItemGroupDataTable $dataTable;
    protected int $companyId;

    public function __construct(ItemGroupRepository $repository, ItemGroupDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:item_group.list')->only(['index']);
        $this->middleware('permission:item_group.create')->only(['create', 'store']);
        $this->middleware('permission:item_group.update')->only(['edit', 'update']);
        $this->middleware('permission:item_group.delete')->only('destroy');
        $this->middleware('permission:item_group.restore')->only('restore');

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

        $groups  =  ItemGroup::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.item-group.index', compact('groups'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.item_group.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.item-group._modal', compact('modalData'))->render()],
            code: 200
        );

    }

    /** Store */
    public function store(StoreItemGroupRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();
            $itemGroup = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.item_group.created'), data: $itemGroup, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'), 
                errors: $e->getMessage()
            );
        }
    }

    /** Load View Modal */
    public function show(Request $request, ItemGroup $itemGroup): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.item_group.view'),
            'uuid'          => null,
            'data'      => $itemGroup,
            'form_mode' => 'view',
        ];
        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.item-group._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, ItemGroup $itemGroup): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.item_group.edit'),
            'uuid'      => $itemGroup->uuid,
            'data'      => $itemGroup,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.item-group._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdateItemGroupRequest $request, ItemGroup $itemGroup): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($itemGroup, $validated);

            return AjaxResponse::success(
                message: __('messages.item_group.updated'),
                data: $updated,
                code: 200
            );
            
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'), 
                errors: $e->getMessage()
            );
        }
    }

    /** Delete */
    public function destroy(Request $request, ItemGroup $itemGroup): JsonResponse
    {
        dd('Work In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(
                message: __('messages.request.type'), 
            );
        }

        // if ($this->repository->hasItems($itemGroup)) {
        //     return AjaxResponse::error(
        //         message: __('messages.item_group.has_items'), 
        //     );
        // }

        try {
            $itemGroup->deleted_by = current_user_id();
            $itemGroup->save();
            $this->repository->delete($itemGroup);

            return AjaxResponse::success(message: __('messages.item_group.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'), 
                errors: $e->getMessage()
            );
        }
    }
}

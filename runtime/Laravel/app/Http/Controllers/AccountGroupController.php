<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreAccountGroupRequest;
use App\Http\Requests\UpdateAccountGroupRequest;
use App\Models\AccountGroup;
use App\Repositories\AccountGroupRepository;
use App\Services\DataTables\AccountGroupDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountGroupController extends Controller
{
    protected AccountGroupRepository $repository;
    protected AccountGroupDataTable $dataTable;
    protected int $companyId;

    public function __construct(AccountGroupRepository $repository, AccountGroupDataTable $dataTable)
    {
        // Permissions
        $this->middleware('permission:account_group.list')->only(['index']);
        $this->middleware('permission:account_group.create')->only(['create', 'store']);
        $this->middleware('permission:account_group.update')->only(['edit', 'update']);
        $this->middleware('permission:account_group.delete')->only(['destroy']);

        $this->repository = $repository;
        $this->dataTable  = $dataTable;
    }

    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $groups  =  AccountGroup::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.account-group.index', compact('groups'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $parentGroups = $this->repository->getParentGroups(company_id());

        $modalData = [
            'title'        => __('titles.account_group.add'),
            'uuid'         => uuid(),
            'data'         => null,
            'form_mode'    => 'create',
            'parentGroups' => $parentGroups,
        ];


        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.account-group._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Store new record */
    public function store(StoreAccountGroupRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['parent_id'] = $request->is_primary == 0 ? $request->parent_id : null;
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();

            $accountGroup = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.account_group.created'), data: $accountGroup, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Load View Modal */
    public function show(Request $request, AccountGroup $accountGroup): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $parentGroups = $this->repository->getParentGroups(company_id());

        $modalData = [
            'title'        => __('titles.account_group.view'),
            'uuid'          => null,
            'data'         => $accountGroup,
            'form_mode'    => 'view',
            'parentGroups' => $parentGroups,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.account-group._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, AccountGroup $accountGroup): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $parentGroups = $this->repository->getParentGroups(company_id(), $accountGroup->id);

        $modalData = [
            'title'        => __('titles.account_group.edit'),
            'uuid'         => $accountGroup->uuid,
            'data'         => $accountGroup,
            'form_mode'    => 'edit',
            'parentGroups' => $parentGroups,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.account-group._modal', compact('modalData'))->render()
        ]);
    }

    /** Update record */
    public function update(UpdateAccountGroupRequest $request, AccountGroup $accountGroup)
    {
        try {

            $validated = $request->validated();
            // $validated['parent_id'] = $request->parent_id == 0 ? $request->parent_id : null;
            $validated['parent_id'] = $request->is_primary == 0 ? $request->parent_id : null;          
            $validated['updated_by'] = current_user_id();
 
            $updated = $this->repository->update($accountGroup, $validated);

            return AjaxResponse::success(__('messages.account_group.updated'), $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
        }
    }

    /** Delete record */
    public function destroy(Request $request, AccountGroup $accountGroup): JsonResponse
    {
        dd("work in progress"); // prevent update for now for all system account groups
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {

            if ($this->repository->hasAccounts($accountGroup)) {
                return AjaxResponse::error(message: __('messages.account_group.has_accounts'));
            }

            if ($this->repository->hasChildren($accountGroup)) {
                return AjaxResponse::error(message: __('messages.account_group.has_children'));
            }
            $accountGroup->deleted_by = current_user_id();
            $accountGroup->save();
            $this->repository->delete($accountGroup);

            return AjaxResponse::success(message: __('messages.account_group.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
}

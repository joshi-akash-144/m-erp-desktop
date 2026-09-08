<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreExpenseTypeRequest;
use App\Http\Requests\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;
use App\Models\AccountGroup;
use App\Repositories\CommonRepository;
use App\Services\DataTables\ExpenseTypeDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ExpenseTypeController extends Controller
{
    protected CommonRepository $commonRepository;
    protected ExpenseTypeDataTable $dataTable;
    protected int $companyId;

    public function __construct(ExpenseTypeDataTable $dataTable, CommonRepository $commonRepository)
    {
        $this->middleware('permission:expense_type.list')->only(['index']);
        $this->middleware('permission:expense_type.create')->only(['create', 'store']);
        $this->middleware('permission:expense_type.update')->only(['edit', 'update']);
        $this->middleware('permission:expense_type.delete')->only('destroy');
        $this->middleware('permission:expense_type.restore')->only('restore');

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
            $query = ExpenseType::query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        $expenseTypes = ExpenseType::where('company_id', company_id())->get();

        return view('company.pages.masters.expense-type.index', compact('expenseTypes'));
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $expenseTypeGroups = $this->getExpenseTypeGroup();

        $modalData = [
            'title'     => 'Add New Expense Type',
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'expenseTypeGroups' => $expenseTypeGroups,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.expense-type._modal', compact('modalData'))->render(),
        ]);
    }

    public function store(StoreExpenseTypeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $expenseType = ExpenseType::create($validated);

            return AjaxResponse::success(message: __('messages.expense_type.created'), data: $expenseType, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function show(Request $request, ExpenseType $expenseType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $expenseTypeGroups = $this->getExpenseTypeGroup();

        $modalData = [
            'title'     => __('titles.expense_type.view'),
            'uuid'      => null,
            'data'      => $expenseType,
            'expenseTypeGroups' => $expenseTypeGroups,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.expense-type._modal', compact('modalData'))->render(),
        ]);
    }

    public function edit(Request $request, ExpenseType $expenseType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $expenseTypeGroups = $this->getExpenseTypeGroup();

        $modalData = [
            'title'     => 'Edit Expense Type',
            'uuid'      => $expenseType->uuid,
            'data'      => $expenseType,
            'expenseTypeGroups' => $expenseTypeGroups,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.expense-type._modal', compact('modalData'))->render(),
        ]);
    }

    public function update(UpdateExpenseTypeRequest $request, ExpenseType $expenseType): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $expenseType->update($validated);

            return AjaxResponse::success(message: __('messages.expense_type.updated'), data: $expenseType);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Request $request, ExpenseType $expenseType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $expenseType->update(['deleted_by' => current_user_id()]);
            $expenseType->delete();
            
            return AjaxResponse::success(message: __('messages.expense_type.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function getExpenseTypeGroup()
    {
        $companyId = company_id();
        $creditorGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '420')
            ->first();
        
        if (!$creditorGroup) {
            return collect(); 
        }
        
        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $creditorGroup->id)
            ->pluck('id')
            ->toArray();       
        $allGroupIds = array_merge([$creditorGroup->id], $childGroups);
           
        return AccountGroup::whereIn('id', $allGroupIds)->get(['id', 'name']);
    }
}

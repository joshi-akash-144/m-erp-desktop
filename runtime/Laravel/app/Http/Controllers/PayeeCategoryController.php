<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StorePayeeCategoryRequest;
use App\Http\Requests\UpdatePayeeCategoryRequest;
use App\Models\PayeeCategory;
use App\Repositories\PayeeCategoryRepository;
use App\Services\DataTables\PayeeCategoryDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayeeCategoryController extends Controller
{
    protected PayeeCategoryRepository $repository;
    protected PayeeCategoryDataTable $dataTable;
    protected int $companyId;

    public function __construct(PayeeCategoryRepository $repository, PayeeCategoryDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:payee_category.list')->only(['index']);
        $this->middleware('permission:payee_category.create')->only(['create', 'store']);
        $this->middleware('permission:payee_category.update')->only(['edit', 'update']);
        $this->middleware('permission:payee_category.delete')->only('destroy');
        $this->middleware('permission:payee_category.restore')->only('restore');

        $this->repository = $repository;
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

        $groups  =  PayeeCategory::select('payee_category', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.payee-category.index', compact('groups'));
    }

        /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.payee_category.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];
        return AjaxResponse::success(message:__('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.payee-category._modal', compact('modalData'))->render(),
        ]);
    }

      /** Store */
    public function store(StorePayeeCategoryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;            
            $validated['created_by'] = current_user_id();            
            $payeeCategory = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.payee_category.created'),data: $payeeCategory,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, PayeeCategory $payeeCategory): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.payee_category.view'),
            'uuid'      => null,
            'data'      => $payeeCategory,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message:__('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.payee-category._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, PayeeCategory $payeeCategory): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.payee_category.edit'),
            'uuid'      => $payeeCategory->uuid,
            'data'      => $payeeCategory,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message:__('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.payee-category._modal', compact('modalData'))->render(),
        ]);
    }

   /** Update */
    public function update(UpdatePayeeCategoryRequest $request, PayeeCategory $payeeCategory): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($payeeCategory, $validated);

            return AjaxResponse::success(message: __('messages.payee_category.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, PayeeCategory $payeeCategory): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // Check if Payee Category is used in Account Master
        $isUsedInAccount = \App\Models\AccountTaxDetail::where('payee_category_id', $payeeCategory->id)->exists();
        if ($isUsedInAccount) {
            return AjaxResponse::error(message: 'Cannot delete Payee Category because it is associated with one or more Account Masters.');
        }

        // Check if Payee Category is used in TDS Category Setup
        $isUsedInTds = \App\Models\TdsCategoryDetail::where('payee_category_id', $payeeCategory->id)->exists();
        if ($isUsedInTds) {
            return AjaxResponse::error(message: 'Cannot delete Payee Category because it is configured in one or more TDS Categories.');
        }

        try {
            $payeeCategory->deleted_by = current_user_id();
            $payeeCategory->save();
            $this->repository->delete($payeeCategory);
            return AjaxResponse::success(message: __('messages.payee_category.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
   
}

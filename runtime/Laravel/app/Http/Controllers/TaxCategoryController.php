<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreTaxCategoryRequest;
use App\Http\Requests\UpdateTaxCategoryRequest;
use App\Models\TaxCategory;
use App\Repositories\TaxCategoryRepository;
use App\Services\DataTables\TaxCategoryDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class TaxCategoryController extends Controller
{
    protected TaxCategoryRepository $repository;
    protected TaxCategoryDataTable $dataTable;
    protected int $companyId;

    public function __construct(TaxCategoryRepository $repository, TaxCategoryDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:tax_category.list')->only(['index']);
        $this->middleware('permission:tax_category.create')->only(['create', 'store']);
        $this->middleware('permission:tax_category.update')->only(['edit', 'update']);
        $this->middleware('permission:tax_category.delete')->only('destroy');
        $this->middleware('permission:tax_category.restore')->only('restore');

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

        $groups  =  TaxCategory::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.tax-category.index', compact('groups'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.tax_category.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.tax-category._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Store */
    public function store(StoreTaxCategoryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;
            $validated['created_by'] = current_user_id();
            $validated['code'] = $this->repository->nextCode($this->companyId);

            $taxCategory = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.tax_category.created'), data: $taxCategory, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    /** Load View Modal */
    public function show(Request $request, TaxCategory $taxCategory): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.tax_category.view'),
            'uuid'          => null,
            'data'      => $taxCategory,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.tax-category._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Load Edit Modal */
    public function edit(Request $request, TaxCategory $taxCategory): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => __('titles.tax_category.edit'),
            'uuid'      => $taxCategory->uuid,
            'data'      => $taxCategory,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.tax-category._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Update */
    public function update(UpdateTaxCategoryRequest $request, TaxCategory $taxCategory): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($taxCategory, $validated);

            return AjaxResponse::success(
                message: __('messages.tax_category.updated'),
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
    public function destroy(Request $request, TaxCategory $taxCategory): JsonResponse
    {
        dd('Work in Process');
        if (!$request->ajax()) {
            return AjaxResponse::error(
                message: __('messages.messages.request.type'),
            );
        }

        if ($this->repository->hasAccounts($taxCategory)) {
            return AjaxResponse::error(
                message: __('messages.tax_category.has_accounts'),
            );
        }

        try {
            $taxCategory->deleted_by = current_user_id();
            $taxCategory->save();
            $this->repository->delete($taxCategory);

            return AjaxResponse::success(message: __('messages.tax_category.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }
    
}

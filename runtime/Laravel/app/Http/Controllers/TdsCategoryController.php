<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreTdsCategoryRequest;
use App\Http\Requests\UpdateTdsCategoryRequest;
use App\Models\TdsCategory;
use App\Repositories\TdsCategoryRepository;
use App\Services\DataTables\TdsCategoryDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TdsCategoryController extends Controller
{
    protected TdsCategoryRepository $repository;
    protected TdsCategoryDataTable $dataTable;
    protected int $companyId;

    public function __construct(TdsCategoryRepository $repository, TdsCategoryDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:tds_category.list')->only(['index']);
        $this->middleware('permission:tds_category.create')->only(['create', 'store']);
        $this->middleware('permission:tds_category.update')->only(['edit', 'update']);
        $this->middleware('permission:tds_category.delete')->only('destroy');
        $this->middleware('permission:tds_category.restore')->only('restore');

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

        $TdsCategories  =  TdsCategory::select('section', 'id')->where('company_id', company_id())->get();
        // dd($groups->toArray());

        return view('company.pages.masters.tds-category.index', compact('TdsCategories'));
    }

        /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $payeeCategories = \App\Models\PayeeCategory::where('company_id', $this->companyId)->where('is_active', 1)->get();
        $accounts = \App\Models\Account::where('company_id', $this->companyId)->where('is_active', 1)->get(['id', 'name']);

        $modalData = [
            'title'     => __('titles.tds_category.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'payeeCategories' => $payeeCategories,
            'accounts' => $accounts,
        ];        
        return AjaxResponse::success(message:__('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.tds-category._modal', compact('modalData'))->render(),
        ]);
    }

      /** Store */
    public function store(StoreTdsCategoryRequest $request): JsonResponse
    {
        // dd($request->toArray());
        try {
            $validated = $request->validated();
            $validated['company_id'] = $this->companyId;            
            $validated['created_by'] = current_user_id();            
            $tdsCategory = $this->repository->create($validated);

            if ($request->has('details')) {
                $details = $request->input('details');
                foreach ($details as $detail) {
                    $tdsCategory->details()->create([
                        'payee_category_id' => $detail['payee_category_id'],
                        'threshold_limit' => !empty($detail['threshold_limit']) ? $detail['threshold_limit'] : 0,
                        'tds_with_pan' => !empty($detail['tds_with_pan']) ? $detail['tds_with_pan'] : 0,
                        'tds_without_pan' => !empty($detail['tds_without_pan']) ? $detail['tds_without_pan'] : 0,
                    ]);
                }
            }

            return AjaxResponse::success(message: __('messages.tds_category.created'),data: $tdsCategory,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, TdsCategory $tdsCategory): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        
        $tdsCategory->load('details');
        $payeeCategories = \App\Models\PayeeCategory::where('company_id', $this->companyId)->where('is_active', 1)->get();
        $accounts = \App\Models\Account::where('company_id', $this->companyId)->where('is_active', 1)->get(['id', 'name']);

        $modalData = [
            'title'     => __('titles.tds_category.view'),
            'uuid'      => null,
            'data'      => $tdsCategory,
            'form_mode' => 'view',
            'payeeCategories' => $payeeCategories,
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(message:__('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.tds-category._modal', compact('modalData'))->render(),
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, TdsCategory $tdsCategory): JsonResponse
    {        
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
       
        $tdsCategory->load('details');
        $payeeCategories = \App\Models\PayeeCategory::where('company_id', $this->companyId)->where('is_active', 1)->get();
        $accounts = \App\Models\Account::where('company_id', $this->companyId)->where('is_active', 1)->get(['id', 'name']);

        $modalData = [
            'title'     => __('titles.tds_category.edit'),
            'uuid'      => $tdsCategory->uuid,
            'data'      => $tdsCategory,
            'form_mode' => 'edit',
            'payeeCategories' => $payeeCategories,
            'accounts' => $accounts,
        ];
        
        return AjaxResponse::success(message:__('messages.modal.load_success'),data: [
            'html' => view('company.pages.masters.tds-category._modal', compact('modalData'))->render(),
        ]);
    }

   /** Update */
    public function update(UpdateTdsCategoryRequest $request, TdsCategory $tdsCategory): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($tdsCategory, $validated);

            if ($request->has('details')) {
                $tdsCategory->details()->delete();
                $details = $request->input('details');
                foreach ($details as $detail) {
                    $tdsCategory->details()->create([
                        'payee_category_id' => $detail['payee_category_id'],
                        'threshold_limit' => !empty($detail['threshold_limit']) ? $detail['threshold_limit'] : 0,
                        'tds_with_pan' => !empty($detail['tds_with_pan']) ? $detail['tds_with_pan'] : 0,
                        'tds_without_pan' => !empty($detail['tds_without_pan']) ? $detail['tds_without_pan'] : 0,
                    ]);
                }
            }

            return AjaxResponse::success(message: __('messages.tds_category.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

       /** Delete */
    public function destroy(Request $request, TdsCategory $tdsCategory): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // Check if this TDS Category is assigned to any Account Master
        $isUsed = \App\Models\AccountTaxDetail::where('tds_category_id', $tdsCategory->id)->exists();
        if ($isUsed) {
            return AjaxResponse::error(message: 'Cannot delete TDS Category because it is associated with one or more Account Masters.');
        }

        try {
            $tdsCategory->deleted_by = current_user_id();
            $tdsCategory->save();
            $this->repository->delete($tdsCategory);
            return AjaxResponse::success(message: __('messages.tds_category.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
    
}

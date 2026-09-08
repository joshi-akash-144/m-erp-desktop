<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreSaleTypeRequest;
use App\Http\Requests\UpdateSaleTypeRequest;
use App\Models\Company;
use App\Models\SaleType;
use App\Repositories\CommonRepository;
use App\Repositories\SaleTypeRepository;
use App\Services\DataTables\SaleTypeDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class SaleTypeController extends Controller
{
    protected SaleTypeRepository $repository;
    protected SaleTypeDataTable $dataTable;
    protected CommonRepository $commonRepository;
    protected int $companyId;

    public function __construct(SaleTypeRepository $repository, SaleTypeDataTable $dataTable, CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:sale_type.list')->only(['index']);
        $this->middleware('permission:sale_type.create')->only(['create', 'store']);
        $this->middleware('permission:sale_type.update')->only(['edit', 'update']);
        $this->middleware('permission:sale_type.delete')->only('destroy');
        $this->middleware('permission:sale_type.restore')->only('restore');

        $this->repository = $repository;
        $this->commonRepository = $commonRepository;
        $this->dataTable = $dataTable;

    }

    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $saleTypes = SaleType::where('company_id', company_id())
            ->selectRaw('MIN(id) as id, taxation_type')
            ->groupBy('taxation_type')
            ->get();

        return view('company.pages.masters.sale-type.index', compact('saleTypes'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
            'title'     => __('titles.sale_type.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.sale-type._modal', compact('modalData'))->render()],
            code: 200
        );

    }

    /** Store */
    public function store(StoreSaleTypeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();

            $saleType = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.sale_type.created'), data: $saleType, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'), 
                errors: $e->getMessage()
            );
        }
    }

    /** Load View Modal */
    public function show(Request $request, SaleType $saleType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
            'title'     => __('titles.sale_type.view'),
            'uuid'          => null,
            'data'      => $saleType,
            'form_mode' => 'view',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.sale-type._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, SaleType $saleType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
           'title'     => __('titles.sale_type.edit'),
            'uuid'      => $saleType->uuid,
            'data'      => $saleType,
            'form_mode' => 'edit',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.sale-type._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdateSaleTypeRequest $request, SaleType $saleType): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($saleType, $validated);

            return AjaxResponse::success(
                message: __('messages.sale_type.updated'),
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
    public function destroy(Request $request, SaleType $saleType): JsonResponse
    {
        dd('Working In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(
                message: __('messages.request.type'), 
            );
        }

        // if ($this->repository->hasAccounts($taxCategory)) {
        //     return AjaxResponse::error(
        //         message: __('messages.tax_category.has_accounts'), 
        //     );
        // }

        try {
            $saleType->deleted_by = current_user_id();
            $saleType->save();
            $this->repository->delete($saleType);

            return AjaxResponse::success(message: __('messages.sale_type.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'), 
                errors: $e->getMessage()
            );
        }
    }

    
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StorePurchaseTypeRequest;
use App\Http\Requests\UpdatePurchaseTypeRequest;
use App\Models\PurchaseType;
use App\Repositories\PurchaseTypeRepository;
use App\Repositories\CommonRepository;
use App\Services\DataTables\PurchaseTypeDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PurchaseTypeController extends Controller
{
    protected PurchaseTypeRepository $repository;
    protected PurchaseTypeDataTable $dataTable;

    protected CommonRepository $commonRepository;
    protected int $companyId;

    public function __construct(PurchaseTypeRepository $repository, PurchaseTypeDataTable $dataTable, CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:purchase_type.list')->only(['index']);
        $this->middleware('permission:purchase_type.create')->only(['create', 'store']);
        $this->middleware('permission:purchase_type.update')->only(['edit', 'update']);
        $this->middleware('permission:purchase_type.delete')->only('destroy');
        $this->middleware('permission:purchase_type.restore')->only('restore');

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

        $purchaseTypes = PurchaseType::where('company_id', company_id())
            ->selectRaw('MIN(id) as id, taxation_type')
            ->groupBy('taxation_type')
            ->get();

        return view('company.pages.masters.purchase-type.index', compact('purchaseTypes'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
            'title'     => __('titles.purchase_type.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.purchase-type._modal', compact('modalData'))->render()],
            code: 200
        );

    }

    /** Store */
    public function store(StorePurchaseTypeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();
            // $validated['code'] = $this->repository->nextCode($this->companyId);
            // dd($validated);
            $purchaseType = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.purchase_type.created'), data: $purchaseType, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    /** Load View Modal */
    public function show(Request $request, PurchaseType $purchaseType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
            'title'     => __('titles.purchase_type.view'),
            'uuid'          => null,
            'data'      => $purchaseType,
            'form_mode' => 'view',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.purchase-type._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, PurchaseType $purchaseType): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id(), columns: ['id', 'name']);

        $modalData = [
           'title'     => __('titles.purchase_type.edit'),
            'uuid'      => $purchaseType->uuid,
            'data'      => $purchaseType,
            'form_mode' => 'edit',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.purchase-type._modal', compact('modalData'))->render()
        ]);
    }

    /** Update */
    public function update(UpdatePurchaseTypeRequest $request, PurchaseType $purchaseType): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->update($purchaseType, $validated);

            return AjaxResponse::success(
                message: __('messages.purchase_type.updated'),
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
    public function destroy(Request $request, PurchaseType $purchaseType): JsonResponse
    {
        dd('Working In Progress');
        if (!$request->ajax()) {
            return AjaxResponse::error(
                message: __('messages.request.type'),
            );
        }

        // if ($this->repository->hasAccounts($purchaseType)) {
        //     return AjaxResponse::error(
        //         message: __('messages.purchase_type.has_accounts'),
        //     );
        // }

        try {
            $purchaseType->deleted_by = current_user_id();
            $purchaseType->save();
            $this->repository->delete($purchaseType);

            return AjaxResponse::success(message: __('messages.purchase_type.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

}

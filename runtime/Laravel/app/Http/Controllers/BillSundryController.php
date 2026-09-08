<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\BillSundry;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;
use App\Models\PurchaseInvoiceSundry;
use App\Repositories\CommonRepository;
use App\Repositories\BillSundryRepository;
use App\Http\Requests\StoreBillSundryRequest;
use App\Http\Requests\UpdateBillSundryRequest;
use App\Services\DataTables\BillSundryDataTable;


class BillSundryController extends Controller
{
    protected CommonRepository $commonRepository;
    protected BillSundryRepository $repository;
    protected BillSundryDataTable $dataTable;
    protected int $companyId;

    public function __construct(BillSundryRepository $repository, BillSundryDataTable $dataTable, CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:bill_sundry.list')->only(['index', 'list']);
        $this->middleware('permission:bill_sundry.create')->only(['create', 'store']);
        $this->middleware('permission:bill_sundry.update')->only(['edit', 'update']);
        $this->middleware('permission:bill_sundry.delete')->only('destroy');
        $this->middleware('permission:bill_sundry.restore')->only('restore');

        $this->repository = $repository;
        $this->dataTable = $dataTable;
        $this->commonRepository = $commonRepository;

        // // Get company ID from session
        // $this->middleware(function ($request, $next) {
        //     $this->companyId = (int) session('company_id');
        //     return $next($request);
        // });
    }
    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $billSundry = BillSundry::where('company_id', company_id())
            ->selectRaw('MIN(id) as id, bill_sundry_type')
            ->groupBy('bill_sundry_type')
            ->get();

        return view('company.pages.masters.bill-sundry.index', compact('billSundry'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id());

        $modalData = [
            'title'     => __('titles.bill_sundry.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'accounts' => $accounts,
        ];
        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bill-sundry._modal', compact('modalData'))->render()],
            code: 200
        );
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillSundryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();
            $billSundry = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.bill_sundry.created'), data: $billSundry, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Load View Modal */
    public function show(Request $request, BillSundry $billSundry): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id());

        $modalData = [
            'title'     => __('titles.bill_sundry.view'),
            'uuid'          => null,
            'data'      => $billSundry,
            'form_mode' => 'view',
            'accounts' => $accounts,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bill-sundry._modal', compact('modalData'))->render(),],
            code: 200
        );
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, BillSundry $billSundry): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accounts = $this->commonRepository->getAccounts(company_id());

        $usedInPurchase = PurchaseInvoiceSundry::where('sundry_id', $billSundry->id)->exists();
        // dd($usedInPurchase);
        
        $modalData = [
            'title'     => __('titles.bill_sundry.edit'),
            'uuid'      => $billSundry->uuid,
            'data'      => $billSundry,
            'form_mode' => 'edit',
            'accounts' => $accounts,
            'usedInPurchase' => $usedInPurchase,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bill-sundry._modal', compact('modalData'))->render(),],
            code: 200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillSundryRequest $request, BillSundry $billSundry)
    {
        // dd("work in progress");
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();
            $updated = $this->repository->updateBillSundry($billSundry, $validated);

            return AjaxResponse::success(
                message: __('messages.bill_sundry.updated'),
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

    /** Delete account */
    public function destroy(Request $request, BillSundry $billSundry): JsonResponse
    {
        dd("work in progress");
        $usedInPurchase = PurchaseInvoiceSundry::where('sundry_id', $billSundry->id)->exists();
        if ($usedInPurchase) {
            return AjaxResponse::error(
                message: __('This Bill Sundry cannot be deleted because it is already used in other table.'),
                code: 422
            );
        }

        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $billSundry->deleted_by = current_user_id();
            $billSundry->save();
            $this->repository->delete($billSundry);

            return AjaxResponse::success(message: __('messages.bill_sundry.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

}

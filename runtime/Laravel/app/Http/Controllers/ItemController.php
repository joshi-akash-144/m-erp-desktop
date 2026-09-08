<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Repositories\ItemRepository;
use App\Services\DataTables\ItemDataTable;
use App\Repositories\CommonRepository;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ItemController extends Controller
{

    protected ItemRepository $repository;
    protected CommonRepository $commonRepository;
    protected ItemDataTable $dataTable;
    protected int $companyId;
    protected int $financialYearId;

    public function __construct(ItemRepository $repository, ItemDataTable $dataTable, CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:item.list')->only(['index']);
        $this->middleware('permission:item.create')->only(['create', 'store']);
        $this->middleware('permission:item.update')->only(['edit', 'update']);
        $this->middleware('permission:item.delete')->only('destroy');
        $this->middleware('permission:item.restore')->only('restore');

        $this->repository = $repository;
        $this->commonRepository = $commonRepository;
        $this->dataTable = $dataTable;

        // Get company ID from session
        // $this->middleware(function ($request, $next) {
        //     $this->companyId = (int) session('company_id');
        //     $this->financialYearId = session('financial_year_id');
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

        $itemGroups  =  ItemGroup::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.item.index', compact('itemGroups'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $taxCategories = $this->commonRepository->getTaxCategories(company_id(),['name','id']);
        $itemGroups = ItemGroup::select('name', 'id')->where('company_id', company_id())->get();
        $accounts = $this->commonRepository->getAccounts(company_id(),['name','id']);
        $units = $this->commonRepository->getUnits(company_id(),['name','id']);
        

        $purchaseTypes = $this->commonRepository->getPurchaseTypes(company_id(), ['name', 'id', 'region']);
        $saleTypes = $this->commonRepository->getSaleTypes(company_id(), ['name', 'id', 'region']);

        $modalData = [
            'title'        => __('titles.item.add'),
            'uuid'         => Str::uuid(),
            'data'         => null,
            'form_mode'    => 'create',
            'itemGroups' => $itemGroups,
            'units' => $units,
            'taxCategories' => $taxCategories,
            'accounts' => $accounts,
            'purchaseTypes' => $purchaseTypes,
            'saleTypes' => $saleTypes,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.item._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['financial_year_id'] = financial_year_id();
            $validated['created_by'] = current_user_id();

            $item = $this->repository->create($validated);

            return AjaxResponse::success(message: __('messages.item.created'),data: $item,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): JsonResponse
    {
        $item->load(['currentOpeningStock']);

        $taxCategories = $this->commonRepository->getTaxCategories(company_id(),['name','id']);
        $itemGroups = ItemGroup::select('name', 'id')->where('company_id', company_id())->get();
        $accounts = $this->commonRepository->getAccounts(company_id(),['name','id']);
        $units = $this->commonRepository->getUnits(company_id(),['name','id']);

        $purchaseTypes = $this->commonRepository->getPurchaseTypes(company_id(), ['name', 'id', 'region']);
        $saleTypes = $this->commonRepository->getSaleTypes(company_id(), ['name', 'id', 'region']);

        $modalData = [
            'title'        => __('titles.item.view'),
            'uuid'          => null,
            'data'         => $item,
            'form_mode'     => 'view',
            'itemGroups' => $itemGroups,
            'units' => $units,
            'taxCategories' => $taxCategories,
            'accounts' => $accounts,
            'purchaseTypes' => $purchaseTypes,
            'saleTypes' => $saleTypes,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.item._modal', compact('modalData'))->render()
        ]);
    }

    /** Load Edit Modal */
    public function edit(Request $request, Item $item): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $item->load(['currentOpeningStock']);

        $taxCategories = $this->commonRepository->getTaxCategories(company_id(),['name','id']);
        $itemGroups = ItemGroup::select('name', 'id')->where('company_id', company_id())->get();
        $accounts = $this->commonRepository->getAccounts(company_id(),['name','id']);
        $units = $this->commonRepository->getUnits(company_id(),['name','id']);

        $purchaseTypes = $this->commonRepository->getPurchaseTypes(company_id(), ['name', 'id', 'region']);
        $saleTypes = $this->commonRepository->getSaleTypes(company_id(), ['name', 'id', 'region']);

        $modalData = [
            'title'        => __('titles.item.edit'),
            'uuid'         => $item->uuid,
            'data'         => $item,
            'form_mode'     => 'edit',
            'itemGroups' => $itemGroups,
            'units' => $units,
            'taxCategories' => $taxCategories,
            'accounts' => $accounts,
            'purchaseTypes' => $purchaseTypes,
            'saleTypes' => $saleTypes,
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.item._modal', compact('modalData'))->render()
        ]);
    }

    /** Update item */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();

            $updated = $this->repository->update($item, $validated);

            return AjaxResponse::success(message: __('messages.item.updated'),data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

     /** Delete */
     public function destroy(Item $item): JsonResponse
     {
        dd('Work In Progress');
         try {
             if ($this->repository->isUsedInTransactions($item)) {
                 return AjaxResponse::error(
                     message: __('This item cannot be deleted because it is used in purchase or sales invoices.'),
                     code: 422
                 );
             }
            $item->deleted_by = current_user_id();
            $item->save();
             $this->repository->delete($item);

             return AjaxResponse::success(message: __('messages.item.deleted'));
         } catch (Exception $e) {
             return AjaxResponse::error(
                 message: __('messages.common.unexpected_error'),
                 errors: $e->getMessage()
             );
         }
     }

    /** List (DataTable) */
    public function list(): JsonResponse
    {
        $data = $this->repository->all(
            columns: ['*'],
            with: ['creator:id,name', 'updater:id,name'],
            filters: ['company_id' => $this->companyId],
            scopes: [
               fn($query) => $query->status() 
           ],
            orderBy: 'name'
        );
        
        return $this->dataTable->getData($data);
    }
}

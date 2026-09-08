<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contractor;
use App\Models\FreightContractorItem;
use App\Services\DataTables\ContractorDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContractorController extends Controller
{
    protected ContractorDataTable $dataTable;
    protected int $companyId;

    public function __construct(ContractorDataTable $dataTable)
    {        
        $this->middleware('permission:contractor.list')->only(['index']);
        $this->middleware('permission:contractor.create')->only(['create', 'store']);
        $this->middleware('permission:contractor.update')->only(['edit', 'update']);
        $this->middleware('permission:contractor.delete')->only('destroy');
        $this->middleware('permission:contractor.restore')->only('restore');

        $this->dataTable = $dataTable;
    }
    
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = Contractor::query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        // $contracts = Contract::select('name', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.contractor.index');
    }
    
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => 'Add Contract',
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.contractor._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();
            $contract = Contractor::create($validated);

            return AjaxResponse::success(message: 'Contract created successfully', data: $contract, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    
    public function show(Request $request, Contractor $contract): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => 'View Contract',
            'uuid'      => null,
            'data'      => $contract,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.contractor._modal', compact('modalData'))->render()
        ]);
    }
    
    public function edit(Request $request, Contractor $contract): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'     => 'Edit Contract',
            'uuid'      => null,
            'data'      => $contract,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.contractor._modal', compact('modalData'))->render()
        ]);
    }
    
    public function update(UpdateContractRequest $request, Contractor $contract): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();            
            $contract->update($validated);
            return AjaxResponse::success(message: 'Contract updated successfully', data: $contract);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    
    public function destroy(Request $request, Contractor $contract): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        
        try {
            // Check if contractor is assigned in freight_contractor_items table
            $isAssigned = FreightContractorItem::where('contractor_id', $contract->id)->exists();
            if ($isAssigned) {
                return AjaxResponse::error(message: 'Cannot delete contractor because it is assigned to one or more freight invoices.');
            }

            $contract->deleted_by = current_user_id();
            $contract->save();
            $contract->delete();
            return AjaxResponse::success(message: 'Contractor deleted successfully');
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
}

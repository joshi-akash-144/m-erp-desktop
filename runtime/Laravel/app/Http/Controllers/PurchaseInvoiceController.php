<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoice;
use App\Models\Account;
use App\Models\BillSundry;
use App\Models\PurchaseInvoice;
use App\Services\PurchaseInvoiceService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;
use App\Services\MasterDataService;

class PurchaseInvoiceController extends Controller
{

    protected PurchaseInvoiceService $service;
    protected MasterDataService $masterService;

    public function __construct(PurchaseInvoiceService $service,MasterDataService $masterService)
    {
        $this->middleware('permission:purchase_invoice.create')->only(['create', 'store']);
        $this->middleware('permission:purchase_invoice.update')->only(['edit', 'update']);
        $this->middleware('permission:purchase_invoice.delete')->only('destroy');
        $this->middleware('permission:purchase_invoice.restore')->only('restore');

        $this->service = $service;
        $this->masterService = $masterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $filters = $request->only([
                "start_date",
                "end_date",
                "account_id",
                "item_id",
                "payment_status",
                "grn_serial",
                "voucher_id",
                "sales_invoice_serial",
                "voucher_serial",
                "file_no",
                "reference_number",
            ]);
    
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
    
            $result = $this->service->purchaseInvoiceList(companyId: company_id(),financialYearId: financial_year_id(),filters: $filters);
            // dd($result);
            return response()->json($result);
        }
    

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        $invoiceSerials = $this->service->getInvoiceSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        extract($this->extractMasterData($masterData));
        
        return view('company.pages.purchase-invoice.index', compact(
            'accounts',
            'brokers',
            'items',
            'destinations',
            'conditions',
            'grnSerials',
            'invoiceSerials',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = company_id();
        $invoiceInfo = $this->service->getNextVoucherNumber(
            companyId: $companyId,
            financialYearId: financial_year_id()
        );

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());

        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();

        extract($this->extractMasterData($masterData));

        $pendingGrns = $this->service->fetchPendingGrns($companyId);

        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();

        return view('company.pages.purchase-invoice.create',[  
            'invoiceSerial' => $invoiceInfo->serial,
            'invoiceNumber' => $invoiceInfo->voucher_number,
            'pendingGrns' => $pendingGrns,
            'accounts' => $accounts,
            'brokers' => $brokers,
            'items' => $items,
            'destinations' => $destinations,
            'conditions' => $conditions,
            'purchaseTypes' => $purchaseTypes,
            'billSundry' => $billSundry,
            'allLedgers'  => $allLedgers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseInvoiceRequest $request)
    {
        try {

            $invoice = $this->service->createInvoice(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            return AjaxResponse::success(
                message: "Purchase Invoice Number <b class='text-primary'>{$invoice->invoice_serial}</b> has been created successfully.",
                data: $invoice
            );
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.purchase_invoice.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }

        

    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ?int $purchaseInvoiceId = null): JsonResponse 
    {
        if ($request->ajax()) {
            $id = $purchaseInvoiceId ?? $request->purchaseInvoiceId;
            if ($id) {
                $purchaseInvoice = $this->service->getViewData(
                    purchaseInvoiceId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            // dd($purchaseInvoice);
            if (!$purchaseInvoice) {
                return AjaxResponse::error('Purchase Invoice not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.purchase_invoice.fetched'),
                data: $purchaseInvoice
            );
        }

        $purchaseInvoiceSerials = $this->service->getPurchaseInvoiceSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        // dd($grnSerials);
        return AjaxResponse::success(
            message: __('messages.purchase_invoice.fetched'),
            data: $purchaseInvoiceSerials
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(PurchaseInvoice $purchaseInvoice)
    public function edit(Request $request, ?int $purchaseInvoice = null): View|JsonResponse
    {
        if ($request->ajax() && $purchaseInvoice) {
            $invoice = $this->service->getEditData(
                invoiceId : $purchaseInvoice,
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            if (!$invoice) {
                return AjaxResponse::error('Invoice not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.purchase_invoice.fetched'),
                data: $invoice
            );
        }

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());

        
        $companyId = company_id();

        $pendingGrns = $this->service->fetchPendingGrns($companyId);

        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();

        extract($this->extractMasterData($masterData));


        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();


        $grnSerials = $this->service->getGrnSerial(companyId: company_id(),financialYearId: financial_year_id());

        $invoiceSerials = $this->service->getInvoiceSerial(companyId: company_id(),financialYearId: financial_year_id());

        $purchaseInvoiceId = $purchaseInvoice;

        $pendingGrns = $this->service->fetchPendingGrns(company_id());

        return view('company.pages.purchase-invoice.edit', compact('pendingGrns', 'invoiceSerials', 'purchaseInvoiceId', 'accounts', 'brokers', 'items', 'destinations', 'conditions', 'purchaseTypes', 'billSundry', 'allLedgers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseInvoice $request, PurchaseInvoice $purchaseInvoice)
    {
        
        $data = $request->validated();
        $financialYearId = financial_year_id();
        $companyId        = company_id();
    
        try {
    
            $invoice = $this->service->updateInvoice($data, $companyId, $financialYearId, $purchaseInvoice->id);
    
            return AjaxResponse::success(
                message: __('messages.purchase_invoice.updated'),
                data:$invoice,
            );
    
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.purchase_invoice.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        try {
            $this->service->deleteInvoice($purchaseInvoice);

            return AjaxResponse::success(
                message: __('messages.purchase_invoice.deleted')
            );
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: $e->getMessage(),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }


    // public function fetchPendingGrns(Request $request): JsonResponse
    // {
    //     try {
    //         $companyId = company_id();
    //         $grns = $this->service->fetchPendingGrns($companyId, $request->q);

    //         return AjaxResponse::success('Pending Grns Fetch Successfully', data: $grns);
    //     } catch (Throwable $e) {
    //         return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
    //     }
    // }
    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'accounts'     => $masterData['accounts'] ?? [],
            'brokers'       => $masterData['brokers'] ?? [],
            'conditions'    => $masterData['conditions'] ?? [],
            'items'         => $masterData['items'] ?? [],
            'destinations'  => $masterData['destinations'] ?? [],
            'purchaseTypes' => $masterData['purchaseTypes'] ?? [],
        ];
    }
    public function checkDuplicateReference(Request $request)
    {
        $referenceNumber   = $request->query('reference_number');
        $accountId         = $request->query('account_id');
        $currentInvoiceId  = $request->query('invoice_id') ? (int) $request->query('invoice_id') : null;

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $exists = $this->service->isDuplicateReference(
            companyId: $companyId,
            financialYearId: $financialYearId,
            referenceNumber: $referenceNumber,
            accountId: $accountId,
            invoiceId: $currentInvoiceId   // skips current record in edit mode
        );

        $url = null;

        if ($exists) {
            $duplicateInvoiceId = $this->service->getByReferenceNumberAndAccount(
                companyId: $companyId,
                financialYearId: $financialYearId,
                referenceNumber: $referenceNumber,
                accountId: $accountId,
                excludeId: $currentInvoiceId   // skips current record so URL points to the OTHER duplicate
            );

            if ($duplicateInvoiceId) {
                $url = route('purchase-invoices.show', $duplicateInvoiceId);
            }
        }

        return AjaxResponse::success(
            message: 'Reference check completed.',
            data: [
                'is_duplicate' => $exists,
                'url'          => $url,
            ]
        );
    }

    function getGrnDetails(Request $request){
        $grnId = $request->grn_id;
        if(!$grnId) return;
        if($request->ajax()){
            $grn = $this->service->getGrnDetails($grnId);
            return AjaxResponse::success(
                message: __('messages.grn.fetched'),
                data: $grn
            );
        }
    }

    public function getSupplierTurnOver(Request $request)
    {
        $supplierId = $request->supplier_id;
        if (!$supplierId) return;
        if ($request->ajax()) {
            $supplierTurnOver = $this->service->getSupplierTurnOver($supplierId, company_id(), financial_year_id());
            return AjaxResponse::success(
                message: __('messages.supplier_turn_over.fetched'),
                data: ['turn_over' => $supplierTurnOver]
            );
        }
    }
}

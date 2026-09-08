<?php

namespace App\Http\Controllers;

use App\Services\MasterDataService;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Http\Requests\UpdateSalesInvoiceRequest;
use App\Models\Account;
use App\Models\BillSundry;
use App\Models\SalesInvoice;
use App\Services\LookupService;
use App\Services\SalesInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use App\Models\SaleType;
use Throwable;

class SalesInvoiceController extends Controller
{
    protected SalesInvoiceService $service;
    protected MasterDataService $masterService;
    protected LookupService $lookupService;

    public function __construct(SalesInvoiceService $service, MasterDataService $masterService, LookupService $lookupService)
    {
        $this->middleware('permission:sales_invoice.create')->only(['create', 'store']);
        $this->middleware('permission:sales_invoice.update')->only(['edit', 'update']);
        $this->middleware('permission:sales_invoice.delete')->only('destroy');
        $this->middleware('permission:sales_invoice.restore')->only('restore');
        $this->service = $service;
        $this->masterService = $masterService;
        $this->lookupService = $lookupService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $filters = $request->only([
                'orderSerials',
                'start_date',
                'end_date',
                'account_id',
                'item_id',
                'payment_status',
                'grn_number',
                'vehicle_number',
                'op_numbers',
                'invoice_date',
                'bill_from',
                'bill_to',
            ]);
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
            $result = $this->service->salesInvoiceList(companyId: company_id(), financialYearId: financial_year_id(), filters: $filters);
            // dd(json_decode(json_encode($result), true));                        
            return response()->json($result);
        }
        $masterData   = $this->masterService->salesInvoiceMasterData(company_id());
        $invoiceSerials = $this->service->getInvoiceSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $grnNumbers = $this->service->getGrnNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );
        $OpNumbers = $this->service->getPoNumbers(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        // dd($invoiceSerials->toArray());
        extract($this->extractMasterData($masterData));
        return view('company.pages.sales-invoice.index', compact(
            'suppliers',
            'brokers',
            'conditions',
            'items',
            'destinations',
            'invoiceSerials',
            'grnNumbers',
            'OpNumbers',
            'customers',                

        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $masterData = $this->masterService->salesInvoiceMasterData(company_id());

        extract($this->extractMasterData($masterData));
        $defaultDeliveryDays = SalesInvoice::DEFAULT_DELIVERY_DAYS;

        $invoiceInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $latestBillDate = SalesInvoice::latestBillDate(company_id(), financial_year_id());

        $saleTypes = SaleType::where('company_id', company_id())->get();

        $billDate = $latestBillDate
            ? $latestBillDate->format('d-m-Y')
            : now()->format('d-m-Y');

        $current_date = now()->format('d-m-Y');

        $salesOrders = $this->service->getPendingPurchaseOrders(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $companyId  = company_id();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();

        return view('company.pages.sales-invoice.create', [
            'customers'           => $customers,
            'brokers'             => $brokers,
            'conditions'          => $conditions,
            'defaultDeliveryDays' => $defaultDeliveryDays,
            'invoiceSerial'       => $invoiceInfo->serial,
            'invoiceNumber'       => $invoiceInfo->voucher_number,
            'items'               => $items,
            'destinations'        => $destinations,
            'billDate'            => $billDate,
            'current_date'        => $current_date,
            'salesOrders'         => $salesOrders,
            'saleTypes'           => $saleTypes,
            'billSundry'          => $billSundry,
            'allLedgers'          => $allLedgers,
        ]);
    }

    /**
     * Store a newly created sales Invoice in storage.
     */
    public function store(StoreSalesInvoiceRequest $request): JsonResponse
    {
        try {
            $salesInvoice = $this->service->createSalesInvoice(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: "Sales Invoice Number <b class='text-primary'>{$salesInvoice->invoice_serial}</b> has been created successfully.",
                data: $salesInvoice
            );
        } catch (Throwable $e) {

            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_invoice.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Request $request, ?int $salesInvoiceId = null): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error('Bad Request.', code: 400);
        }

        $id = $salesInvoiceId ?? $request->sales_invoice_id;

        if (!$id) {
            return AjaxResponse::error('Sales Invoice not found.', code: 404);
        }

        $salesInvoice = $this->service->getViewData(
            salesInvoiceId: $id,
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        if (!$salesInvoice) {
            return AjaxResponse::error('Sales Invoice not found.', code: 404);
        }

        return AjaxResponse::success(
            message: __('messages.sales_invoice.fetched'),
            data: $salesInvoice
        );
    }

    /**
     * Edit the form for editing the specified resource.
     */
    public function edit(Request $request, ?int $salesInvoiceId = null): View|JsonResponse
    {
        if ($request->ajax() && $request->has('sales_invoice_id')) {
            $id = $request->sales_invoice_id;
            // dd('edit', $request->all());
            if ($id) {
                $salesInvoice = $this->service->getEditData(
                    salesInvoiceId: $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            // dd('salesInvoice', $salesInvoice->toArray());
            if (!$salesInvoice) {
                return AjaxResponse::error('Sales invoice not found.', code: 404);
            }

            $salesOrders = $this->service->getPendingPurchaseOrders(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                currentOrderId: $salesInvoice['sales_order_id']
            );

            return AjaxResponse::success(
                message: __('messages.sales_invoice.fetched'),
                data: [
                    'salesInvoice' => $salesInvoice,
                    'salesOrders'  => $salesOrders
                ],
            );
        }

        $invoiceSerials = $this->service->getInvoiceSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $latestBillDate = SalesInvoice::latestBillDate(company_id(), financial_year_id());
        $billDate = $latestBillDate
            ? $latestBillDate->format('d-m-Y')
            : now()->format('d-m-Y');
        $current_date = now()->format('d-m-Y');

        $masterData = $this->masterService->salesInvoiceMasterData(company_id());
        extract($this->extractMasterData($masterData));

        $companyId  = company_id();
        $saleTypes  = SaleType::where('company_id', $companyId)->get();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();
        $salesOrders = $this->service->getPendingPurchaseOrders(
            companyId: $companyId,
            financialYearId: financial_year_id()
        );

        return view('company.pages.sales-invoice.edit', compact(
            'invoiceSerials',
            'salesInvoiceId',
            'billDate',
            'current_date',
            'customers',
            'brokers',
            'conditions',
            'items',
            'destinations',
            'saleTypes',
            'billSundry',
            'allLedgers',
            'salesOrders',
        ));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice): JsonResponse
    {
        // dd('update', 'validated', $request->validated(), 'salesInvoice', $salesInvoice->toArray(), $request->all());
        try {
            if (!$salesInvoice) {
                return AjaxResponse::error(
                    message: __('messages.sales_invoice.not_found'),
                    code: 404
                );
            }
            $updatedInvoice = $this->service->updateSalesInvoice(invoiceId: $request->sales_invoice_id, data: $request->validated(), companyId: company_id(), financialYearId: financial_year_id());
            return AjaxResponse::success(
                message: __('messages.sales_invoice.updated'),
                data: $updatedInvoice
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_invoice.throwable_error'),
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesInvoice $salesInvoice): JsonResponse
    {
        try {
            // $this->service->deleteSalesInvoice($salesInvoice);
            return AjaxResponse::success(
                message: __('messages.sales_invoice.deleted')
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.sales_invoice.throwable_error'),
                code: 500
            );
        }
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'customers'    => $masterData['customers'] ?? [],
            'suppliers'    => $masterData['suppliers'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
        ];
    }

    public function validateGrn(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $grnNumber = $request->grn_number;
            $salesInvoiceId = $request->sales_invoice_id ?? null;
            $accountId = $request->account_id;
            $result = $this->service->validateGrn($companyId, $grnNumber, $salesInvoiceId, $accountId);
            return AjaxResponse::success(message: __('messages.common.data_found'), data: ['is_duplicate' => $result]);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    // public function allSalesOrders(Request $request): JsonResponse
    // {
    //     // dd('allSalesOrders', $request->all());
    //     try {
    //         $companyId = company_id();
    //         $result = $this->service->allOrders($companyId, $request->q);
    //         if (!$result) {
    //             return AjaxResponse::error(message: __('messages.common.data_not_found'));
    //         }

    //         return AjaxResponse::success(message: __('messages.common.data_found'), data: $result);
    //     } catch (Throwable $e) {
    //         dd('allSalesOrders', $e);
    //         return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
    //     }
    // }

    public function getSalesOrderDetails(string $purchaseOrderNumber): JsonResponse
    {
        try {
            $companyId = company_id();
            $result    = $this->service->getSalesOrderDetails($companyId, $purchaseOrderNumber);

            if ($result['status'] === 'not_found') {
                return AjaxResponse::error(message: 'No Purchase Order found.');
            }
            if ($result['status'] === 'closed') {
                return AjaxResponse::error(message: 'This Purchase Order is already closed.');
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $result['data']);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    // public function checkDuplicateReference(Request $request)
    // {
    //     $referenceNumber = $request->query('reference_number');
    //     $accountId = $request->query('account_id');
    //     $invoiceId = $request->query('invoice_id');

    //     $companyId = company_id();
    //     $financialYearId = financial_year_id();

    //     $exists = $this->service->isDuplicateReference(
    //         companyId: $companyId,
    //         financialYearId: $financialYearId,
    //         referenceNumber: $referenceNumber,
    //         accountId: $accountId,
    //         invoiceId: $invoiceId
    //     );

    //     $url = null;

    //     if ($exists) {
    //         $invoiceId = $this->service->getByReferenceNumberAndAccount(
    //             companyId: $companyId,
    //             financialYearId: $financialYearId,
    //             referenceNumber: $referenceNumber,
    //             accountId: $accountId
    //         );

    //         if ($invoiceId) {
    //             $url = route('purchase-invoices.show', $invoiceId);
    //         }
    //     }

    //     return AjaxResponse::success(
    //         message: 'Reference check completed.',
    //         data: [
    //             'is_duplicate' => $exists,
    //             'url'          => $url,
    //         ]
    //     );
    // }    
}

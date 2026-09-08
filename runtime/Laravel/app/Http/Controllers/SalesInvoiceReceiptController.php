<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreSalesInvoiceReceiptRequest;
use App\Models\Company;
use App\Services\MasterDataService;
use App\Services\SalesInvoiceReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class SalesInvoiceReceiptController extends Controller
{
    protected SalesInvoiceReceiptService $service;
    protected MasterDataService $masterService;

    public function __construct(SalesInvoiceReceiptService $service, MasterDataService $masterService)
    {
        // $this->middleware('permission:sales_invoice_receipt.list')->only(['index', 'getPendingInvoices', 'report', 'getReceiptReport']);
        // $this->middleware('permission:sales_invoice_receipt.create')->only('store');
        // $this->middleware('permission:sales_invoice_receipt.print')->only('printReceiptReport');
        $this->service = $service;
        $this->masterService = $masterService;
    }

    /**
     * Display the Sales Invoice Receipt screen.
     */
    public function index(): View
    {
        $customers = $this->masterService->get('customers', company_id());
        $destinations = $this->masterService->get('destinations', company_id());
        $items = $this->masterService->get('items', company_id());

        return view('company.pages.sales-invoice-receipt.index', compact('customers', 'destinations', 'items'));
    }

    /**
     * Return pending (not yet received) sales invoices for a customer via AJAX.
     */
    public function getPendingInvoices(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => ['required', 'integer'],
        ]);

        $filters = $request->only(['customer_id', 'destination_id', 'item_id', 'start_date', 'end_date']);
        $filters['page'] = (int) $request->input('page', 1);
        $filters['size'] = (int) $request->input('size', 50);

        $result = $this->service->pendingInvoices(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            filters: $filters
        );

        return response()->json($result);
    }

    /**
     * Save the selected invoices as received.
     */
    public function store(StoreSalesInvoiceReceiptRequest $request): JsonResponse
    {
        try {
            $result = $this->service->storeReceipts(
                companyId: company_id(),
                customerId: (int) $request->customer_id,
                invoiceIds: $request->sales_invoice_ids,
                receivedBy: current_user_id()
            );

            return AjaxResponse::success(
                message: "{$result['saved']} invoice(s) Hisab completed Successfully." . ($result['skipped'] > 0 ? " {$result['skipped']} were already received and skipped." : ''),
                data: $result
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.common.something_went_wrong'),
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Display the "which bill settled on which day, by whom" report screen.
     */
    public function report(): View
    {
        $customers = $this->masterService->get('customers', company_id());

        return view('company.pages.sales-invoice-receipt.report', compact('customers'));
    }

    /**
     * Return receipt report rows via AJAX.
     */
    public function getReceiptReport(Request $request): JsonResponse
    {
        $filters = $request->only(['customer_id', 'start_date', 'end_date']);
        $filters['page'] = (int) $request->input('page', 1);
        $filters['size'] = (int) $request->input('size', 50);

        $result = $this->service->receiptReport(company_id(), $filters);

        return response()->json($result);
    }

    /**
     * Render a printable version of the receipt report.
     */
    public function printReceiptReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];
            $rows = $this->service->receiptReportAll(company_id(), $filters);

            $html = view('company.pages.sales-invoice-receipt.report-print', [
                'company'  => Company::find(company_id()),
                'rows'     => $rows,
                'filters'  => $filters,
            ])->render();

            return AjaxResponse::success(message: 'Report generated successfully.', data: ['html' => $html]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.common.something_went_wrong'),
                errors: $e->getMessage()
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreCreditNoteRequest;
use App\Http\Requests\UpdateCreditNoteRequest;
use App\Models\Account;
use App\Models\BillSundry;
use App\Models\CreditNote;
use App\Models\SalesInvoice;
use App\Models\SaleType;
use App\Services\CreditNoteService;
use App\Services\LookupService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class CreditNoteController extends Controller
{
    protected CreditNoteService $service;
    protected MasterDataService $masterService;
    protected LookupService $lookupService;

    public function __construct(
        CreditNoteService $service,
        MasterDataService $masterService,
        LookupService $lookupService
    ) {
        $this->middleware('permission:credit_note.create')->only(['create', 'store']);
        $this->middleware('permission:credit_note.update')->only(['edit', 'update']);
        $this->middleware('permission:credit_note.delete')->only('destroy');
        $this->middleware('permission:credit_note.restore')->only('restore');

        $this->service       = $service;
        $this->masterService = $masterService;
        $this->lookupService = $lookupService;
    }

    /* -------------------------------------------------------
     | INDEX
     | ------------------------------------------------------ */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $filters = $request->only(['start_date', 'end_date', 'account_id', 'bill_from', 'bill_to']);
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);

            $result = $this->service->creditNoteList(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                filters: $filters
            );
            return response()->json($result);
        }

        $masterData = $this->masterService->salesInvoiceMasterData(company_id());
        $customers  = $masterData['customers'] ?? [];

        $cnSerials = $this->service->getCreditNoteSerials(company_id(), financial_year_id());

        return view('company.pages.credit-note.index', compact('customers', 'cnSerials'));
    }

    /* -------------------------------------------------------
     | CREATE
     | ------------------------------------------------------ */
    public function create(): View
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $masterData = $this->masterService->salesInvoiceMasterData($companyId);
        $customers  = $masterData['customers']   ?? [];
        $items      = $masterData['items']        ?? [];
        $conditions = $masterData['conditions']   ?? [];
        $destinations = $masterData['destinations'] ?? [];

        $saleTypes  = SaleType::where('company_id', $companyId)->get();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();

        $cnInfo = $this->service->getNextVoucherNumber($companyId, financial_year_id());

        $latestDate = CreditNote::latestBillDate($companyId, financial_year_id());
        $billDate   = $latestDate ? $latestDate->format('d-m-Y') : now()->format('d-m-Y');


        return view('company.pages.credit-note.create', compact(
            'customers', 'items', 'conditions', 'destinations',
            'saleTypes', 'billSundry', 'allLedgers',
            'cnInfo', 'billDate',
        ));
    }

    /* -------------------------------------------------------
     | STORE
     | ------------------------------------------------------ */
    public function store(StoreCreditNoteRequest $request): JsonResponse
    {
        try {
            $cn = $this->service->createCreditNote(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: "Credit Note <b class='text-primary'>{$cn->credit_note_serial}</b> created successfully.",
                data: $cn
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: 'Failed to create Credit Note.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /* -------------------------------------------------------
     | SHOW (AJAX)
     | ------------------------------------------------------ */
    public function show(Request $request, ?int $creditNoteId = null): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error('Bad Request.', code: 400);
        }

        $id = $creditNoteId ?? $request->credit_note_id;
        if (!$id) {
            return AjaxResponse::error('Credit Note not found.', code: 404);
        }

        $cn = $this->service->getViewData($id, company_id(), financial_year_id());
        if (!$cn) {
            return AjaxResponse::error('Credit Note not found.', code: 404);
        }

        return AjaxResponse::success(message: 'Data fetched.', data: $cn);
    }

    /* -------------------------------------------------------
     | EDIT
     | ------------------------------------------------------ */
    public function edit(Request $request, ?int $creditNoteId = null)
    {
        if ($request->ajax() && $request->has('credit_note_id')) {
            $cn = $this->service->getEditData(
                $request->credit_note_id,
                company_id(),
                financial_year_id()
            );
            if (!$cn) {
                return AjaxResponse::error('Credit Note not found.', code: 404);
            }
            return AjaxResponse::success(message: 'Data fetched.', data: ['creditNote' => $cn]);
        }

        $creditNoteId = $creditNoteId ?? $request->credit_note_id;

        if ($creditNoteId) {
            $creditNote = CreditNote::find($creditNoteId);
            if ($creditNote) {
                $ref = \App\Models\Reference::where('voucher_id', $creditNote->voucher_id)->first();
                if ($ref && $ref->settled_amount > 0) {
                    return redirect()->route('company.credit-note.index')->with('error', 'Cannot edit. Credit Note is already paid or settled.');
                }
            }
        }

        $companyId = company_id();

        $masterData  = $this->masterService->salesInvoiceMasterData($companyId);
        $customers   = $masterData['customers']    ?? [];
        $items       = $masterData['items']         ?? [];
        $conditions  = $masterData['conditions']    ?? [];
        $destinations = $masterData['destinations'] ?? [];

        $saleTypes  = SaleType::where('company_id', $companyId)->get();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();
        $cnSerials  = $this->service->getCreditNoteSerials($companyId, financial_year_id());

        $latestDate  = CreditNote::latestBillDate($companyId, financial_year_id());
        $billDate    = $latestDate ? $latestDate->format('d-m-Y') : now()->format('d-m-Y');
        $currentDate = now()->format('d-m-Y');

        return view('company.pages.credit-note.edit', compact(
            'customers', 'items', 'conditions', 'destinations',
            'saleTypes', 'billSundry', 'allLedgers', 'cnSerials',
            'creditNoteId', 'billDate', 'currentDate'
        ));
    }

    /* -------------------------------------------------------
     | UPDATE
     | ------------------------------------------------------ */
    public function update(UpdateCreditNoteRequest $request, CreditNote $creditNote): JsonResponse
    {
        try {
            $updated = $this->service->updateCreditNote(
                creditNoteId: $request->credit_note_id,
                data: $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(message: 'Credit Note updated successfully.', data: $updated);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: 'Failed to update Credit Note.', errors: $e->getMessage());
        }
    }

    /* -------------------------------------------------------
     | DESTROY
     | ------------------------------------------------------ */
    public function destroy(CreditNote $creditNote): JsonResponse
    {
        try {
            $this->service->deleteCreditNote($creditNote->id);
            return AjaxResponse::success(message: 'Credit Note deleted successfully.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: $e->getMessage(), code: 500);
        }
    }

    /* -------------------------------------------------------
     | HELPER: get sales invoices for a customer (for Bill No. picker)
     | ------------------------------------------------------ */
    public function getSalesInvoices(Request $request): JsonResponse
    {
        try {
            $accountId = (int) $request->account_id;
            if (!$accountId) {
                return AjaxResponse::error('Account ID is required.', code: 422);
            }
            $invoices = $this->service->getSalesInvoicesForCustomer(company_id(), financial_year_id(), $accountId);
            return AjaxResponse::success(message: 'Data fetched.', data: $invoices);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: 'Something went wrong.', errors: $e->getMessage());
        }
    }
}

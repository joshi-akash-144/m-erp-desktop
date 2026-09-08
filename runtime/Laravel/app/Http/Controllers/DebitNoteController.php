<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreDebitNoteRequest;
use App\Http\Requests\UpdateDebitNoteRequest;
use App\Models\Account;
use App\Models\BillSundry;
use App\Models\DebitNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseType;
use App\Services\DebitNoteService;
use App\Services\LookupService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class DebitNoteController extends Controller
{
    protected DebitNoteService $service;
    protected MasterDataService $masterService;
    protected LookupService $lookupService;

    public function __construct(
        DebitNoteService $service,
        MasterDataService $masterService,
        LookupService $lookupService
    ) {
        $this->middleware('permission:debit_note.create')->only(['create', 'store']);
        $this->middleware('permission:debit_note.update')->only(['edit', 'update']);
        $this->middleware('permission:debit_note.delete')->only('destroy');
        $this->middleware('permission:debit_note.restore')->only('restore');

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

            $result = $this->service->debitNoteList(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                filters: $filters
            );
            return response()->json($result);
        }

        $masterData = $this->masterService->purchaseInvoiceMasterData(company_id());
        $suppliers  = $masterData['suppliers'] ?? [];

        $dnSerials = $this->service->getDebitNoteSerials(company_id(), financial_year_id());

        return view('company.pages.debit-note.index', compact('suppliers', 'dnSerials'));
    }

    /* -------------------------------------------------------
     | CREATE
     | ------------------------------------------------------ */
    public function create(): View
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $masterData = $this->masterService->purchaseInvoiceMasterData($companyId);
        $suppliers  = $masterData['suppliers']   ?? [];
        $items      = $masterData['items']        ?? [];
        $conditions = $masterData['conditions']   ?? [];
        $destinations = $masterData['destinations'] ?? [];

        $purchaseTypes  = PurchaseType::where('company_id', $companyId)->get();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();

        $dnInfo = $this->service->getNextVoucherNumber($companyId, financial_year_id());

        $latestDate = DebitNote::latestBillDate($companyId, financial_year_id());
        $billDate   = $latestDate ? $latestDate->format('d-m-Y') : now()->format('d-m-Y');

        return view('company.pages.debit-note.create', compact(
            'suppliers', 'items', 'conditions', 'destinations',
            'purchaseTypes', 'billSundry', 'allLedgers',
            'dnInfo', 'billDate',
        ));
    }

    /* -------------------------------------------------------
     | STORE
     | ------------------------------------------------------ */
    public function store(StoreDebitNoteRequest $request): JsonResponse
    {
        try {
            $dn = $this->service->createDebitNote(
                $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: "Debit Note <b class='text-primary'>{$dn->debit_note_serial}</b> created successfully.",
                data: $dn
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: 'Failed to create Debit Note.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /* -------------------------------------------------------
     | SHOW (AJAX)
     | ------------------------------------------------------ */
    public function show(Request $request, ?int $debitNoteId = null): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error('Bad Request.', code: 400);
        }

        $id = $debitNoteId ?? $request->debit_note_id;
        if (!$id) {
            return AjaxResponse::error('Debit Note not found.', code: 404);
        }

        $dn = $this->service->getViewData($id, company_id(), financial_year_id());
        if (!$dn) {
            return AjaxResponse::error('Debit Note not found.', code: 404);
        }

        return AjaxResponse::success(message: 'Data fetched.', data: $dn);
    }

    /* -------------------------------------------------------
     | EDIT
     | ------------------------------------------------------ */
    public function edit(Request $request, ?int $debitNoteId = null)
    {
        if ($request->ajax() && $request->has('debit_note_id')) {
            $dn = $this->service->getEditData(
                $request->debit_note_id,
                company_id(),
                financial_year_id()
            );
            if (!$dn) {
                return AjaxResponse::error('Debit Note not found.', code: 404);
            }
            return AjaxResponse::success(message: 'Data fetched.', data: ['debitNote' => $dn]);
        }

        $debitNoteId = $debitNoteId ?? $request->debit_note_id;

        if ($debitNoteId) {
            $debitNote = DebitNote::find($debitNoteId);
            if ($debitNote) {
                $ref = \App\Models\Reference::where('voucher_id', $debitNote->voucher_id)->first();
                if ($ref && $ref->settled_amount > 0) {
                    return redirect()->route('company.debit-note.index')->with('error', 'Cannot edit. Debit Note is already paid or settled.');
                }
            }
        }

        $companyId = company_id();

        $masterData  = $this->masterService->purchaseInvoiceMasterData($companyId);
        $suppliers   = $masterData['suppliers']    ?? [];
        $items       = $masterData['items']         ?? [];
        $conditions  = $masterData['conditions']    ?? [];
        $destinations = $masterData['destinations'] ?? [];

        $purchaseTypes  = PurchaseType::where('company_id', $companyId)->get();
        $billSundry = BillSundry::where('company_id', $companyId)->where('is_active', 1)->get();
        $allLedgers = Account::where('company_id', $companyId)->where('is_active', 1)->pluck('name', 'id')->toArray();
        $dnSerials  = $this->service->getDebitNoteSerials($companyId, financial_year_id());

        $latestDate  = DebitNote::latestBillDate($companyId, financial_year_id());
        $billDate    = $latestDate ? $latestDate->format('d-m-Y') : now()->format('d-m-Y');
        $currentDate = now()->format('d-m-Y');

        return view('company.pages.debit-note.edit', compact(
            'suppliers', 'items', 'conditions', 'destinations',
            'purchaseTypes', 'billSundry', 'allLedgers', 'dnSerials',
            'debitNoteId', 'billDate', 'currentDate'
        ));
    }

    /* -------------------------------------------------------
     | UPDATE
     | ------------------------------------------------------ */
    public function update(UpdateDebitNoteRequest $request, DebitNote $debitNote): JsonResponse
    {
        try {
            $ref = \App\Models\Reference::where('voucher_id', $debitNote->voucher_id)->first();
            if ($ref && $ref->settled_amount > 0) {
                return AjaxResponse::error('Cannot update. Debit Note is already paid or settled.', code: 403);
            }

            $updated = $this->service->updateDebitNote(
                debitNoteId: $request->debit_note_id,
                data: $request->validated(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(message: 'Debit Note updated successfully.', data: $updated);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: 'Failed to update Debit Note.', errors: $e->getMessage());
        }
    }

    /* -------------------------------------------------------
     | DESTROY
     | ------------------------------------------------------ */
    public function destroy(DebitNote $debitNote): JsonResponse
    {
        try {
            $this->service->deleteDebitNote($debitNote->id);
            return AjaxResponse::success(message: 'Debit Note deleted successfully.');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(message: $e->getMessage(), code: 500);
        }
    }

    /* -------------------------------------------------------
     | HELPER: get purchase invoices for a supplier (for Bill No. picker)
     | ------------------------------------------------------ */
    public function getPurchaseInvoices(Request $request): JsonResponse
    {
        try {
            $accountId = (int) $request->account_id;
            if (!$accountId) {
                return AjaxResponse::error('Account ID is required.', code: 422);
            }
            $invoices = $this->service->getPurchaseInvoicesForSupplier(company_id(), financial_year_id(), $accountId);
            return AjaxResponse::success(message: 'Data fetched.', data: $invoices);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: 'Something went wrong.', errors: $e->getMessage());
        }
    }
}

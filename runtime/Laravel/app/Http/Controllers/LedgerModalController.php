<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Models\Freight;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\JournalVoucherService;
use App\Services\PaymentVoucherService;
use App\Services\ReceiptVoucherService;
use App\Services\PurchaseInvoiceService;
use App\Services\SalesInvoiceService;
use App\Services\CreditNoteService;
use App\Services\DebitNoteService;
use App\Services\CreditNoteVoucherService;
use App\Services\DebitNoteVoucherService;
use Illuminate\Routing\Controller;
use Throwable;

class LedgerModalController extends Controller
{
    protected PaymentVoucherService $paymentVoucherService;
    protected ReceiptVoucherService $receiptVoucherService;
    protected JournalVoucherService $journalVoucherService;
    protected PurchaseInvoiceService $purchaseInvoiceService;
    protected SalesInvoiceService $salesInvoiceService;
    protected CreditNoteService $creditNoteService;
    protected DebitNoteService $debitNoteService;
    protected CreditNoteVoucherService $creditNoteVoucherService;
    protected DebitNoteVoucherService $debitNoteVoucherService;

    public function __construct(
        PaymentVoucherService $paymentVoucherService,
        ReceiptVoucherService $receiptVoucherService,
        JournalVoucherService $journalVoucherService,
        PurchaseInvoiceService $purchaseInvoiceService,
        SalesInvoiceService $salesInvoiceService,
        CreditNoteService $creditNoteService,
        DebitNoteService $debitNoteService,
        CreditNoteVoucherService $creditNoteVoucherService,
        DebitNoteVoucherService $debitNoteVoucherService
    ) {
        $this->paymentVoucherService = $paymentVoucherService;
        $this->receiptVoucherService = $receiptVoucherService;
        $this->journalVoucherService = $journalVoucherService;
        $this->purchaseInvoiceService = $purchaseInvoiceService;
        $this->salesInvoiceService = $salesInvoiceService;
        $this->creditNoteService = $creditNoteService;
        $this->debitNoteService = $debitNoteService;
        $this->creditNoteVoucherService = $creditNoteVoucherService;
        $this->debitNoteVoucherService = $debitNoteVoucherService;
    }

    /*--------------------------------------------------------------
    | MODAL HTML (Generic method based on voucher type)
    --------------------------------------------------------------*/
    public function voucherModalHtml(string $id)
    {
        try {
            $voucher = Voucher::find($id);
            if (!$voucher) {
                return AjaxResponse::error('Voucher not found', 404);
            }

            $html = '';
            $modalId = '';
            
            switch ($voucher->voucher_type_id) {
                case VoucherType::PAYMENT:
                    $data = $this->paymentVoucherService->details($id);
                    $html = view('company.pages.ledger-modal.payment_modal', $data)->render();
                    $modalId = '#payment_voucher_modal';
                    break;
                case VoucherType::RECEIPT:
                    $data = $this->receiptVoucherService->details($id);
                    $html = view('company.pages.ledger-modal.receipt_modal', $data)->render();
                    $modalId = '#receipt_voucher_modal';
                    break;
                case VoucherType::JOURNAL:
                    $data = $this->journalVoucherService->details($id);
                    $html = view('company.pages.ledger-modal.journal_modal', $data)->render();
                    $modalId = '#journal_voucher_modal';
                    break;
                case VoucherType::CREDIT_NOTE:
                    $data = $this->creditNoteVoucherService->details($id);
                    $html = view('company.pages.ledger-modal.credit_note_voucher_modal', $data)->render();
                    $modalId = '#credit_note_voucher_modal';
                    break;
                case VoucherType::DEBIT_NOTE:
                    $data = $this->debitNoteVoucherService->details($id);
                    $html = view('company.pages.ledger-modal.debit_note_voucher_modal', $data)->render();
                    $modalId = '#debit_note_voucher_modal';
                    break;
                case VoucherType::PURCHASE_INVOICE:
                    $invoice = $this->purchaseInvoiceService->getViewData($voucher->source_id, company_id(), financial_year_id());
                    $html = view('company.pages.ledger-modal.purchase_invoice_modal', [
                        'formMode' => 'view',
                        'grnSerials' => [],
                        'invoice' => $invoice,
                    ])->render();
                    $modalId = '#purchase_invoice_modal';
                    break;

                case VoucherType::SALE_INVOICE:
                    
                    $company = Company::find(company_id());
                    if ($company && $company->company_type === Company::TRANSPORT) {
                        $freight = Freight::with(['account', 'vehicle', 'consignor', 'consignee', 'fromDestination', 'toDestination', 'items.item', 'items.zone', 'creator', 'updater', 'voucher'])
                            ->find($voucher->source_id);
                        if ($freight) {
                            if ($freight->entry_from === Freight::ENTRY_FROM_VOUCHER) {
                                $html = view('company.pages.ledger-modal.freight_modal', [
                                    'formMode' => 'view',
                                    'freight' => $freight,
                                    'accounts' => collect(),
                                    'items' => collect(),
                                    'destinations' => collect(),
                                    'vehicles' => collect(),
                                    'transportParties' => collect(),
                                    'serialInfo' => (object)['serial' => $freight->invoice_serial],
                                ])->render();
                                $modalId = '#freight_modal';
                                break;
                            } elseif ($freight->entry_from === Freight::ENTRY_FROM_INVOICE) {
                                $html = view('company.pages.ledger-modal.freight_invoice_modal', [
                                    'formMode' => 'view',
                                    'freight' => $freight,
                                    'customers' => collect(),
                                    'ref' => $freight->invoice_number,
                                ])->render();
                                $modalId = '#freight_invoice_modal';
                                break;
                            } elseif ($freight->entry_from === Freight::ENTRY_FROM_INVOICE2) {
                                $html = view('company.pages.ledger-modal.freight_invoice_2_modal', [
                                    'formMode' => 'view',
                                    'freight' => $freight,
                                    'customers' => collect(),
                                    'ref' => $freight->invoice_number,
                                ])->render();
                                $modalId = '#freight_invoice_2_modal';
                                break;
                            }
                        }
                    }

                    $invoice = $this->salesInvoiceService->getViewData($voucher->source_id, company_id(), financial_year_id());
                    $html = view('company.pages.ledger-modal.sales_invoice_modal', [
                        'formMode' => 'view',
                        'invoice' => $invoice,
                    ])->render();
                    $modalId = '#sales_invoice_modal';
                    break;
                case VoucherType::SALES_RETURN:
                    $creditNote = $this->creditNoteService->getViewData($voucher->source_id, company_id(), financial_year_id());
                    $html = view('company.pages.ledger-modal.credit_note_modal', [
                        'formMode' => 'view',
                        'creditNote' => $creditNote,
                    ])->render();
                    $modalId = '#credit_note_modal';
                    break;
                case VoucherType::PURCHASE_RETURN:
                    $debitNote = $this->debitNoteService->getViewData($voucher->source_id, company_id(), financial_year_id());
                    $html = view('company.pages.ledger-modal.debit_note_modal', [
                        'formMode' => 'view',
                        'debitNote' => $debitNote,
                    ])->render();
                    $modalId = '#debit_note_modal';
                    break;
                default:
                    return AjaxResponse::error('Modal not implemented for this voucher type.', 400);
            }

            return AjaxResponse::success(
                message: "Voucher HTML fetched successfully.",
                data: [
                    'html' => $html,
                    'modal_id' => $modalId
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: 'Failed to fetch voucher html.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
}

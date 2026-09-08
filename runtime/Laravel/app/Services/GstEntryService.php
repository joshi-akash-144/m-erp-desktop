<?php

namespace App\Services;

use App\Enums\GstDocumentType;
use App\Enums\GstSupplyType;
use App\Models\GstEntry;
use App\Models\VoucherType;

class GstEntryService
{
    public function storeForSalesInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, VoucherType::SALE_INVOICE);
    }

    public function updateForSalesInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, VoucherType::SALE_INVOICE, isAmendment: true);
    }

    public function storeForPurchaseInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, VoucherType::PURCHASE_INVOICE);
    }

    public function updateForPurchaseInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, VoucherType::PURCHASE_INVOICE, isAmendment: true);
    }

    public function storeForCreditNote($creditNote, $voucher): void
    {
        $this->storeForCreditNoteInternal($creditNote, $voucher, isAmendment: false);
    }

    public function updateForCreditNote($creditNote, $voucher): void
    {
        $this->storeForCreditNoteInternal($creditNote, $voucher, isAmendment: true);
    }

    private function storeForCreditNoteInternal($creditNote, $voucher, bool $isAmendment): void
    {
        $originalInvoiceNo   = null;
        $originalInvoiceDate = null;

        if ($isAmendment) {
            $existing = GstEntry::where('voucher_id', $voucher->id)->first();
            if ($existing) {
                $originalInvoiceNo   = $existing->original_invoice_no ?? $existing->invoice_no;
                $originalInvoiceDate = $existing->original_invoice_date ?? $existing->invoice_date;
            }
        }

        GstEntry::where('voucher_id', $voucher->id)->delete();

        $creditNote->loadMissing('details.item.unit', 'account.taxDetail', 'account.state');

        $account       = $creditNote->account;
        $gstin         = $account?->taxDetail?->gst_number;
        $gstType       = $creditNote->gst_type ?? null;
        $supplyType    = $this->resolveSupplyType($gstin, $gstType);
        $placeOfSupply = $this->resolvePlaceOfSupply($account, $gstin);

        $invoiceValue = (float) ($creditNote->grand_total ?? $creditNote->net_amount ?? 0);

        foreach ($creditNote->details as $detail) {
            $item     = $detail->item;
            $totalTax = ($detail->cgst_amount ?? 0)
                + ($detail->sgst_amount ?? 0)
                + ($detail->igst_amount ?? 0);

            GstEntry::create([
                'company_id'            => $creditNote->company_id,
                'financial_year_id'     => $creditNote->financial_year_id,
                'voucher_id'            => $voucher->id,
                'account_id'            => $creditNote->account_id,
                'account_name'          => $account?->name,
                'gstin'                 => $gstin,
                'voucher_type_id'       => VoucherType::SALES_RETURN,
                'document_type'         => GstDocumentType::CRN,
                'invoice_no'            => $creditNote->credit_note_serial,
                'invoice_date'          => $creditNote->credit_note_date,
                'is_amendment'          => $isAmendment,
                'original_invoice_no'   => $originalInvoiceNo,
                'original_invoice_date' => $originalInvoiceDate,
                'item_id'               => $detail->item_id,
                'item_name'             => $item?->name,
                'hsn_code'              => $item?->hsn_sac_code,
                'uqc'                   => $item?->unit?->name,
                'qty'                   => $detail->quantity ?? 0,
                'supply_type'           => $supplyType,
                'place_of_supply'       => $placeOfSupply,
                'taxable_amount'        => $detail->taxable_amount ?? 0,
                'cgst_rate'             => $detail->cgst_rate ?? 0,
                'cgst_amount'           => $detail->cgst_amount ?? 0,
                'sgst_rate'             => $detail->sgst_rate ?? 0,
                'sgst_amount'           => $detail->sgst_amount ?? 0,
                'igst_rate'             => $detail->igst_rate ?? 0,
                'igst_amount'           => $detail->igst_amount ?? 0,
                'cess_rate'             => 0,
                'cess_amount'           => 0,
                'total_tax_amount'      => $totalTax,
                'invoice_value'         => $invoiceValue,
                'reverse_charge'        => false,
                'is_ecommerce'          => false,
            ]);
        }
    }
    public function storeForDebitNote($debitNote, $voucher): void
    {
        $this->storeForDebitNoteInternal($debitNote, $voucher, isAmendment: false);
    }

    public function updateForDebitNote($debitNote, $voucher): void
    {
        $this->storeForDebitNoteInternal($debitNote, $voucher, isAmendment: true);
    }

    private function storeForDebitNoteInternal($debitNote, $voucher, bool $isAmendment): void
    {
        $originalInvoiceNo   = null;
        $originalInvoiceDate = null;

        if ($isAmendment) {
            $existing = GstEntry::where('voucher_id', $voucher->id)->first();
            if ($existing) {
                $originalInvoiceNo   = $existing->original_invoice_no ?? $existing->invoice_no;
                $originalInvoiceDate = $existing->original_invoice_date ?? $existing->invoice_date;
            }
        }

        GstEntry::where('voucher_id', $voucher->id)->delete();

        $debitNote->loadMissing('details.item.unit', 'account.taxDetail', 'account.state');

        $account       = $debitNote->account;
        $gstin         = $account?->taxDetail?->gst_number;
        $gstType       = $debitNote->gst_type ?? null;
        $supplyType    = $this->resolveSupplyType($gstin, $gstType);
        $placeOfSupply = $this->resolvePlaceOfSupply($account, $gstin);

        $invoiceValue = (float) ($debitNote->grand_total ?? $debitNote->net_amount ?? 0);

        foreach ($debitNote->details as $detail) {
            $item     = $detail->item;
            $totalTax = ($detail->cgst_amount ?? 0)
                + ($detail->sgst_amount ?? 0)
                + ($detail->igst_amount ?? 0);

            GstEntry::create([
                'company_id'            => $debitNote->company_id,
                'financial_year_id'     => $debitNote->financial_year_id,
                'voucher_id'            => $voucher->id,
                'account_id'            => $debitNote->account_id,
                'account_name'          => $account?->name,
                'gstin'                 => $gstin,
                'voucher_type_id'       => VoucherType::PURCHASE_RETURN,
                'document_type'         => GstDocumentType::DBN,
                'invoice_no'            => $debitNote->debit_note_serial,
                'invoice_date'          => $debitNote->debit_note_date,
                'is_amendment'          => $isAmendment,
                'original_invoice_no'   => $originalInvoiceNo,
                'original_invoice_date' => $originalInvoiceDate,
                'item_id'               => $detail->item_id,
                'item_name'             => $item?->name,
                'hsn_code'              => $item?->hsn_sac_code,
                'uqc'                   => $item?->unit?->name,
                'qty'                   => $detail->quantity ?? 0,
                'supply_type'           => $supplyType,
                'place_of_supply'       => $placeOfSupply,
                'taxable_amount'        => $detail->taxable_amount ?? 0,
                'cgst_rate'             => $detail->cgst_rate ?? 0,
                'cgst_amount'           => $detail->cgst_amount ?? 0,
                'sgst_rate'             => $detail->sgst_rate ?? 0,
                'sgst_amount'           => $detail->sgst_amount ?? 0,
                'igst_rate'             => $detail->igst_rate ?? 0,
                'igst_amount'           => $detail->igst_amount ?? 0,
                'cess_rate'             => 0,
                'cess_amount'           => 0,
                'total_tax_amount'      => $totalTax,
                'invoice_value'         => $invoiceValue,
                'reverse_charge'        => false,
                'is_ecommerce'          => false,
            ]);
        }
    }

    public function store(
        $invoice,
        $voucher,
        int $voucherTypeId,
        GstDocumentType $documentType = GstDocumentType::INV,
        bool $isAmendment = false
    ): void {
        // On amendment: capture original invoice details before deleting old entries
        $originalInvoiceNo   = null;
        $originalInvoiceDate = null;

        if ($isAmendment) {
            $existing = GstEntry::where('voucher_id', $voucher->id)->first();
            if ($existing) {
                $originalInvoiceNo   = $existing->original_invoice_no ?? $existing->invoice_no;
                $originalInvoiceDate = $existing->original_invoice_date ?? $existing->invoice_date;
            }
        }

        GstEntry::where('voucher_id', $voucher->id)->delete();

        $invoice->loadMissing('details.item.unit', 'account.taxDetail', 'account.state');

        $account       = $invoice->account;
        $gstin         = $account?->taxDetail?->gst_number;
        $gstType       = $invoice->gst_type ?? $invoice->tax_type ?? null;
        $supplyType    = $this->resolveSupplyType($gstin, $gstType);
        $placeOfSupply = $this->resolvePlaceOfSupply($account, $gstin);

        $invoiceValue = (float) ($invoice->grand_total ?? $invoice->net_amount ?? 0);

        foreach ($invoice->details as $detail) {
            $item     = $detail->item;
            $totalTax = ($detail->cgst_amount ?? 0)
                + ($detail->sgst_amount ?? 0)
                + ($detail->igst_amount ?? 0);

            GstEntry::create([
                'company_id'            => $invoice->company_id,
                'financial_year_id'     => $invoice->financial_year_id,
                'voucher_id'            => $voucher->id,
                'account_id'            => $invoice->account_id,
                'account_name'          => $account?->name,
                'gstin'                 => $gstin,
                'voucher_type_id'       => $voucherTypeId,
                'document_type'         => $documentType,
                'invoice_no'            => $invoice->invoice_serial,
                'invoice_date'          => $invoice->invoice_date,
                'is_amendment'          => $isAmendment,
                'original_invoice_no'   => $originalInvoiceNo,
                'original_invoice_date' => $originalInvoiceDate,
                'item_id'               => $detail->item_id,
                'item_name'             => $item?->name,
                'hsn_code'              => $item?->hsn_sac_code,
                'uqc'                   => $item?->unit?->name,
                'qty'                   => $detail->quantity ?? 0,
                'supply_type'           => $supplyType,
                'place_of_supply'       => $placeOfSupply,
                'taxable_amount'        => $detail->taxable_amount ?? 0,
                'cgst_rate'             => $detail->cgst_rate ?? 0,
                'cgst_amount'           => $detail->cgst_amount ?? 0,
                'sgst_rate'             => $detail->sgst_rate ?? 0,
                'sgst_amount'           => $detail->sgst_amount ?? 0,
                'igst_rate'             => $detail->igst_rate ?? 0,
                'igst_amount'           => $detail->igst_amount ?? 0,
                'cess_rate'             => 0,
                'cess_amount'           => 0,
                'total_tax_amount'      => $totalTax,
                'invoice_value'         => $invoiceValue,
                'reverse_charge'        => false,
                'is_ecommerce'          => false,
            ]);
        }
    }

    private function resolvePlaceOfSupply($account, ?string $gstin): ?string
    {
        $state = $account?->state;

        if (!$state && !empty($account?->state_id)) {
            $state = \App\Models\State::find($account->state_id);
        }

        if ($state) {
            $gstCode = $state->gst_code ? str_pad((string) $state->gst_code, 2, '0', STR_PAD_LEFT) : '';
            return $gstCode ? "{$gstCode}-{$state->name}" : $state->name;
        }

        if (!empty($gstin) && strlen($gstin) >= 2) {
            $gstCode = (int) substr($gstin, 0, 2);
            $state = \App\Models\State::where('gst_code', $gstCode)->first();
            if ($state) {
                $code = str_pad((string) $state->gst_code, 2, '0', STR_PAD_LEFT);
                return "{$code}-{$state->name}";
            }
        }

        return null;
    }

    private function resolveSupplyType(?string $gstin, ?string $gstType): GstSupplyType
    {
        if (!empty($gstin)) {
            return GstSupplyType::B2B;
        }

        if ($gstType === 'interstate') {
            return GstSupplyType::B2CL;
        }

        return GstSupplyType::B2CS;
    }
}

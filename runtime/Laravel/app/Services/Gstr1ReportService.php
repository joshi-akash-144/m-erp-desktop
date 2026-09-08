<?php

namespace App\Services;

use App\Enums\GstDocumentType;
use App\Enums\GstSupplyType;
use App\Models\GstEntry;
use App\Models\VoucherType;

class Gstr1ReportService
{
    public function getSummary(int $companyId, int $financialYearId, array $filters): array
    {
        $base = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $notesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        return [
            'b2b'           => $this->aggregate((clone $base)->where('supply_type', GstSupplyType::B2B)->where('total_tax_amount', '>', 0)),
            'b2cl'          => $this->aggregate((clone $base)->where('supply_type', GstSupplyType::B2CL)),
            'b2cs'          => $this->aggregate((clone $base)->where('supply_type', GstSupplyType::B2CS)),
            'cdnr'          => $this->aggregate((clone $notesBase)->whereNotNull('gstin')),
            'cdnu'          => $this->aggregate((clone $notesBase)->whereNull('gstin')),
            'exports'       => $this->aggregate((clone $base)->where('supply_type', GstSupplyType::EXPORT)),
            'nil_rated'     => $this->aggregateNil((clone $base)->where('total_tax_amount', 0)),
            'hsn_summary'   => $this->hsnSummary(clone $base),
            'doc_summary'   => $this->docSummary($companyId, $financialYearId, $filters),
            'tax_liability' => $this->taxLiability(clone $base),
        ];
    }

    private function aggregate($query): array
    {
        $row = $query->selectRaw('
            COUNT(DISTINCT invoice_no) as invoice_count,
            SUM(invoice_value)         as invoice_value,
            SUM(taxable_amount)        as taxable_amount,
            SUM(total_tax_amount)      as total_tax,
            SUM(cgst_amount)           as cgst,
            SUM(sgst_amount)           as sgst,
            SUM(igst_amount)           as igst,
            SUM(cess_amount)           as cess
        ')->first();

        return [
            'count'          => (int)   ($row->invoice_count ?? 0),
            'invoice_value'  => (float) ($row->invoice_value  ?? 0),
            'taxable_amount' => (float) ($row->taxable_amount ?? 0),
            'total_tax'      => (float) ($row->total_tax      ?? 0),
            'cgst'           => (float) ($row->cgst           ?? 0),
            'sgst'           => (float) ($row->sgst           ?? 0),
            'igst'           => (float) ($row->igst           ?? 0),
            'cess'           => (float) ($row->cess           ?? 0),
        ];
    }

    private function aggregateNil($query): array
    {
        $row = $query->selectRaw('
            COUNT(DISTINCT invoice_no) as invoice_count,
            SUM(taxable_amount)        as taxable_amount
        ')->first();

        return [
            'count'          => (int)   ($row->invoice_count ?? 0),
            'invoice_value'  => 0,
            'taxable_amount' => (float) ($row->taxable_amount ?? 0),
            'total_tax'      => 0,
            'cgst'           => 0,
            'sgst'           => 0,
            'igst'           => 0,
            'cess'           => 0,
        ];
    }

    private function hsnSummary($query): array
    {
        return $query->selectRaw('
            hsn_code,
            uqc,
            SUM(qty)                   as total_qty,
            SUM(invoice_value)         as invoice_value,
            SUM(taxable_amount)        as taxable_amount,
            SUM(total_tax_amount)      as total_tax,
            MAX(igst_rate)             as igst_rate,
            MAX(cgst_rate)             as cgst_rate,
            MAX(sgst_rate)             as sgst_rate,
            SUM(cgst_amount)           as cgst,
            SUM(sgst_amount)           as sgst,
            SUM(igst_amount)           as igst,
            SUM(cess_amount)           as cess
        ')
        ->groupBy('hsn_code', 'uqc')
        ->orderBy('taxable_amount', 'desc')
        ->get()
        ->map(fn($r) => [
            'hsn_code'       => $r->hsn_code ?? '-',
            'uqc'            => $r->uqc ?? 'OTH',
            'total_qty'      => (float) $r->total_qty,
            'invoice_value'  => (float) $r->invoice_value,
            'taxable_amount' => (float) $r->taxable_amount,
            'total_tax'      => (float) $r->total_tax,
            'igst_rate'      => (float) $r->igst_rate,
            'cgst_rate'      => (float) $r->cgst_rate,
            'sgst_rate'      => (float) $r->sgst_rate,
            'cgst'           => (float) $r->cgst,
            'sgst'           => (float) $r->sgst,
            'igst'           => (float) $r->igst,
            'cess'           => (float) $r->cess,
        ])
        ->toArray();
    }

    private function docSummary(int $companyId, int $financialYearId, array $filters): array
    {
        $totalDocs = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']))
            ->distinct('invoice_no')
            ->count('invoice_no');

        return [
            'total_docs'     => $totalDocs,
            'cancelled_docs' => 0,
            'net_issued'     => $totalDocs,
        ];
    }

    public function getDetail(int $companyId, int $financialYearId, array $filters): array
    {
        $section = $filters['section'] ?? '';

        $invoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $notesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        if ($section === 'hsn_detail') {
            $hsnCode = $filters['hsn_code'] ?? null;
            $paginator = (clone $invoiceBase)
                ->when($hsnCode, fn($q) => $q->where('hsn_code', $hsnCode))
                ->selectRaw('
                    invoice_no, invoice_date, account_name, hsn_code, item_name, uqc,
                    SUM(qty)               as qty,
                    SUM(taxable_amount)    as taxable_amount,
                    MAX(cgst_rate)         as cgst_rate,
                    SUM(cgst_amount)       as cgst_amount,
                    MAX(sgst_rate)         as sgst_rate,
                    SUM(sgst_amount)       as sgst_amount,
                    MAX(igst_rate)         as igst_rate,
                    SUM(igst_amount)       as igst_amount,
                    SUM(cess_amount)       as cess_amount,
                    SUM(total_tax_amount)  as total_tax_amount,
                    SUM(invoice_value)     as invoice_value
                ')
                ->groupBy('invoice_no', 'invoice_date', 'account_name', 'hsn_code', 'item_name', 'uqc')
                ->orderBy('invoice_date')->orderBy('invoice_no')
                ->paginate((int)($filters['size'] ?? 100));

            $paginator->getCollection()->transform(fn($r) => [
                'invoice_no'       => $r->invoice_no,
                'invoice_date'     => $r->invoice_date,
                'account_name'     => $r->account_name ?? '-',
                'gstin'            => '-',
                'place_of_supply'  => '-',
                'hsn_code'         => $r->hsn_code ?? '-',
                'item_name'        => $r->item_name ?? '-',
                'uqc'              => $r->uqc ?? '-',
                'qty'              => (float) $r->qty,
                'taxable_amount'   => (float) $r->taxable_amount,
                'cgst_rate'        => (float) $r->cgst_rate,
                'cgst_amount'      => (float) $r->cgst_amount,
                'sgst_rate'        => (float) $r->sgst_rate,
                'sgst_amount'      => (float) $r->sgst_amount,
                'igst_rate'        => (float) $r->igst_rate,
                'igst_amount'      => (float) $r->igst_amount,
                'cess_amount'      => (float) $r->cess_amount,
                'total_tax_amount' => (float) $r->total_tax_amount,
                'invoice_value'    => (float) $r->invoice_value,
                'reverse_charge'   => false,
                'document_type'    => 'INV',
                'supply_type'      => '-',
            ]);
            
            return $paginator->toArray();
        }

        $query = match ($section) {
            'b2b'       => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2B)->where('total_tax_amount', '>', 0),
            'b2cl'      => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2CL),
            'b2cs'      => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2CS),
            'cdnr'      => (clone $notesBase)->whereNotNull('gstin'),
            'cdnu'      => (clone $notesBase)->whereNull('gstin'),
            'exports'   => (clone $invoiceBase)->where('supply_type', GstSupplyType::EXPORT),
            'nil_rated' => (clone $invoiceBase)->where('total_tax_amount', 0),
            default     => (clone $invoiceBase),
        };

        $paginator = $query->selectRaw('
            invoice_no, invoice_date, account_name, gstin, place_of_supply,
            document_type, supply_type, reverse_charge, voucher_type_id,
            SUM(taxable_amount)    as taxable_amount,
            MAX(cgst_rate)         as cgst_rate,
            SUM(cgst_amount)       as cgst_amount,
            MAX(sgst_rate)         as sgst_rate,
            SUM(sgst_amount)       as sgst_amount,
            MAX(igst_rate)         as igst_rate,
            SUM(igst_amount)       as igst_amount,
            SUM(cess_amount)       as cess_amount,
            SUM(total_tax_amount)  as total_tax_amount,
            SUM(invoice_value)     as invoice_value
        ')
        ->groupBy('invoice_no', 'invoice_date', 'account_name', 'gstin', 'place_of_supply', 'document_type', 'supply_type', 'reverse_charge', 'voucher_type_id')
        ->orderBy('invoice_date')->orderBy('invoice_no')
        ->paginate((int)($filters['size'] ?? 100));
        
        $paginator->getCollection()->transform(fn($r) => [
            'invoice_no'       => $r->invoice_no,
            'invoice_date'     => $r->invoice_date,
            'account_name'     => $r->account_name ?? '-',
            'gstin'            => $r->gstin ?? '-',
            'place_of_supply'  => self::formatPlaceOfSupply($r->place_of_supply, $r->gstin),
            'hsn_code'         => '-',
            'item_name'        => '-',
            'uqc'              => '-',
            'qty'              => 0,
            'taxable_amount'   => (float) $r->taxable_amount,
            'cgst_rate'        => (float) $r->cgst_rate,
            'cgst_amount'      => (float) $r->cgst_amount,
            'sgst_rate'        => (float) $r->sgst_rate,
            'sgst_amount'      => (float) $r->sgst_amount,
            'igst_rate'        => (float) $r->igst_rate,
            'igst_amount'      => (float) $r->igst_amount,
            'cess_amount'      => (float) $r->cess_amount,
            'total_tax_amount' => (float) $r->total_tax_amount,
            'invoice_value'    => (float) $r->invoice_value,
            'reverse_charge'   => (bool) $r->reverse_charge,
            'document_type'    => $r->document_type ?? '-',
            'supply_type'      => $r->supply_type ?? '-',
            'voucher_type_id'  => $r->voucher_type_id,
        ]);

        return $paginator->toArray();
    }

    public function getDetailForExport(int $companyId, int $financialYearId, array $filters): array
    {
        $section = $filters['section'] ?? '';

        $invoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $notesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        if ($section === 'hsn_summary') {
            return $this->hsnSummary(clone $invoiceBase);
        }

        if ($section === 'hsn_detail') {
            $hsnCode = $filters['hsn_code'] ?? null;
            return (clone $invoiceBase)
                ->when($hsnCode, fn($q) => $q->where('hsn_code', $hsnCode))
                ->selectRaw('
                    invoice_no, invoice_date, account_name, hsn_code, item_name, uqc,
                    SUM(qty)               as qty,
                    SUM(taxable_amount)    as taxable_amount,
                    MAX(cgst_rate)         as cgst_rate,
                    SUM(cgst_amount)       as cgst_amount,
                    MAX(sgst_rate)         as sgst_rate,
                    SUM(sgst_amount)       as sgst_amount,
                    MAX(igst_rate)         as igst_rate,
                    SUM(igst_amount)       as igst_amount,
                    SUM(cess_amount)       as cess_amount,
                    SUM(total_tax_amount)  as total_tax_amount,
                    SUM(invoice_value)     as invoice_value
                ')
                ->groupBy('invoice_no', 'invoice_date', 'account_name', 'hsn_code', 'item_name', 'uqc')
                ->orderBy('invoice_date')->orderBy('invoice_no')
                ->get()
                ->map(fn($r) => [
                    'invoice_no'       => $r->invoice_no,
                    'invoice_date'     => $r->invoice_date,
                    'account_name'     => $r->account_name ?? '-',
                    'gstin'            => '-',
                    'place_of_supply'  => '-',
                    'hsn_code'         => $r->hsn_code ?? '-',
                    'item_name'        => $r->item_name ?? '-',
                    'uqc'              => $r->uqc ?? '-',
                    'qty'              => (float) $r->qty,
                    'taxable_amount'   => (float) $r->taxable_amount,
                    'cgst_rate'        => (float) $r->cgst_rate,
                    'cgst_amount'      => (float) $r->cgst_amount,
                    'sgst_rate'        => (float) $r->sgst_rate,
                    'sgst_amount'      => (float) $r->sgst_amount,
                    'igst_rate'        => (float) $r->igst_rate,
                    'igst_amount'      => (float) $r->igst_amount,
                    'cess_amount'      => (float) $r->cess_amount,
                    'total_tax_amount' => (float) $r->total_tax_amount,
                    'invoice_value'    => (float) $r->invoice_value,
                    'reverse_charge'   => false,
                    'document_type'    => 'INV',
                    'supply_type'      => '-',
                ])
                ->toArray();
        }

        $query = match ($section) {
            'b2b'       => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2B)->where('total_tax_amount', '>', 0),
            'b2cl'      => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2CL),
            'b2cs'      => (clone $invoiceBase)->where('supply_type', GstSupplyType::B2CS),
            'cdnr'      => (clone $notesBase)->whereNotNull('gstin'),
            'cdnu'      => (clone $notesBase)->whereNull('gstin'),
            'exports'   => (clone $invoiceBase)->where('supply_type', GstSupplyType::EXPORT),
            'nil_rated' => (clone $invoiceBase)->where('total_tax_amount', 0),
            default     => (clone $invoiceBase),
        };

        return $query->selectRaw('
            invoice_no, invoice_date, account_name, gstin, place_of_supply,
            document_type, supply_type, reverse_charge, voucher_type_id,
            SUM(taxable_amount)    as taxable_amount,
            MAX(cgst_rate)         as cgst_rate,
            SUM(cgst_amount)       as cgst_amount,
            MAX(sgst_rate)         as sgst_rate,
            SUM(sgst_amount)       as sgst_amount,
            MAX(igst_rate)         as igst_rate,
            SUM(igst_amount)       as igst_amount,
            SUM(cess_amount)       as cess_amount,
            SUM(total_tax_amount)  as total_tax_amount,
            SUM(invoice_value)     as invoice_value
        ')
        ->groupBy('invoice_no', 'invoice_date', 'account_name', 'gstin', 'place_of_supply', 'document_type', 'supply_type', 'reverse_charge', 'voucher_type_id')
        ->orderBy('invoice_date')->orderBy('invoice_no')
        ->get()
        ->map(fn($r) => [
            'invoice_no'       => $r->invoice_no,
            'invoice_date'     => $r->invoice_date,
            'account_name'     => $r->account_name ?? '-',
            'gstin'            => $r->gstin ?? '-',
            'place_of_supply'  => self::formatPlaceOfSupply($r->place_of_supply, $r->gstin),
            'hsn_code'         => '-',
            'item_name'        => '-',
            'uqc'              => '-',
            'qty'              => 0,
            'taxable_amount'   => (float) $r->taxable_amount,
            'cgst_rate'        => (float) $r->cgst_rate,
            'cgst_amount'      => (float) $r->cgst_amount,
            'sgst_rate'        => (float) $r->sgst_rate,
            'sgst_amount'      => (float) $r->sgst_amount,
            'igst_rate'        => (float) $r->igst_rate,
            'igst_amount'      => (float) $r->igst_amount,
            'cess_amount'      => (float) $r->cess_amount,
            'total_tax_amount' => (float) $r->total_tax_amount,
            'invoice_value'    => (float) $r->invoice_value,
            'reverse_charge'   => (bool) $r->reverse_charge,
            'document_type'    => $r->document_type ?? '-',
            'supply_type'      => $r->supply_type ?? '-',
            'voucher_type_id'  => $r->voucher_type_id,
        ])
        ->toArray();
    }

    private function taxLiability($query): array
    {
        $row = $query->selectRaw('
            SUM(cgst_amount) as cgst,
            SUM(sgst_amount) as sgst,
            SUM(igst_amount) as igst,
            SUM(cess_amount) as cess
        ')->first();

        return [
            'cgst' => (float) ($row->cgst ?? 0),
            'sgst' => (float) ($row->sgst ?? 0),
            'igst' => (float) ($row->igst ?? 0),
            'cess' => (float) ($row->cess ?? 0),
        ];
    }

    private static ?array $statesById = null;

    private static function formatPlaceOfSupply(?string $pos, ?string $gstin = null): string
    {
        if (empty($pos) || $pos === '-') {
            if (!empty($gstin) && strlen($gstin) >= 2 && is_numeric(substr($gstin, 0, 2))) {
                $code = (int) substr($gstin, 0, 2);
                if (self::$statesById === null) {
                    self::$statesById = \App\Models\State::all()->keyBy('id')->all();
                }
                $state = collect(self::$statesById)->firstWhere('gst_code', $code);
                if ($state) {
                    $gstCode = str_pad((string) $state->gst_code, 2, '0', STR_PAD_LEFT);
                    return "{$gstCode}-{$state->name}";
                }
            }
            return '-';
        }

        // If it is already a full string (not numeric), return directly without fetching master
        if (!is_numeric($pos)) {
            return $pos;
        }

        // If only a number value (State ID), fetch state data by ID
        $stateId = (int) $pos;
        if (self::$statesById === null) {
            self::$statesById = \App\Models\State::all()->keyBy('id')->all();
        }

        if (isset(self::$statesById[$stateId])) {
            $state = self::$statesById[$stateId];
            $gstCode = $state->gst_code ? str_pad((string) $state->gst_code, 2, '0', STR_PAD_LEFT) : '';
            return $gstCode ? "{$gstCode}-{$state->name}" : $state->name;
        }

        return $pos;
    }
}

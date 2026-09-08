<?php

namespace App\Services;

use App\Enums\GstDocumentType;
use App\Enums\GstSupplyType;
use App\Models\GstEntry;
use App\Models\State;
use App\Models\VoucherType;

class Gstr3bReportService
{
    private static ?array $statesById = null;

    public function getSummary(int $companyId, int $financialYearId, array $filters): array
    {
        $company = \App\Models\Company::with('state')->find($companyId);
        $companyGstCode = $company?->state?->gst_code ? (int) $company->state->gst_code : null;

        // ── Base Outward Queries (Sales) ──
        $salesInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $salesNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        // ── Base Inward Queries (Purchases) ──
        $purchaseInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::PURCHASE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $purchaseNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::PURCHASE_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::DEBIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        // ═══════════════════════════════════════════════════════════════════════════
        // Table 3.1: Outward Supplies and Inward Supplies Liable to Reverse Charge
        // ═══════════════════════════════════════════════════════════════════════════
        // 3.1(a) Outward Taxable supplies (other than zero rated, nil rated and exempted)
        $outwardTaxableInv = (clone $salesInvoiceBase)
            ->whereIn('supply_type', [GstSupplyType::B2B, GstSupplyType::B2CL, GstSupplyType::B2CS])
            ->where('total_tax_amount', '>', 0);
        $sec31a = $this->aggregate(clone $outwardTaxableInv);

        // 3.1(b) Outward Taxable supplies (zero rated - Exports)
        $outwardZeroRated = (clone $salesInvoiceBase)->where('supply_type', GstSupplyType::EXPORT);
        $sec31b = $this->aggregate(clone $outwardZeroRated);

        // 3.1(c) Other outward supplies (Nil rated, exempted)
        $outwardNilRated = (clone $salesInvoiceBase)->where('total_tax_amount', 0)->where('supply_type', '!=', GstSupplyType::EXPORT);
        $sec31c = $this->aggregateNil(clone $outwardNilRated);

        // 3.1(d) Inward supplies (liable to reverse charge)
        $inwardRcm = (clone $purchaseInvoiceBase)->where('reverse_charge', 1)->where('total_tax_amount', '>', 0);
        $sec31d = $this->aggregate(clone $inwardRcm);

        // 3.1(e) Non-GST outward supplies
        $sec31e = $this->emptyRowData();

        // ═══════════════════════════════════════════════════════════════════════════
        // Table 3.2: Inter-State supplies made to unregistered persons
        // ═══════════════════════════════════════════════════════════════════════════
        $sec32Unregistered = $this->getInterstateUnregisteredSummary(clone $salesInvoiceBase);

        // ═══════════════════════════════════════════════════════════════════════════
        // Table 4: Eligible Input Tax Credit (ITC)
        // ═══════════════════════════════════════════════════════════════════════════
        // 4(A)(1) Import of Goods
        $itcImportGoods = (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_GOODS);
        $sec4a1 = $this->aggregate(clone $itcImportGoods);

        // 4(A)(2) Import of Services
        $itcImportServices = (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_SERVICES);
        $sec4a2 = $this->aggregate(clone $itcImportServices);

        // 4(A)(3) Inward supplies liable to reverse charge
        $sec4a3 = $sec31d; // Same RCM inward

        // 4(A)(4) Inward supplies from ISD
        $sec4a4 = $this->emptyRowData();

        // 4(A)(5) All other ITC (Regular B2B Purchases non-RCM, non-import)
        $itcAllOther = (clone $purchaseInvoiceBase)
            ->where(function ($q) {
                $q->whereNull('reverse_charge')->orWhere('reverse_charge', 0);
            })
            ->whereNotIn('supply_type', [GstSupplyType::IMPORT_GOODS, GstSupplyType::IMPORT_SERVICES])
            ->where('total_tax_amount', '>', 0);
        $sec4a5 = $this->aggregate(clone $itcAllOther);

        // Total ITC Available 4(A)
        $sec4aTotal = [
            'count'          => $sec4a1['count'] + $sec4a2['count'] + $sec4a3['count'] + $sec4a5['count'],
            'invoice_value'  => $sec4a1['invoice_value'] + $sec4a2['invoice_value'] + $sec4a3['invoice_value'] + $sec4a5['invoice_value'],
            'taxable_amount' => $sec4a1['taxable_amount'] + $sec4a2['taxable_amount'] + $sec4a3['taxable_amount'] + $sec4a5['taxable_amount'],
            'total_tax'      => $sec4a1['total_tax'] + $sec4a2['total_tax'] + $sec4a3['total_tax'] + $sec4a5['total_tax'],
            'cgst'           => $sec4a1['cgst'] + $sec4a2['cgst'] + $sec4a3['cgst'] + $sec4a5['cgst'],
            'sgst'           => $sec4a1['sgst'] + $sec4a2['sgst'] + $sec4a3['sgst'] + $sec4a5['sgst'],
            'igst'           => $sec4a1['igst'] + $sec4a2['igst'] + $sec4a3['igst'] + $sec4a5['igst'],
            'cess'           => $sec4a1['cess'] + $sec4a2['cess'] + $sec4a3['cess'] + $sec4a5['cess'],
        ];

        // 4(B)(1) ITC Reversed - Rules 42 & 43
        $sec4b1 = $this->emptyRowData();

        // 4(B)(2) ITC Reversed - Others (Purchase Return / Debit Notes)
        $sec4b2 = $this->aggregate(clone $purchaseNotesBase);

        // Total ITC Reversed 4(B)
        $sec4bTotal = $sec4b2;

        // 4(C) Net ITC Available (4(A) - 4(B))
        $sec4c = [
            'count'          => max(0, $sec4aTotal['count'] - $sec4bTotal['count']),
            'invoice_value'  => max(0, $sec4aTotal['invoice_value'] - $sec4bTotal['invoice_value']),
            'taxable_amount' => max(0, $sec4aTotal['taxable_amount'] - $sec4bTotal['taxable_amount']),
            'total_tax'      => max(0, $sec4aTotal['total_tax'] - $sec4bTotal['total_tax']),
            'cgst'           => max(0, $sec4aTotal['cgst'] - $sec4bTotal['cgst']),
            'sgst'           => max(0, $sec4aTotal['sgst'] - $sec4bTotal['sgst']),
            'igst'           => max(0, $sec4aTotal['igst'] - $sec4bTotal['igst']),
            'cess'           => max(0, $sec4aTotal['cess'] - $sec4bTotal['cess']),
        ];

        // 4(D) Ineligible ITC
        $sec4d1 = $this->emptyRowData();
        $sec4d2 = $this->emptyRowData();

        // ═══════════════════════════════════════════════════════════════════════════
        // Table 5: Values of exempt, nil-rated and non-GST inward supplies
        // ═══════════════════════════════════════════════════════════════════════════
        $inwardNilRated = (clone $purchaseInvoiceBase)->where('total_tax_amount', 0);
        $sec5Nil = $this->aggregateNil(clone $inwardNilRated, $companyGstCode);
        $sec5NonGst = $this->emptyRowData();

        // ═══════════════════════════════════════════════════════════════════════════
        // Table 6: Payment of Tax (Net Tax Payable)
        // ═══════════════════════════════════════════════════════════════════════════
        // Total Tax Liability (3.1(a) + 3.1(b) + 3.1(d))
        $totalLiability = [
            'cgst'  => $sec31a['cgst'] + $sec31d['cgst'],
            'sgst'  => $sec31a['sgst'] + $sec31d['sgst'],
            'igst'  => $sec31a['igst'] + $sec31b['igst'] + $sec31d['igst'],
            'cess'  => $sec31a['cess'] + $sec31b['cess'] + $sec31d['cess'],
            'total' => $sec31a['total_tax'] + $sec31b['total_tax'] + $sec31d['total_tax'],
        ];

        // Paid through ITC (Net ITC available from 4C)
        $paidThroughItc = [
            'cgst'  => min($totalLiability['cgst'], $sec4c['cgst']),
            'sgst'  => min($totalLiability['sgst'], $sec4c['sgst']),
            'igst'  => min($totalLiability['igst'], $sec4c['igst']),
            'cess'  => min($totalLiability['cess'], $sec4c['cess']),
            'total' => 0,
        ];
        $paidThroughItc['total'] = $paidThroughItc['cgst'] + $paidThroughItc['sgst'] + $paidThroughItc['igst'] + $paidThroughItc['cess'];

        // Tax Payable in Cash (Liability - ITC)
        $taxPayableCash = [
            'cgst'  => max(0, $totalLiability['cgst'] - $sec4c['cgst']),
            'sgst'  => max(0, $totalLiability['sgst'] - $sec4c['sgst']),
            'igst'  => max(0, $totalLiability['igst'] - $sec4c['igst']),
            'cess'  => max(0, $totalLiability['cess'] - $sec4c['cess']),
            'total' => 0,
        ];
        $taxPayableCash['total'] = $taxPayableCash['cgst'] + $taxPayableCash['sgst'] + $taxPayableCash['igst'] + $taxPayableCash['cess'];

        return [
            'sec_3_1_a'         => $sec31a,
            'sec_3_1_b'         => $sec31b,
            'sec_3_1_c'         => $sec31c,
            'sec_3_1_d'         => $sec31d,
            'sec_3_1_e'         => $sec31e,
            'sec_3_2'           => $sec32Unregistered,
            'sec_4_a_1'         => $sec4a1,
            'sec_4_a_2'         => $sec4a2,
            'sec_4_a_3'         => $sec4a3,
            'sec_4_a_4'         => $sec4a4,
            'sec_4_a_5'         => $sec4a5,
            'sec_4_a_total'     => $sec4aTotal,
            'sec_4_b_1'         => $sec4b1,
            'sec_4_b_2'         => $sec4b2,
            'sec_4_b_total'     => $sec4bTotal,
            'sec_4_c'           => $sec4c,
            'sec_4_d_1'         => $sec4d1,
            'sec_4_d_2'         => $sec4d2,
            'sec_5_nil'         => $sec5Nil,
            'sec_5_nongst'      => $sec5NonGst,
            'total_liability'   => $totalLiability,
            'paid_through_itc'  => $paidThroughItc,
            'tax_payable_cash'  => $taxPayableCash,
        ];
    }

    private function getInterstateUnregisteredSummary($query): array
    {
        $rows = $query->whereIn('supply_type', [GstSupplyType::B2CL, GstSupplyType::B2CS])
            ->where('igst_amount', '>', 0)
            ->selectRaw('
                place_of_supply,
                COUNT(DISTINCT invoice_no) as invoice_count,
                SUM(taxable_amount)        as taxable_amount,
                SUM(igst_amount)           as igst_amount,
                SUM(cess_amount)           as cess_amount
            ')
            ->groupBy('place_of_supply')
            ->get();

        return $rows->map(fn($r) => [
            'place_of_supply' => self::formatPlaceOfSupply($r->place_of_supply),
            'count'           => (int) $r->invoice_count,
            'taxable_amount'  => (float) $r->taxable_amount,
            'igst_amount'     => (float) $r->igst_amount,
            'cess_amount'     => (float) $r->cess_amount,
        ])->toArray();
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

    private function aggregateNil($query, $companyGstCode = null): array
    {
        $rows = $query->selectRaw('
            place_of_supply,
            gstin,
            COUNT(DISTINCT invoice_no) as invoice_count,
            SUM(taxable_amount)        as taxable_amount,
            SUM(invoice_value)         as invoice_value
        ')->groupBy('place_of_supply', 'gstin')->get();

        $interCount = 0; $interTaxable = 0; $interInvoice = 0;
        $intraCount = 0; $intraTaxable = 0; $intraInvoice = 0;
        $totalInvoiceValue = 0;
        
        foreach ($rows as $row) {
            $totalInvoiceValue += (float) $row->invoice_value;
            
            $posStr = $row->place_of_supply;
            $gstin = $row->gstin;
            $posGstCode = null;

            if (!empty($posStr) && $posStr !== '-') {
                if (is_numeric($posStr)) {
                    $stateId = (int) $posStr;
                    if (self::$statesById === null) {
                        self::$statesById = \App\Models\State::all()->keyBy('id')->all();
                    }
                    if (isset(self::$statesById[$stateId])) {
                        $posGstCode = (int) self::$statesById[$stateId]->gst_code;
                    }
                } else {
                    preg_match('/^(\d{1,2})/', $posStr, $matches);
                    if (!empty($matches[1])) {
                        $posGstCode = (int) $matches[1];
                    }
                }
            } elseif (!empty($gstin) && strlen($gstin) >= 2 && is_numeric(substr($gstin, 0, 2))) {
                $posGstCode = (int) substr($gstin, 0, 2);
            }

            if ($companyGstCode && $posGstCode !== null && $posGstCode !== $companyGstCode) {
                $interCount += $row->invoice_count;
                $interTaxable += (float) $row->taxable_amount;
                $interInvoice += (float) $row->invoice_value;
            } else {
                $intraCount += $row->invoice_count;
                $intraTaxable += (float) $row->taxable_amount;
                $intraInvoice += (float) $row->invoice_value;
            }
        }

        return [
            'count'                => $interCount + $intraCount,
            'inter_taxable_amount' => $interTaxable,
            'intra_taxable_amount' => $intraTaxable,
            'inter_invoice_value'  => $interInvoice,
            'intra_invoice_value'  => $intraInvoice,
            'taxable_amount'       => $interTaxable + $intraTaxable,
            'invoice_value'        => $totalInvoiceValue,
            'total_tax'            => 0,
            'cgst'                 => 0,
            'sgst'                 => 0,
            'igst'                 => 0,
            'cess'                 => 0,
        ];
    }

    private function emptyRowData(): array
    {
        return [
            'count'                => 0,
            'invoice_value'        => 0,
            'taxable_amount'       => 0,
            'inter_taxable_amount' => 0,
            'intra_taxable_amount' => 0,
            'total_tax'            => 0,
            'cgst'                 => 0,
            'sgst'           => 0,
            'igst'           => 0,
            'cess'           => 0,
        ];
    }

    public function getDetail(int $companyId, int $financialYearId, array $filters): array
    {
        $section = $filters['section'] ?? '';

        $salesInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $salesNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $purchaseInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::PURCHASE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $purchaseNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::PURCHASE_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::DEBIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $query = match ($section) {
            '3.1_a' => (clone $salesInvoiceBase)
                ->whereIn('supply_type', [GstSupplyType::B2B, GstSupplyType::B2CL, GstSupplyType::B2CS])
                ->where('total_tax_amount', '>', 0),

            '3.1_b' => (clone $salesInvoiceBase)->where('supply_type', GstSupplyType::EXPORT),

            '3.1_c' => (clone $salesInvoiceBase)->where('total_tax_amount', 0)->where('supply_type', '!=', GstSupplyType::EXPORT),

            '3.1_d', '4_a_3' => (clone $purchaseInvoiceBase)->where('reverse_charge', 1)->where('total_tax_amount', '>', 0),

            '3.2' => (clone $salesInvoiceBase)
                ->whereIn('supply_type', [GstSupplyType::B2CL, GstSupplyType::B2CS])
                ->where('igst_amount', '>', 0),

            '4_a_1' => (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_GOODS),

            '4_a_2' => (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_SERVICES),

            '4_a_5' => (clone $purchaseInvoiceBase)
                ->where(function ($q) {
                    $q->whereNull('reverse_charge')->orWhere('reverse_charge', 0);
                })
                ->whereNotIn('supply_type', [GstSupplyType::IMPORT_GOODS, GstSupplyType::IMPORT_SERVICES])
                ->where('total_tax_amount', '>', 0),

            '4_b_2' => (clone $purchaseNotesBase),

            '5_nil', '5_nil_inter', '5_nil_intra' => (clone $purchaseInvoiceBase)->where('total_tax_amount', 0),

            default => (clone $salesInvoiceBase),
        };

        if (in_array($section, ['5_nil_inter', '5_nil_intra'])) {
            $company = \App\Models\Company::with('state')->find($companyId);
            $companyGstCode = $company?->state?->gst_code ? (int) $company->state->gst_code : null;

            if ($companyGstCode) {
                $companyGstCodeStr = str_pad((string)$companyGstCode, 2, '0', STR_PAD_LEFT);
                $matchingStateIds = \App\Models\State::where('gst_code', $companyGstCode)->pluck('id')->toArray();
                
                $applyIntra = function($q) use ($companyGstCodeStr, $matchingStateIds) {
                    $q->whereIn('place_of_supply', $matchingStateIds)
                      ->orWhere('place_of_supply', 'like', $companyGstCodeStr . '-%')
                      ->orWhere('place_of_supply', $companyGstCodeStr)
                      ->orWhere(function($sq) use ($companyGstCodeStr) {
                          $sq->where(function($ssq) {
                              $ssq->whereNull('place_of_supply')
                                  ->orWhere('place_of_supply', '')
                                  ->orWhere('place_of_supply', '-');
                          })->where('gstin', 'like', $companyGstCodeStr . '%');
                      });
                };

                if ($section === '5_nil_intra') {
                    $query->where($applyIntra);
                } else {
                    $query->whereNot($applyIntra);
                }
            }
        }

        $paginator = $query->selectRaw('
            invoice_no, invoice_date, account_name, gstin, place_of_supply,
            document_type, voucher_type_id, supply_type, reverse_charge,
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
        ->groupBy('invoice_no', 'invoice_date', 'account_name', 'gstin', 'place_of_supply', 'document_type', 'voucher_type_id', 'supply_type', 'reverse_charge')
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
            'voucher_type_id'  => $r->voucher_type_id,
            'supply_type'      => $r->supply_type ?? '-',
        ]);

        return $paginator->toArray();
    }

    public function getDetailForExport(int $companyId, int $financialYearId, array $filters): array
    {
        $section = $filters['section'] ?? '';

        $salesInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::SALE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $salesNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::SALES_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::CREDIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $purchaseInvoiceBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('document_type', GstDocumentType::INV)
            ->where('voucher_type_id', VoucherType::PURCHASE_INVOICE)
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $purchaseNotesBase = GstEntry::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('document_type', [GstDocumentType::CRN, GstDocumentType::DBN])
            ->where(function ($query) {
                $query->where('voucher_type_id', VoucherType::PURCHASE_RETURN)
                      ->orWhere('voucher_type_id', VoucherType::DEBIT_NOTE);
            })
            ->when(!empty($filters['from_date']), fn($q) => $q->where('invoice_date', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']),   fn($q) => $q->where('invoice_date', '<=', $filters['to_date']));

        $query = match ($section) {
            '3.1_a' => (clone $salesInvoiceBase)
                ->whereIn('supply_type', [GstSupplyType::B2B, GstSupplyType::B2CL, GstSupplyType::B2CS])
                ->where('total_tax_amount', '>', 0),

            '3.1_b' => (clone $salesInvoiceBase)->where('supply_type', GstSupplyType::EXPORT),

            '3.1_c' => (clone $salesInvoiceBase)->where('total_tax_amount', 0)->where('supply_type', '!=', GstSupplyType::EXPORT),

            '3.1_d', '4_a_3' => (clone $purchaseInvoiceBase)->where('reverse_charge', 1)->where('total_tax_amount', '>', 0),

            '3.2' => (clone $salesInvoiceBase)
                ->whereIn('supply_type', [GstSupplyType::B2CL, GstSupplyType::B2CS])
                ->where('igst_amount', '>', 0),

            '4_a_1' => (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_GOODS),

            '4_a_2' => (clone $purchaseInvoiceBase)->where('supply_type', GstSupplyType::IMPORT_SERVICES),

            '4_a_5' => (clone $purchaseInvoiceBase)
                ->where(function ($q) {
                    $q->whereNull('reverse_charge')->orWhere('reverse_charge', 0);
                })
                ->whereNotIn('supply_type', [GstSupplyType::IMPORT_GOODS, GstSupplyType::IMPORT_SERVICES])
                ->where('total_tax_amount', '>', 0),

            '4_b_2' => (clone $purchaseNotesBase),

            '5_nil', '5_nil_inter', '5_nil_intra' => (clone $purchaseInvoiceBase)->where('total_tax_amount', 0),

            default => (clone $salesInvoiceBase),
        };

        if (in_array($section, ['5_nil_inter', '5_nil_intra'])) {
            $company = \App\Models\Company::with('state')->find($companyId);
            $companyGstCode = $company?->state?->gst_code ? (int) $company->state->gst_code : null;

            if ($companyGstCode) {
                $companyGstCodeStr = str_pad((string)$companyGstCode, 2, '0', STR_PAD_LEFT);
                $matchingStateIds = \App\Models\State::where('gst_code', $companyGstCode)->pluck('id')->toArray();
                
                $applyIntra = function($q) use ($companyGstCodeStr, $matchingStateIds) {
                    $q->whereIn('place_of_supply', $matchingStateIds)
                      ->orWhere('place_of_supply', 'like', $companyGstCodeStr . '-%')
                      ->orWhere('place_of_supply', $companyGstCodeStr)
                      ->orWhere(function($sq) use ($companyGstCodeStr) {
                          $sq->where(function($ssq) {
                              $ssq->whereNull('place_of_supply')
                                  ->orWhere('place_of_supply', '')
                                  ->orWhere('place_of_supply', '-');
                          })->where('gstin', 'like', $companyGstCodeStr . '%');
                      });
                };

                if ($section === '5_nil_intra') {
                    $query->where($applyIntra);
                } else {
                    $query->whereNot($applyIntra);
                }
            }
        }

        $items = $query->selectRaw('
            invoice_no, invoice_date, account_name, gstin, place_of_supply,
            document_type, voucher_type_id, supply_type, reverse_charge,
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
        ->groupBy('invoice_no', 'invoice_date', 'account_name', 'gstin', 'place_of_supply', 'document_type', 'voucher_type_id', 'supply_type', 'reverse_charge')
        ->orderBy('invoice_date')->orderBy('invoice_no')
        ->get();

        return $items->map(fn($r) => [
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
            'voucher_type_id'  => $r->voucher_type_id,
            'supply_type'      => $r->supply_type ?? '-',
        ])->toArray();
    }

    private static function formatPlaceOfSupply(?string $pos, ?string $gstin = null): string
    {
        if (empty($pos) || $pos === '-') {
            if (!empty($gstin) && strlen($gstin) >= 2 && is_numeric(substr($gstin, 0, 2))) {
                $code = (int) substr($gstin, 0, 2);
                if (self::$statesById === null) {
                    self::$statesById = State::all()->keyBy('id')->all();
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
            self::$statesById = State::all()->keyBy('id')->all();
        }

        if (isset(self::$statesById[$stateId])) {
            $state = self::$statesById[$stateId];
            $gstCode = $state->gst_code ? str_pad((string) $state->gst_code, 2, '0', STR_PAD_LEFT) : '';
            return $gstCode ? "{$gstCode}-{$state->name}" : $state->name;
        }

        return $pos;
    }
}

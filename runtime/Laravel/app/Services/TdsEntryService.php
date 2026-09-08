<?php

namespace App\Services;

use App\Enums\TdsEntryType;
use App\Models\BillSundry;
use App\Models\TdsEntry;
use App\Models\TdsCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TdsEntryService
{
    public function storeForPurchaseInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, TdsEntryType::Purchase);
    }

    public function updateForPurchaseInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, TdsEntryType::Purchase, isUpdate: true);
    }

    public function storeForSalesInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, TdsEntryType::Sales);
    }

    public function updateForSalesInvoice($invoice, $voucher): void
    {
        $this->store($invoice, $voucher, TdsEntryType::Sales, isUpdate: true);
    }

    private function store($invoice, $voucher, TdsEntryType $entryType, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            TdsEntry::where('voucher_id', $voucher->id)->delete();
        }

        $invoice->loadMissing('billSundries', 'account.taxDetail');

        $account    = $invoice->account;
        $pan        = $account?->taxDetail?->pan ?? null;
        $deductee   = $account?->name ?? '';

        $invoiceDate = $invoice->invoice_date ?? now()->toDateString();
        $refNo       = $invoice->reference_number ?? null;

        // Collect sundry IDs that have tds_category_id on the master bill sundry
        // $sundryIds = $invoice->billSundries->pluck('sundry_id')->filter()->unique()->toArray();
        // if (empty($sundryIds)) {
        //     return;
        // }

        // $masterSundries = BillSundry::whereIn('id', $sundryIds)
        //     ->whereNotNull('tds_category_id')
        //     ->with('tdsCategory')
        //     ->get()
        //     ->keyBy('id');

        // if ($masterSundries->isEmpty()) {
        //     return;
        // }

        foreach ($invoice->billSundries as $sundry) {
            // $master = $masterSundries->get($sundry->sundry_id);
            // if (!$master) {
            //     continue;
            // }

            
            if($sundry->code != 1009){
                continue;
            }

            if((float)$sundry->value == 0){
                continue;
            }

            // Fetch the 194Q (Code 519) TDS Category
            $tdsCategory = \App\Models\TdsCategory::where('company_id', $invoice->company_id)
                ->where('code', 519)
                ->first();

            $tdsAmount   = abs((float) ($sundry->value ?? 0));
            $tdsRate     = $sundry->rate_percent;
            $baseAmount  = (float) ($sundry->base_amount ?? 0);

            TdsEntry::create([
                'company_id'          => $invoice->company_id,
                'financial_year_id'   => $invoice->financial_year_id,
                'voucher_id'          => $voucher->id,
                'voucher_transaction_id' => null,
                'account_id'          => $invoice->account_id,
                'tds_category_id'     => $tdsCategory?->id,
                'reference_no'        => $refNo,
                'deductee_name'       => $deductee,
                'pan_no'              => $pan,
                'payment_amount'      => $baseAmount,
                'payment_date'        => $invoiceDate,
                'tds_rate'            => $tdsRate,
                'tds_amount'          => $tdsAmount,
                'total_deducted'      => $tdsAmount,
                'tax_deducted_on'     => $invoiceDate,
                'section_code'        => $tdsCategory?->section ?? '194Q',
                'entry_type'          => $entryType,
                'is_lower_deduction'  => false,
                'voucher_type_id'     => $voucher->voucher_type_id,
                'certificate_no'      => null,
                'remarks'             => $sundry->remarks ?? null,
            ]);
        }
    }

    public function getFilteredQuery(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = TdsEntry::with([
                'tdsCategory:id,section',
            ])
            ->select([
                'id',
                'company_id',
                'financial_year_id',
                'tds_category_id',
                'reference_no',
                'deductee_name',
                'pan_no',
                'payment_amount',
                'payment_date',
                'tds_rate',
                'tds_amount',
                'tax_deducted_on'
            ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        $query->when($request->filled('tds_category_id'), fn ($q) =>
            $q->where('tds_category_id', $request->tds_category_id));

        $query->when($request->filled('voucher_type_id'), fn ($q) =>
            $q->where('voucher_type_id', $request->voucher_type_id));

        $query->when($request->filled('from_date'), fn ($q) =>
            $q->whereDate('payment_date', '>=', $this->parseDate($request->from_date)));

        $query->when($request->filled('to_date'), fn ($q) =>
            $q->whereDate('payment_date', '<=', $this->parseDate($request->to_date)));

        $query->when($request->filled('pan_no'), fn ($q) =>
            $q->where('pan_no', 'like', '%' . addcslashes($request->pan_no, '%_\\') . '%'));

        return $query->orderByDesc('payment_date')->orderByDesc('id');
    }

    private function parseDate($date)
    {
        return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
    }
}

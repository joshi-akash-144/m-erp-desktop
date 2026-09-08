<?php

namespace App\Repositories;

use App\Models\Grn;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PurchaseInvoiceRepository extends BaseRepository
{
    public function __construct(PurchaseInvoice $purchaseInvoice)
    {
        parent::__construct($purchaseInvoice);
    }


    public function existsByReferenceNumberAndAccount(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $invoiceId = null): bool
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('reference_number', $referenceNumber)
            ->where('account_id', $accountId)
            ->when($invoiceId, function ($query) use ($invoiceId) {
                return $query->where('id', '!=', $invoiceId);
            })
            ->exists();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = [])
    {
        $mainSelect = ['id', 'invoice_number', 'invoice_serial', 'invoice_date', 'due_date', 'show_date', 'party_bill_date', 'grn_id', 'grn_number', 'grn_serial', 'file_number', 'sales_invoice_serial', 'account_id', 'gst_type', 'reference_number', 'broker_id', 'vehicle_number', 'remarks', 'tax_amount', 'paid_amount', 'net_amount', 'grand_total', 'payment_status', 'invoice_status', 'voucher_id', 'updated_by', 'created_by'];
        $detailsSelect = ['purchase_invoice_id', 'item_id', 'condition_id', 'destination_id', 'purchase_order_id', 'purchase_order_item_id', 'purchase_order_serial', 'quantity', 'party_quantity', 'bag_count', 'rate', 'inclusive_rate', 'taxable_amount', 'cgst_rate', 'sgst_rate', 'igst_rate', 'cgst_amount', 'sgst_amount', 'igst_amount', 'tax_amount', 'amount', 'net_amount', 'grand_total', 'remarks'];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);
        return $query->simplePaginate(
            $filters['size'],
            ['*'],
            'page',
            $filters['page']
        );
    }

    public function listAll(int $companyId, int $financialYearId, ?array $filters = [])
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->get();
    }

    public function countFiltered(int $companyId, int $financialYearId, array $filters): int
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->count();
    }

    public function countAll(int $companyId, int $financialYearId): int
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function getFilteredQuery(
        int $companyId,
        int $financialYearId,
        ?array $filters = null,
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ) {
        // Normalize filters (always an array)
        $filters = is_array($filters) ? $filters : [];

        return $this->model->query()
            ->with([
                // 'creator:id,name',
                // 'updater:id,name',
                // 'deleter:id,name',
                'account:id,name,city',
                // 'broker:id,name,city',
                'details' => function ($q) use ($detailsSelect) {
                    $q->select($detailsSelect);
                },
                'billSundries:id,purchase_invoice_id,sundry_id,name,code,bill_sundry_type,calculation_type,apply_on,base_amount,rate_percent,value,amount,affect_net_total',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.destination:id,name',
                // 'details.condition:id,name',
                // 'details.item.taxCategory:id,name,cgst,sgst,igst'
                'reference'
            ])
            ->where([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])

            // Safe filtering using null coalescing
            ->when(!empty($filters['voucher_id']), fn($q) => $q->where('voucher_id', $filters['voucher_id']))
            ->when(!empty($filters['grn_serial']), fn($q) => $q->where('grn_serial', $filters['grn_serial']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['sales_invoice_serial']), fn($q) => $q->where('sales_invoice_serial', $filters['sales_invoice_serial']))
            ->when(!empty($filters['reference_number']), fn($q) => $q->where('reference_number', $filters['reference_number']))
            ->when(!empty($filters['voucher_serial']), fn($q) => $q->where('id', $filters['voucher_serial']))
            // payment_status filtered below via references relation
            ->when(!empty($filters['file_no']), fn($q) => $q->where('file_number', $filters['file_no']))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['start_date'])->startOfDay();
                $endDate   = Carbon::parse($filters['end_date'])->endOfDay();
                $query->whereBetween('invoice_date', [$startDate, $endDate]);
            })

            ->when(
                !empty($filters['item_id']),
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )

            ->when(
                !empty($filters['condition_id']),
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id']))
            )

            ->when(
                !empty($filters['destination_id']),
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id']))
            )

            ->when(!empty($filters['payment_status']) && $filters['payment_status'] !== 'all', function ($q) use ($filters) {
                if ($filters['payment_status'] === 'unpaid') {
                    return $q->whereHas('reference', fn($r) => $r->where('is_closed', false));
                } elseif ($filters['payment_status'] === 'partially_paid') {
                    return $q->whereHas('reference', fn($r) => $r->where('pending_amount', '>', 0)->where('settled_amount', '>', 0));
                } elseif ($filters['payment_status'] === 'fully_paid') {
                    return $q->whereHas('reference', fn($r) => $r->where('is_closed', true));
                } elseif ($filters['payment_status'] === 'overpaid') {
                    return $q->whereHas('reference', fn($r) => $r->where('pending_amount', '<', 0));
                }
            })

            ->orderBy('voucher_id', 'desc')

            ->select($mainSelect);
    }

    public function getPurchaseInvoiceSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId])->orderBy('id', 'desc')->get(['invoice_serial', 'id', 'voucher_id']);
    }

    public function getViewData(int $purchaseInvoiceId, int $companyId, int $financialYearId): ?PurchaseInvoice
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $purchaseInvoiceId)
            ->with([
                'creator:id,name',
                'updater:id,name',
                'account:id,city,name',
                'broker:id,city,name',
                'purchaseType:id,name',
                'billSundries:id,purchase_invoice_id,sundry_id,name,code,bill_sundry_type,calculation_type,apply_on,base_amount,rate_percent,value,amount,affect_net_total',
                'details:id,purchase_invoice_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,purchase_order_id,purchase_order_item_id,purchase_order_serial',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',
                'reference',
                'reference.allocations',
                'reference.allocations.voucher:id,voucher_serial,voucher_date',
                'reference.allocations.voucher.paymentVoucher:id,voucher_id,payment_id',
                'reference.allocations.voucher.paymentVoucher.payment:id,cheque_number'
            ])
            ->select([
                'id',
                'invoice_serial',
                'invoice_number',
                'invoice_date',
                'due_date',
                'show_date',
                'party_bill_date',
                'grn_id',
                'grn_number',
                'grn_serial',
                'purchase_type_id',
                'account_id',
                'broker_id',
                'gst_type',
                'file_number',
                'sales_invoice_serial',
                'reference_number',
                'vehicle_number',
                'remarks',
                'total_quantity',
                'taxable_amount',
                'tax_amount',
                'net_amount',
                'grand_total',
                'paid_amount',
                'payment_status',
                'invoice_status',
                'voucher_id', 
                'created_by', 
                'updated_by'
            ])
            ->first();
    }

    public function findByReferenceNumberAndAccount(array $filters, ?int $excludeId = null): ?PurchaseInvoice
    {
        return $this->model
            ->where($filters)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    public function getInvoiceSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->where('company_id', $companyId)->where('financial_year_id', $financialYearId)->get(['invoice_serial', 'id']);
    }

    public function getGrnSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->where('company_id', $companyId)->where('financial_year_id', $financialYearId)->get(['grn_serial', 'id', 'voucher_id']);
    }

    public function getEditData(int $invoiceId, int $companyId, int $financialYearId): ?PurchaseInvoice
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $invoiceId)
            ->with([
                'billSundries',
                'purchaseType',
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'details:id,purchase_invoice_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,purchase_order_id,purchase_order_item_id,purchase_order_serial',
                'billSundries.crAccount:id,name',
                'billSundries.drAccount:id,name',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'invoice_serial', 'invoice_date', 'reference_number', 'account_id', 'broker_id',  'total_quantity', 'taxable_amount as total_amount', 'gst_type', 'vehicle_number', 'remarks', 'file_number', 'sales_invoice_serial', 'purchase_type_id', 'net_amount as net_total', 'grand_total as gross_total', 'party_bill_date', 'grn_id', 'grn_serial', 'grn_number'])
            ->first();
    }
}

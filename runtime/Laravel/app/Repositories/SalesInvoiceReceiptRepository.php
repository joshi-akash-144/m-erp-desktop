<?php

namespace App\Repositories;

use App\Models\SalesInvoiceReceipt;

class SalesInvoiceReceiptRepository extends BaseRepository
{
    public function __construct(SalesInvoiceReceipt $salesInvoiceReceipt)
    {
        parent::__construct($salesInvoiceReceipt);
    }

    /**
     * Insert one receipt row per invoice id.
     */
    public function createMany(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $this->model->newQuery()->insert($rows);
    }

    /**
     * Of the given invoice ids, return the ones that already have a receipt for this company.
     */
    public function existingInvoiceIds(int $companyId, array $invoiceIds): array
    {
        if (empty($invoiceIds)) {
            return [];
        }

        return $this->model->query()
            ->where('company_id', $companyId)
            ->whereIn('sales_invoice_id', $invoiceIds)
            ->lockForUpdate()
            ->pluck('sales_invoice_id')
            ->all();
    }

    /**
     * Build the base query for "which bill was settled on which day, by whom" report.
     */
    public function reportQuery(int $companyId, array $filters = [])
    {
        return $this->model->query()
            ->select([
                'sales_invoice_receipts.id',
                'sales_invoice_receipts.sales_invoice_id',
                'sales_invoice_receipts.customer_id',
                'sales_invoice_receipts.received_by',
                'sales_invoice_receipts.received_at'
            ])
            ->join('sales_invoices', 'sales_invoice_receipts.sales_invoice_id', '=', 'sales_invoices.id')
            ->with([
            'salesInvoice',
            'salesInvoice.salesOrder.broker',
            'salesInvoice.salesOrder.details:id,sales_order_id,ordered_qty,received_qty',
            'salesInvoice.details.destination',
            'salesInvoice.details.item',
                'customer:id,name',
                'receiver:id,name',
            ])
            ->where('sales_invoice_receipts.company_id', $companyId)
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('sales_invoice_receipts.customer_id', $filters['customer_id']))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), fn($q) => $q->whereBetween('sales_invoices.invoice_date', [
                $filters['start_date'],
                $filters['end_date'],
            ]))
            ->orderBy('sales_invoices.sales_order_id', 'desc')
            ->orderBy('sales_invoice_receipts.received_at', 'desc');
    }
}

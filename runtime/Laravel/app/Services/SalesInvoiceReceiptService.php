<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReceipt;
use App\Repositories\SalesInvoiceReceiptRepository;
use App\Repositories\SalesInvoiceRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesInvoiceReceiptService
{
    protected SalesInvoiceRepository $salesInvoiceRepository;
    protected SalesInvoiceReceiptRepository $salesInvoiceReceiptRepository;

    public function __construct(
        SalesInvoiceRepository $salesInvoiceRepository,
        SalesInvoiceReceiptRepository $salesInvoiceReceiptRepository
    ) {
        $this->salesInvoiceRepository = $salesInvoiceRepository;
        $this->salesInvoiceReceiptRepository = $salesInvoiceReceiptRepository;
    }

    /**
     * List sales invoices for a customer that are not yet marked as received.
     */
    public function pendingInvoices(int $companyId, int $financialYearId, array $filters): array
    {
        [$paginator, $rowCount] = $this->salesInvoiceRepository->getPendingForReceipt(
            $companyId,
            $financialYearId,
            (int) $filters['customer_id'],
            $filters
        );

        $lastOrderId = null;

        $data = collect($paginator->items())->map(function (SalesInvoice $invoice) use (&$lastOrderId) {
            $order = $invoice->salesOrder;
            $soDate = $order?->created_at ? $order->created_at->format('d.m.Y') : '-';
            $invDate = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->format('d.m.Y') : '-';

            $currentOrderId = $order?->id ?? 'no-order-' . $invoice->id;
            $isRepeated = ($currentOrderId === $lastOrderId);
            $lastOrderId = $currentOrderId;

            $due_date = $order?->due_date ? Carbon::parse($order->due_date) : ($order?->created_at ? $order->created_at->copy()->addDays($order->delivery_days ?? 0) : null);
            $rem_days = $due_date ? (int) now()->startOfDay()->diffInDays($due_date->startOfDay(), false) : '-';
            $po_status = $order?->order_status ? ucfirst($order->order_status) : '-';

            return [
                'id'               => $invoice->id,
                'so_date'          => $isRepeated ? '' : $soDate,
                'dalal'            => $isRepeated ? '' : ($order?->broker?->name ?? 'SELF'),
                'po_no'            => $isRepeated ? '' : ($order?->purchase_order_number ?? '-'),
                'delivery'         => $isRepeated ? '' : ($invoice->details->pluck('destination.name')->filter()->unique()->implode(', ') ?: '-'),
                'item'             => $isRepeated ? '' : ($invoice->details->pluck('item.name')->filter()->unique()->implode(', ') ?: '-'),
                'rate'             => $isRepeated ? '' : ($invoice->details->pluck('rate')->filter()->unique()->map(fn ($r) => (float) $r)->implode(', ') ?: '-'),
                'days'             => $isRepeated ? '' : ($order?->delivery_days ?? '-'),
                'qty'              => $isRepeated ? '' : ($order?->total_quantity ? (float) $order->total_quantity : '-'),
                
                'inv_date'         => $invDate,
                'truck_no'         => $invoice->vehicle_number ?? '-',
                'bags'             => $invoice->details->sum('bag_count') ?: 0,
                'wt'               => $invoice->details->sum('quantity') ?: 0,
                'inv_no'           => $invoice->invoice_serial ?? '-',
                
                'rem_qty'          => $isRepeated ? '' : ($order?->details ? (float) $order->details->sum('remaining_qty') : '-'),
                'rem_days'         => $isRepeated ? '' : $rem_days,
                'po_status'        => $isRepeated ? '' : $po_status,
                
                // Original fields if needed by UI
                'invoice_no'       => $invoice->invoice_serial,
                'amount'           => (float) $invoice->net_amount,
                'raw_po_no'        => $order?->purchase_order_number ?? '-',
                'raw_so_id'        => $currentOrderId,
            ];
        });

        return [
            'data'         => $data->values()->all(),
            'total'        => $rowCount,
            'last_page'    => $paginator->lastPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Mark the given sales invoices as received for a customer.
     *
     * @return array{saved:int, skipped:int}
     */
    public function storeReceipts(int $companyId, int $customerId, array $invoiceIds, int $receivedBy): array
    {
        return DB::transaction(function () use ($companyId, $customerId, $invoiceIds, $receivedBy) {
            // Keep only invoices that actually belong to this company + customer.
            $validIds = $this->salesInvoiceRepository->findIdsForCustomer($companyId, $customerId, $invoiceIds);

            // Drop any that already have a receipt (duplicate-submission guard).
            $alreadyReceived = $this->salesInvoiceReceiptRepository->existingInvoiceIds($companyId, $validIds);
            $toInsert = array_diff($validIds, $alreadyReceived);

            $now = Carbon::now();
            $rows = array_map(fn (int $invoiceId) => [
                'company_id'       => $companyId,
                'customer_id'      => $customerId,
                'sales_invoice_id' => $invoiceId,
                'received_by'      => $receivedBy,
                'received_at'      => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ], $toInsert);

            $this->salesInvoiceReceiptRepository->createMany($rows);

            return [
                'saved'   => count($rows),
                'skipped' => count($invoiceIds) - count($rows),
            ];
        });
    }

    /**
     * Paginated "which bill settled on which day, by whom" report for the grid.
     */
    public function receiptReport(int $companyId, array $filters): array
    {
        $paginator = $this->salesInvoiceReceiptRepository->reportQuery($companyId, $filters)
            ->paginate(
                $filters['size'] ?? 50,
                ['*'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data'         => $this->mapReportRows($paginator->getCollection())->all(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Unpaginated report rows for print/export.
     */
    public function receiptReportAll(int $companyId, array $filters): array
    {
        $rows = $this->salesInvoiceReceiptRepository->reportQuery($companyId, $filters)->get();

        return $this->mapReportRows($rows)->all();
    }

    private function mapReportRows(iterable $receipts): \Illuminate\Support\Collection
    {
        $lastOrderId = null;

        return collect($receipts)->map(function (SalesInvoiceReceipt $receipt) use (&$lastOrderId) {
            $invoice = $receipt->salesInvoice;
            $order = $invoice?->salesOrder;
            
            $soDate = $order?->created_at ? $order->created_at->format('d.m.Y') : '-';
            $invDate = $invoice?->invoice_date ? Carbon::parse($invoice->invoice_date)->format('d.m.Y') : '-';

            $currentOrderId = $order?->id ?? 'no-order-' . $invoice?->id;
            $isRepeated = ($currentOrderId === $lastOrderId);
            $lastOrderId = $currentOrderId;

            $due_date = $order?->due_date ? Carbon::parse($order->due_date) : ($order?->created_at ? $order->created_at->copy()->addDays($order->delivery_days ?? 0) : null);
            $rem_days = $due_date ? (int) now()->startOfDay()->diffInDays($due_date->startOfDay(), false) : '-';
            $po_status = $order?->order_status ? ucfirst($order->order_status) : '-';

            return [
                'id'               => $receipt->id,
                'so_date'          => $isRepeated ? '' : $soDate,
                'dalal'            => $isRepeated ? '' : ($order?->broker?->name ?? 'SELF'),
                'po_no'            => $isRepeated ? '' : ($order?->purchase_order_number ?? '-'),
                'delivery'         => $isRepeated ? '' : ($invoice?->details->pluck('destination.name')->filter()->unique()->implode(', ') ?: '-'),
                'item'             => $isRepeated ? '' : ($invoice?->details->pluck('item.name')->filter()->unique()->implode(', ') ?: '-'),
                'rate'             => $isRepeated ? '' : ($invoice?->details->pluck('rate')->filter()->unique()->map(fn ($r) => (float) $r)->implode(', ') ?: '-'),
                'days'             => $isRepeated ? '' : ($order?->delivery_days ?? '-'),
                'qty'              => $isRepeated ? '' : ($order?->total_quantity ? (float) $order->total_quantity : '-'),
                
                'inv_date'         => $invDate,
                'truck_no'         => $invoice?->vehicle_number ?? '-',
                'bags'             => $invoice?->details->sum('bag_count') ?: 0,
                'wt'               => $invoice?->details->sum('quantity') ?: 0,
                'inv_no'           => $invoice?->invoice_serial ?? '-',

                'rem_qty'          => $isRepeated ? '' : ($order?->details ? (float) $order->details->sum('remaining_qty') : '-'),
                'rem_days'         => $isRepeated ? '' : $rem_days,
                'po_status'        => $isRepeated ? '' : $po_status,

                // Legacy fields for backward compatibility or other uses
                'raw_po_no'        => $order?->purchase_order_number ?? '-',
                'raw_so_id'        => $currentOrderId,
                'invoice_no'       => $invoice?->invoice_serial,
                'invoice_date'     => $invDate,
                'customer_name'    => $receipt->customer?->name,
                'amount'           => (float) ($invoice?->net_amount ?? 0),
                'received_date'    => optional($receipt->received_at)->format('d.m.Y'),
                'received_by_name' => $receipt->receiver?->name,
            ];
        });
    }
}

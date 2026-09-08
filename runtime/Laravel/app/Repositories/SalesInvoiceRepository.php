<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\SalesInvoice;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class SalesInvoiceRepository extends BaseRepository
{
    public function __construct(SalesInvoice $salesInvoice)
    {
        parent::__construct($salesInvoice);
    }

    public function getNextVoucherSerial(int $companyId, int $financialYearId): int
    {
        $lastInvoice = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('invoice_serial')
            ->first();

        return $lastInvoice ? $lastInvoice->invoice_serial : 0;
    }

    /**
     * Fetch pending sales invoices.
     *
     * @param int $companyId The company ID to fetch invoices for.
     * @param int|null $financialYearId The financial year ID to fetch invoices for (optional).
     * @param int|null $accountId The account ID to fetch invoices for (optional).
     * @param int|null $brokerId The broker ID to fetch invoices for (optional).
     * @param int|null $itemId The item ID to fetch invoices for (optional).
     * @return Collection The collection of pending sales invoices.
     */
    public function fetchPendingSalesInvoices(
        int $companyId,
        ?int $financialYearId = null,
        ?array $filters = [],
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ): Collection {
        return $this->query()
            ->select($mainSelect)
            ->with([
                'details' => function ($q) use ($detailsSelect) {
                    if ($detailsSelect !== ['*']) {
                        $q->select($detailsSelect)->with('item:id,name')->with('condition:id,name');
                    }
                },
                'account:id,name,city',
                'broker:id,name,city',
            ])
            ->where('company_id', $companyId)
            ->when($financialYearId, fn($q) => $q->where('financial_year_id', $financialYearId))
            ->when($filters['account_id'] ?? null, fn($q) => $q->where('account_id', $filters['account_id']))
            ->when($filters['broker_id'] ?? null, fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(
                $filters['item_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->where('order_status', 'open')
            ->get();
    }

    public function fetchSalesInvoiceDetails(
        int $invoiceId,
        array $detailIds,
        ?array $filters = [],
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ): Collection {
        return $this->query()
            ->where('id', $invoiceId)
            ->select($mainSelect)
            ->with([
                'details' => function ($q) use ($detailsSelect, $detailIds) {
                    if ($detailsSelect !== ['*']) {
                        $q->select($detailsSelect)->with('item:id,name')->whereIn('id', $detailIds);
                    }
                },
                'account:id,name,city',
                'broker:id,name,city',
            ])
            ->when($filters['account_id'] ?? null, fn($q) => $q->where('account_id', $filters['account_id']))
            ->when($filters['broker_id'] ?? null, fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(
                $filters['item_id'] ?? null,
                fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->where('order_status', 'open')
            ->get();
        return $this->model->select('id', 'order_number')->get();
    }

    public function updateSalesInvoiceStatus(array $invoiceIds, ?string $status = null): void
    {
        foreach ($invoiceIds as $poId) {

            $po = $this->model->find($poId);

            $allClosed = $po->details()->where('is_closed', false)->doesntExist();

            if ($allClosed) {
                $po->update(['order_status' => SalesInvoice::STATUS_CLOSE]);
            }
        }
    }

    public function getEditData(int $salesInvoiceId, int $companyId, int $financialYearId): ?SalesInvoice
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $salesInvoiceId)
            ->with([
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'saleType:id,name,region,cgst,sgst,igst',

                'details:id,sales_invoice_id,item_id,destination_id,condition_id,taxable_amount,cgst_rate,sgst_rate,igst_rate,cgst_amount,sgst_amount,igst_amount,tax_amount,bag_count,party_quantity,quantity,inclusive_rate,rate,amount,net_amount,grand_total',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',

                'details.item.saleTypeLocal:id,name,region,cgst,sgst,igst',
                'details.item.saleTypeInterstate:id,name,region,cgst,sgst,igst',

                'salesOrder:id,order_number',
                'salesOrder.details:id,sales_order_id,destination_id',
                'salesOrder.details.destination:id,name',

                'billSundries',
                'billSundries.crAccount:id,name',
                'billSundries.drAccount:id,name',
            ])
            ->select(['id', 'invoice_serial', 'invoice_date', 'reference_number', 'delivery_challan_number', 'sales_order_id', 'sales_order_serial', 'grn_number', 'delivery_date', 'last_invoice_date', 'account_id', 'broker_id', 'kms', 'vehicle_number', 'party_bill_date', 'sale_type_id', 'delivery_date', 'ewaybill_number', 'total_quantity', 'net_amount as total_amount', 'grand_total as gross_total',  'gst_type', 'remarks'])
            ->first();
    }
    public function getViewData(int $salesInvoiceId, int $companyId, int $financialYearId): ?SalesInvoice
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $salesInvoiceId)
            ->with([
                'creator:id,name',
                'updater:id,name',
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'saleType:id,name',

                'reference',
                'reference.allocations',
                'reference.allocations.voucher:id,voucher_serial,voucher_date',

                'details:id,sales_invoice_id,item_id,destination_id,condition_id,taxable_amount,cgst_rate,sgst_rate,igst_rate,cgst_amount,sgst_amount,igst_amount,tax_amount,bag_count,party_quantity,quantity,inclusive_rate,rate,amount,net_amount,grand_total',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst',

                'details.item.saleTypeLocal:id,name,region,cgst,sgst,igst',
                'details.item.saleTypeInterstate:id,name,region,cgst,sgst,igst',

                'salesOrder:id,order_number,purchase_order_number',
                'salesOrder.details:id,sales_order_id,destination_id',
                'salesOrder.details.destination:id,name',

                'billSundries',
                'billSundries.crAccount:id,name',
                'billSundries.drAccount:id,name',

                'eWayBill:id,ewb_no,sales_invoice_id',
                'eInvoice:id,sales_invoice_id,irn,ack_no,ack_dt,signed_qr_code'
            ])
            ->select(['id', 'invoice_serial', 'invoice_date', 'delivery_challan_number', 'sales_order_id', 'sales_order_serial', 'grn_number', 'delivery_date', 'last_invoice_date', 'account_id', 'broker_id', 'kms', 'vehicle_number', 'party_bill_date', 'sale_type_id', 'delivery_date', 'ewaybill_number', 'total_quantity', 'net_amount as total_amount', 'net_amount', 'grand_total', 'gst_type', 'remarks', 'voucher_id', 'created_by', 'updated_by'])->first();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = [])
    {
        // $mainSelect = ['id', 'invoice_serial', 'account_id', 'broker_id', 'delivery_date', 'status', 'updated_by', 'created_by', 'gst_type', 'remarks'];

        $mainSelect = ['id', 'invoice_serial', 'invoice_number', 'invoice_date', 'sales_order_id', 'reference_number', 'account_id', 'sales_order_serial',  'grn_number', 'kms', 'vehicle_number', 'payment_received_status', 'net_amount', 'delivery_date', 'status', 'updated_by', 'created_by', 'gst_type', 'remarks'];

        $detailsSelect = ['sales_invoice_id', 'item_id', 'destination_id', 'rate', 'inclusive_rate', 'condition_id' ,'taxable_amount', 'cgst_rate',	'sgst_rate', 'igst_rate', 'cgst_amount', 'sgst_amount',	'igst_amount',	'tax_amount', 'bag_count', 'party_quantity', 'quantity', 'amount', 'grand_total', 'remarks',];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect)->orderByDesc('invoice_serial');
        
        $rowCount = $query->count(); // for 150 check
    
        // return $query->simplePaginate(
        //     $filters['size'],
        //     ['*'],
        //     'page',
        //     $filters['page']
        // );
    
        $paginator = $query->simplePaginate(
            $filters['size'] ?? 10,
            ['*'],
            'page',
            $filters['page'] ?? 1,);

        return [$paginator, $rowCount];
    }

    public function listAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)
            ->orderBy('invoice_serial', 'asc')
            ->get();
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
        array $filters,
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ) {
        return $this->model->query()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'account:id,name,city',
                'broker:id,name,city',
                'salesOrder:id,purchase_order_number', 
                'details' => function ($q) use ($detailsSelect) {
                    $q->select($detailsSelect);
                },
                'details.item:id,name,unit_id',
                'details.unit:id,name',
                'details.condition:id,name',               
            ])
            ->where([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->when(!empty($filters['so_id']), fn($q) => $q->where('id', $filters['so_id']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['condition_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id'])))            
            ->when(($filters['payment_status'] ?? 'all') !== 'all', fn($q) => $q->where('payment_received_status', $filters['payment_status']))
            ->when(!empty($filters['grn_number']), fn($q) => $q->where('grn_number', $filters['grn_number']))
            ->when(!empty($filters['vehicle_number']), fn($q) => $q->where('vehicle_number', $filters['vehicle_number']))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->format('Y-m-d');
                $end   = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->format('Y-m-d');
                $q->whereBetween('invoice_date', [$start, $end]);
            })
            ->when(!empty($filters['op_numbers']), function ($q) use ($filters) {
                    $q->whereHas('salesOrder', fn($sq) => $sq->where('purchase_order_number', $filters['op_numbers']));
            })
             // 2. Bill Number Range via SELECT BOXES
            ->when(!empty($filters['bill_from']), function ($q) use ($filters) {
                if (!empty($filters['bill_to'])) {
                    $q->whereBetween('id', [$filters['bill_from'], $filters['bill_to']]);
                } else {
                    $q->where('id', $filters['bill_from']);
                }
            })
            ->select($mainSelect);
    }

    public function deleteDetails(SalesInvoice $so): void
    {
        $so->details()->delete();
    }

    public function getReceivedQtyItemWise(SalesInvoice $so): array
    {
        return $so->details()->pluck('received_qty', 'item_id')->toArray();
    }
    public function createDetails(SalesInvoice $so, array $items): void
    {
        foreach ($items as $item) {
            $so->details()->create($item);
        }
    }
    public function isInvoiceOpen(SalesInvoice $so): bool
    {
        return $so->details()->whereColumn('ordered_qty', '>', 'received_qty')->exists();
    }

    public function getInvoiceSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId])->orderBy('id', 'asc')->select('id', 'invoice_serial')->get();
    }

    public function getGrnNumber(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId])->orderBy('id', 'asc')->select('grn_number', 'id')->get();
    }

    public function getPoNumbers(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()
            ->where(['company_id' => $companyId, 'financial_year_id' => $financialYearId])
            ->whereHas('salesOrder')
            ->with('salesOrder:id,purchase_order_number')
            ->get();
            // ->pluck('salesOrder.purchase_order_number', 'sales_order_id');
            // ->filter()
            // ->unique();
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

    public function findByReferenceNumberAndAccount(array $filters): ?SalesInvoice
    {
        return $this->model
            ->where($filters)
            ->first();
    }


    public function getInvoicePrintDetails(int|array|string|null $salesInvoiceId, int $companyId, int $financialYearId, array $filters = []): Collection
    {
        return $this->model->query()
            ->select(['id', 'invoice_serial', 'invoice_date', 'grn_number', 'account_id', 'vehicle_number',  'delivery_date', 'total_quantity', 'net_amount',  'remarks', 'company_id', 'invoice_number', 'remarks', 'sales_order_id'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->when(!empty($salesInvoiceId), function ($q) use ($salesInvoiceId) {
                if (is_array($salesInvoiceId)) {
                    $q->whereIn('id', $salesInvoiceId);
                } else {
                    $q->where('id', $salesInvoiceId);
                }
            })
            ->when(!empty($filters['so_id']), fn($q) => $q->where('id', $filters['so_id']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(!empty($filters['item_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id'])))
            ->when(!empty($filters['condition_id']), fn($q) => $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id'])))            
            ->when(($filters['payment_status'] ?? 'all') !== 'all', fn($q) => $q->where('payment_received_status', $filters['payment_status']))
            ->when(!empty($filters['grn_number']), fn($q) => $q->where('grn_number', $filters['grn_number']))
            ->when(!empty($filters['vehicle_number']), fn($q) => $q->where('vehicle_number', $filters['vehicle_number']))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->format('Y-m-d');
                $end   = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->format('Y-m-d');
                $q->whereBetween('invoice_date', [$start, $end]);
            })
            ->when(!empty($filters['op_numbers']), function ($q) use ($filters) {
                $q->whereHas('salesOrder', fn($sq) => $sq->where('purchase_order_number', $filters['op_numbers']));
            })
            ->when(!empty($filters['bill_from']), function ($q) use ($filters) {
                if (!empty($filters['bill_to'])) {
                    $q->whereBetween('id', [$filters['bill_from'], $filters['bill_to']]);
                } else {
                    $q->where('id', $filters['bill_from']);
                }
            })
            ->with([
                'account:id,city,name,address_one,address_two,mobile_number,postal_code,state_id',
                'account.state:id,name,gst_code',
                'account.preference:account_id,distance',
                'account.taxDetail:account_id,gst_number',

                'details:id,sales_invoice_id,item_id,destination_id,quantity,party_quantity,bag_count,inclusive_rate,rate,amount,net_amount',

                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.destination:id,name',

                'salesOrder:id,order_number,purchase_order_number',

                'billSundries:sales_invoice_id,bill_sundry_type,calculation_type,amount,name,rate_percent,code',

                'eWayBill:id,ewb_no,sales_invoice_id',
                'eInvoice:id,sales_invoice_id,irn,ack_no,ack_dt,signed_qr_code'
            ])

            ->get();
    }

    /**
     * Fetch sales invoices for a customer that have not yet been marked as received.
     */
    public function getPendingForReceipt(int $companyId, int $financialYearId, int $customerId, array $filters = [])
    {
        $paginator = $this->model->query()
            ->select(['id', 'invoice_serial', 'invoice_date', 'sales_order_id', 'account_id', 'net_amount', 'vehicle_number'])
            ->with([
                'account:id,name',
                'salesOrder:id,order_number,purchase_order_number,created_at,delivery_days,total_quantity,broker_id,due_date,order_status',
                'salesOrder.broker:id,name',
                'salesOrder.details:id,sales_order_id,ordered_qty,received_qty',
                'details:id,sales_invoice_id,item_id,destination_id,rate,bag_count,quantity',
                'details.item:id,name',
                'details.destination:id,name',
            ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('account_id', $customerId)
            ->whereDoesntHave('receipt')
            ->when(!empty($filters['destination_id']), fn ($q) => $q->whereHas(
                'details',
                fn ($d) => $d->where('destination_id', $filters['destination_id'])
            ))
            ->when(!empty($filters['item_id']), fn ($q) => $q->whereHas(
                'details',
                fn ($d) => $d->where('item_id', $filters['item_id'])
            ))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), fn ($q) => $q->whereBetween('invoice_date', [
                $filters['start_date'],
                $filters['end_date'],
            ]))
            ->orderBy('sales_order_id', 'DESC')
            ->orderBy('invoice_date', 'ASC')
            ->paginate(
                $filters['size'] ?? 50,
                ['*'],
                'page',
                $filters['page'] ?? 1,
            );

        return [$paginator, $paginator->total()];
    }

    /**
     * Return the ids (subset of $invoiceIds) that belong to the given company + customer.
     */
    public function findIdsForCustomer(int $companyId, int $customerId, array $invoiceIds): array
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('account_id', $customerId)
            ->whereIn('id', $invoiceIds)
            ->pluck('id')
            ->all();
    }

}

// ewaybill_number
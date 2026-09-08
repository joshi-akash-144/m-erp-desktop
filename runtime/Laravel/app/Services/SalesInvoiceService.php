<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Helpers\TaxHelper;
use App\Models\BillSundry;
use App\Models\Company;
use App\Models\EWayBill;
use App\Models\Reference;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\VoucherType;
use App\Repositories\BillSundryRepository;
use App\Models\SalesInvoiceItem;
use App\Repositories\SalesInvoiceRepository;
use App\Repositories\SalesOrderRepository;
use Carbon\Carbon;
use F9WebLtd\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SalesInvoiceService
{
    protected VoucherService $voucherService;
    protected SalesInvoiceRepository $salesInvoiceRepository;
    protected SalesOrderRepository $salesOrderRepo;
    protected LookupService $lookupService;
    protected BillSundryRepository $billSundryRepo;
    protected StockVoucherService $stockVoucherService;
    protected ReferenceService $referenceService;
    protected SalesOrderService $salesOrderService;
    protected GstEntryService $gstEntryService;
    protected TdsEntryService $tdsEntryService;
    protected EInvoiceService $eInvoiceService;
    protected AuditService $auditService;

    public function __construct(VoucherService $voucherService, SalesInvoiceRepository $salesInvoiceRepository, LookupService $lookupService, SalesOrderRepository $salesOrderRepo, BillSundryRepository $billSundryRepo, StockVoucherService $stockVoucherService, ReferenceService $referenceService, SalesOrderService $salesOrderService, GstEntryService $gstEntryService, TdsEntryService $tdsEntryService, AuditService $auditService)
    {
        $this->voucherService = $voucherService;
        $this->lookupService = $lookupService;
        $this->salesInvoiceRepository = $salesInvoiceRepository;
        $this->salesOrderRepo = $salesOrderRepo;
        $this->billSundryRepo = $billSundryRepo;
        $this->stockVoucherService = $stockVoucherService;
        $this->referenceService = $referenceService;
        $this->salesOrderService = $salesOrderService;
        $this->gstEntryService = $gstEntryService;
        $this->tdsEntryService = $tdsEntryService;
        $this->auditService = $auditService;
    }

    public function createSalesInvoice(array $data, int $companyId, int $financialYearId)
    {
        // dd('createSalesInvoice', $data);
        DB::beginTransaction();
        try {
            // Idempotency check — prevent duplicate Sales Invoice submission with the same UUID
            if (isset($data['uuid']) && SalesInvoice::where('uuid', $data['uuid'])->exists()) {
                throw new \Exception("This Sales Invoice already exists. Please refresh and try again.");
            }

            [$masterData, $items, $billSundry] = $this->prepareInvoiceData($data, $companyId, $financialYearId);
            $isCreateEWayBill = false;
            if(!empty($masterData['ewaybill_number']) && trim($masterData['ewaybill_number']) !== ''){
                // check eWaybill number must 12 digits
                if(strlen(trim($masterData['ewaybill_number'])) !== 12){
                    throw ValidationException::withMessages([
                        'ewaybill_number' => 'Ewaybill number must be 12 digits.'
                    ]);
                }
                $isCreateEWayBill = true;
            }

            $salesInvoice = $this->salesInvoiceRepository->create($masterData);
            if($isCreateEWayBill){
                EWayBill::create([
                    'company_id' => $companyId,
                    'sales_invoice_id' => $salesInvoice->id,
                    'ewb_no' => $masterData['ewaybill_number'],
                    'ewb_date' => Carbon::now(),
                    'valid_upto' => Carbon::now ()->addDays(7),
                    'status' => EWayBill::STATUS_ACTIVE,
                    'created_by' => current_user_id(),
                ]);
            }

            $salesInvoice->details()->createMany($items);
            if (!empty($billSundry)) {
                $salesInvoice->billSundries()->createMany($billSundry);
            }

            if (isset($masterData['sales_order_id']) && $masterData['sales_order_id'] > 0) {
                $salesOrder = $this->salesOrderRepo->find($masterData['sales_order_id']);

                if (!$salesOrder) {
                    throw new \Exception("Sales order not found");
                }

                $salesOrderDetails = $salesOrder->details()->where('sales_order_id', $masterData['sales_order_id'])->get();
                foreach ($salesOrderDetails as $key => $item) {
                    $item->received_qty += $masterData['total_quantity'];
                    $item->save();
                }
                $remaining_qty = $salesOrderDetails->sum('remaining_qty');
                if ($remaining_qty <= 0) {
                    $salesOrder->order_status = SalesOrder::STATUS_CLOSE;
                } else {
                    $salesOrder->order_status = SalesOrder::STATUS_OPEN;
                }
                $salesOrder->save();
            }

            $voucherMaster = $this->prepareVoucherMaster($salesInvoice);
            
            $voucherLines = $this->prepareVoucherLinesForSale($companyId, $financialYearId, $masterData, $items, $billSundry);
            
            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);
            $salesInvoice->update([
                'voucher_id' => $voucher->id,
            ]);

            $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
            if ($accountDetail) {
                if ($accountDetail['is_billwise']) {
                    $referenceData = $this->prepareBillWiseReference($salesInvoice, $voucher);
                    $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
                }
            }
            $salesInvoice->load('details', 'billSundries');
            $this->createOrUpdateStockVoucher($companyId, $salesInvoice, $voucher);
            $this->gstEntryService->storeForSalesInvoice($salesInvoice, $voucher);
            // $this->tdsEntryService->storeForSalesInvoice($salesInvoice, $voucher);

            $this->logAudit([], $salesInvoice, $voucher->toArray(), $voucherLines, \App\Models\AuditTrail::ACTION_CREATE);
            // session(['file_number' => $salesInvoice->file_number]);
            DB::commit();
            return $salesInvoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function prepareVoucherLinesForSale(
        $companyId,
        $financialYearId,
        $masterData,
        $items,
        $billSundry
    ) {
        // dd($billSundry);
        $lines = [
            'sales'          => [],
            'party'          => [],
            'over_and_above' => [],
        ];

        /** --------------------------------------------
         * 1. Base Amounts
         * SALE RULE:
         * Customer (Party) → DR
         * Sales → CR
         * -------------------------------------------- */
        $salesAmount = array_sum(array_column($items, 'amount'));
        $partyAmount = $salesAmount;

        /** --------------------------------------------
         * 2. Bill Sundry Master
         * -------------------------------------------- */
        $billSundryIds = array_column($billSundry, 'sundry_id');
        $billSundryRecords = $this->billSundryRepo->query()
            ->whereIn('id', $billSundryIds)
            ->get()
            ->keyBy('id');

        /** --------------------------------------------
         * 3. Sales Ledger
         * -------------------------------------------- */
        $saleTypeDetail = $this->lookupService->getSaleTypeDetails(
            $companyId,
            $masterData['sale_type_id']
        );
        // dd($saleTypeDetail);

        /** --------------------------------------------
         * Party detection (ONLY actual party)
         * -------------------------------------------- */
        $isPartyLedger = function ($accountId) use ($masterData) {
            return (int) $accountId === (int) $masterData['account_id'];
        };

        /** --------------------------------------------
         * Push Voucher Line
         * -------------------------------------------- */
        $pushLine = function (
            &$group,
            $accountId,
            $debit,
            $credit,
            $againstAccountId = null
        ) use ($isPartyLedger) {
            $group[] = [
                'account_id'         => $accountId,
                'against_account_id' => $againstAccountId,
                'debit'              => $debit,
                'credit'             => $credit,
                'is_party_account'   => $isPartyLedger($accountId),
            ];
        };


        /** --------------------------------------
         * 4. Bill Sundry Processing (SALE LOGIC)
         * ---------------------------------------- */
        foreach ($billSundry as $row) {
            $record = $billSundryRecords->get($row['sundry_id']);

            if (!$record) continue;

            $value = (float) $row['value'];
            if ($value == 0) continue;

            $isAdditive = $record->bill_sundry_type === BillSundry::ADDICTIVE
                || $record->bill_sundry_type === 'additive';

            $isSubtractive = $record->bill_sundry_type === BillSundry::SUBTRACTIVE
                || $record->bill_sundry_type === 'subtractive';

            $adjustInSales = (bool) $record->sale_adjust_in_amount;
            $adjustInParty = (bool) $record->sale_adjust_in_party_amount;
            $postOverAbove = (bool) $record->sale_post_over_and_above;

            $salesLedger = $record->sale_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->sale_account_id
                : ($row['bill_sundry_modal_dr_id'] ?? $saleTypeDetail['account_id']);

            $partyLedger = $record->sale_party_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->sale_party_account_id
                : ($row['bill_sundry_modal_cr_id'] ?? $masterData['account_id']);

            /** --------------------------------------
             * Over & Above
             * -------------------------------------------- */
            if ($postOverAbove) {
                if ($isSubtractive) {
                    // Example: TDS
                    //   DR, TDS Payable CR
                    $pushLine(
                        $lines['over_and_above'],
                        $partyLedger,
                        $value,
                        0,
                        $salesLedger
                    );
                    $pushLine(
                        $lines['over_and_above'],
                        $salesLedger,
                        0,
                        $value,
                        $partyLedger
                    );
                } else {
                    // Example: Freight / Labour / Tax
                    // Charge DR, Party CR
                    $pushLine(
                        $lines['over_and_above'],
                        $salesLedger,
                        $value,
                        0,
                        $partyLedger
                    );
                    $pushLine(
                        $lines['over_and_above'],
                        $partyLedger,
                        0,
                        $value,
                        $salesLedger
                    );
                }
                continue;
            }

            /** --------------------------------------------
             * Adjust in BOTH (no separate entry)
             * -------------------------------------------- */
            if ($adjustInSales && $adjustInParty) {
                $salesAmount += $isAdditive ? $value : -$value;
                $partyAmount += $isAdditive ? $value : -$value;
                continue;
            }

            /** ------------------------------
             * Adjust SALES Only
             * ------------------------------ */
            if ($adjustInSales && !$adjustInParty) {
                $salesAmount += $isAdditive ? $value : -$value;
                $accountName = $partyLedger ?? $masterData['account_id'];

                if ($isAdditive) {
                    // Charge DR
                    $pushLine($lines['party'], $accountName, $value, 0, $masterData['account_id']);
                } else {
                    // Discount CR
                    $pushLine($lines['sales'], $accountName, 0, $value, $masterData['account_id']);
                }
                continue;
            }

            /** ------------------------------
             * Adjust PARTY Only
             * ------------------------------ */
            if (!$adjustInSales && $adjustInParty) {
                $partyAmount += $isAdditive ? $value : -$value;
                $accountName = $salesLedger ?? $saleTypeDetail['account_id'];
                if ($isAdditive) {
                    // Party CR
                    $pushLine($lines['sales'], $accountName, 0, $value, $masterData['account_id']);
                } else {
                    // Party DR
                    $pushLine($lines['party'], $accountName, $value, 0, $masterData['account_id']);
                }
            }
        }

        /** --------------------------------------------
         * 5. Main Party Entry (Customer DR)
         * -------------------------------------------- */
        $lines['party'][] = [
            'account_id'         => $masterData['account_id'],
            'against_account_id' => $saleTypeDetail['account_id'],
            'debit'              => round($partyAmount, 2),
            'credit'             => 0,
            'is_party_account'   => true,
        ];

        /** --------------------------------------------
         * 6. Main Sales Entry (Sales CR)
         * -------------------------------------------- */
        $lines['sales'][] = [
            'account_id'         => $saleTypeDetail['account_id'],
            'against_account_id' => $masterData['account_id'],
            'debit'              => 0,
            'credit'             => round($salesAmount, 2),
            'is_party_account'   => false,
        ];

        // dd('lines', $lines);

        /** --------------------------------------------
         * 7. Validation
         * -------------------------------------------- */

        $totalDebit =
            array_sum(array_column($lines['party'], 'debit')) +
            array_sum(array_column($lines['over_and_above'], 'debit'));

        $totalCredit =
            array_sum(array_column($lines['sales'], 'credit')) +
            array_sum(array_column($lines['over_and_above'], 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception(
                "Debit and Credit mismatch: {$totalDebit} != {$totalCredit}"
            );
        }

        /** --------------------------------------------
         * 8. Flatten Lines
         * -------------------------------------------- */
        $flatLines = [];
        foreach ($lines as $group) {
            $flatLines = array_merge($flatLines, $group);
        }


        return $flatLines;
    }

    private function prepareVoucherMaster($invoice): array
    {
        return [
            'uuid'              => $invoice->uuid,
            'company_id'        => $invoice->company_id,
            'financial_year_id' => $invoice->financial_year_id,
            'voucher_date'      => $invoice->invoice_date,
            'voucher_type_id'   => VoucherType::SALE_INVOICE,
            'source_id'         => $invoice->id,
            'source_type'       => SourceType::SALES,
            'reference_number'  => $invoice->reference_number,
            'voucher_serial'    => $invoice->invoice_serial,
            'voucher_number'    => $invoice->invoice_number,
            'narration'         => $invoice->remarks,
        ];
    }

    private function createOrUpdateStockVoucher($companyId, $invoice, $voucher)
    {
        $stockTransactions = [];

        foreach ($invoice->details as $detail) {

            if ($detail->quantity <= 0) {
                continue;
            }

            $itemDetail = $this->lookupService->getItemDetails(
                $companyId,
                $detail->item_id
            );

            if (!$itemDetail['is_maintain_stock_balance']) {
                continue;
            }

            $stockTransactions[] = $this->prepareStockTransaction($detail);
        }

        $stockMaster = $this->prepareStockMaster($invoice, $voucher);

        $this->stockVoucherService->upsertStockVoucher(
            $stockMaster,
            $stockTransactions
        );
    }

    private function prepareStockTransaction($detail): array
    {
        return [
            'item_id' => $detail->item_id,
            'in_qty' => 0,
            'out_qty' => $detail->quantity,
            'rate' => $detail->rate,
            'amount' => $detail->amount,
        ];
    }

    private function prepareBillWiseReference($invoice, $voucher): array
    {
        return [
            'reference_number' => $invoice->reference_number,
            'reference_date' => $invoice->invoice_date,
            'parent_voucher_id' => $voucher->id,
            'reference_type' => 'new_ref',
            'amount' => $invoice->net_amount,
            'direction' => config('ref_direction_map.direction.' . SourceType::SALES),
            'account_id' => $invoice->account_id,
            'source_type' => SourceType::SALES,
            'source_id' => $invoice->id,
            'voucher_id' => $voucher->id,
            'pending_amount' => $invoice->net_amount,
            'file_number' => 0,
        ];
    }

    private function prepareStockMaster($invoice, $voucher): array
    {
        $voucherMaster = $this->prepareVoucherMaster($invoice);

        [
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_date'      => $voucherDate,
            'voucher_type_id'   => $voucherTypeId,
            'voucher_number'    => $voucherNumber,
            'voucher_serial'    => $voucherSerial,
            'reference_number'  => $referenceNumber,
        ] = $voucherMaster;

        return [
            'voucher_id'        => $voucher->id,
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_type_id'   => $voucherTypeId,
            'voucher_number'    => $voucherNumber,
            'voucher_serial'    => $voucherSerial,
            'reference_number'  => $referenceNumber,
            'voucher_date'      => $voucherDate,
        ];
    }

    public function updateSalesInvoice(int $invoiceId, array $data, int $companyId, int $financialYearId): ?SalesInvoice
    {
        DB::beginTransaction();
        try {
            $salesInvoice = $this->salesInvoiceRepository->find($invoiceId);
            if (!$salesInvoice) {
                throw new \Exception("Sales invoice not found");
            }

            $oldValues = $this->buildAuditValues($salesInvoice);

            [$masterData, $items, $billSundry] = $this->prepareInvoiceData($data, $companyId, $financialYearId, 'update');

            /** ---------------------------------
             *  Step 1: Capture OLD sales_order_id and old invoice qty
             *  MUST happen BEFORE update() overwrites sales_order_id on the model
             * --------------------------------- */
            $oldSalesOrderId = $salesInvoice->sales_order_id;

            // Sum qty from old invoice items BEFORE they are deleted
            $oldInvoiceTotalQty = SalesInvoiceItem::where('sales_invoice_id', $invoiceId)
                ->sum('quantity');

            $this->salesInvoiceRepository->update($salesInvoice, $masterData);

            /** ---------------------------------
             *  Step 2: Roll back received_qty on the OLD Sales Order
             *  Only needed when the invoice had a PO linked
             * --------------------------------- */
            if ($oldSalesOrderId && $oldSalesOrderId > 0) {
                $oldSalesOrder = $this->salesOrderRepo->find($oldSalesOrderId);
                if ($oldSalesOrder) {
                    $oldSalesOrderDetails = $oldSalesOrder->details()
                        ->where('sales_order_id', $oldSalesOrderId)
                        ->get();

                    foreach ($oldSalesOrderDetails as $soItem) {
                        $soItem->received_qty = max(0, $soItem->received_qty - $oldInvoiceTotalQty);
                        $soItem->save();
                    }

                    // Compute remaining_qty in PHP — it is an accessor, not a real DB column
                    $oldRemainingQty = $oldSalesOrder->details()
                        ->where('sales_order_id', $oldSalesOrderId)
                        ->selectRaw('SUM(ordered_qty) - SUM(received_qty) as remaining')
                        ->value('remaining');

                    // If qty was returned, the order must be re-opened
                    if ($oldRemainingQty > 0) {
                        $oldSalesOrder->order_status = SalesOrder::STATUS_OPEN;
                        $oldSalesOrder->save();
                    }
                }
            }

            /** ---------------------------------
             *  Step 3: Replace Details & Sundries
             * --------------------------------- */
            $salesInvoice->details()->delete();
            $salesInvoice->details()->createMany($items);
            $salesInvoice->billSundries()->delete();
            $salesInvoice->billSundries()->createMany($billSundry);
            $salesInvoice->save();

            /** ---------------------------------
             *  Step 4: Apply qty to the NEW Sales Order
             *  (may be the same PO or a newly selected one)
             * --------------------------------- */
            if (isset($masterData['sales_order_id']) && $masterData['sales_order_id'] > 0) {
                $salesOrder = $this->salesOrderRepo->find($masterData['sales_order_id']);
                if (!$salesOrder) {
                    throw new \Exception("Sales order not found");
                }
                $salesOrderDetails = $salesOrder->details()
                    ->where('sales_order_id', $masterData['sales_order_id'])
                    ->get();

                foreach ($salesOrderDetails as $soItem) {
                    $soItem->received_qty += $masterData['total_quantity'];
                    $soItem->save();
                }

                // Compute remaining_qty in PHP — it is an accessor, not a real DB column
                $remaining_qty = $salesOrder->details()
                    ->where('sales_order_id', $masterData['sales_order_id'])
                    ->selectRaw('SUM(ordered_qty) - SUM(received_qty) as remaining')
                    ->value('remaining');

                if ($remaining_qty <= 0) {
                    $salesOrder->order_status = SalesOrder::STATUS_CLOSE;
                } else {
                    $salesOrder->order_status = SalesOrder::STATUS_OPEN;
                }
                $salesOrder->save();
            }

            $voucherMaster = $this->prepareVoucherMaster($salesInvoice);
            $voucherLines = $this->prepareVoucherLinesForSale($companyId, $financialYearId, $masterData, $items, $billSundry);
            $voucher = $this->voucherService->updateVoucher($voucherMaster, $voucherLines, $salesInvoice->voucher_id);

            $oldRefExists = Reference::where('voucher_id', $voucher->id)->first();
            if($oldRefExists){
                $oldRefExists->reference_date = $salesInvoice->invoice_date;
                $oldRefExists->reference_number = $salesInvoice->reference_number;
                $oldRefExists->account_id = $salesInvoice->account_id;
                $oldRefExists->amount = $salesInvoice->net_amount;
                $oldRefExists->pending_amount = $oldRefExists->amount - $oldRefExists->settled_amount;
                $oldRefExists->is_closed = $oldRefExists->pending_amount == 0;
                $oldRefExists->closed_at = $oldRefExists->is_closed ? Carbon::now() : null;
                $oldRefExists->updated_by = current_user_id();
                $oldRefExists->updated_at = Carbon::now();
                $oldRefExists->save();
            }else{
                $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
                if ($accountDetail) {
                    if ($accountDetail['is_billwise']) {
                        $referenceData = $this->prepareBillWiseReference($salesInvoice, $voucher);
                        $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
                    }
                }
            }
            
            $salesInvoice->load('details', 'billSundries');
            $this->createOrUpdateStockVoucher($companyId, $salesInvoice, $voucher);
            $this->gstEntryService->updateForSalesInvoice($salesInvoice, $voucher);
            // $this->tdsEntryService->updateForSalesInvoice($salesInvoice, $voucher);

            $this->logAudit(['old_values' => $oldValues], $salesInvoice, $voucher->toArray(), $voucherLines, \App\Models\AuditTrail::ACTION_UPDATE);

            DB::commit();
            return $salesInvoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    public function prepareInvoiceData($data, $companyId, $financialYearId, string $mode = 'create'): array
    {
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
        if (!$accountDetail) {
            throw ValidationException::withMessages([
                'sale_type_id' => 'Invalid account for this company.'
            ]);
        }

        $saleTypeDetail = $this->lookupService->getSaleTypeDetails($companyId, $data['sale_type_id']);
        if (!$saleTypeDetail) {
            throw ValidationException::withMessages([
                'sale_type_id' => 'Invalid Sale type for this company.'
            ]);
        }

        $base = $this->prepareBaseInvoiceData($data, $companyId, $saleTypeDetail);

        $accountType   = $accountDetail['gst_type'];


        if ($mode === 'create') {

            $serialInfo = $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, $companyId, $financialYearId);

            $base = array_merge($base, [
                'uuid'              => $data['uuid'],
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'invoice_serial'    => $serialInfo->serial,
                'invoice_number'    => $serialInfo->voucher_number,
                'reference_number'  => $serialInfo->serial,
                'created_by'        => current_user_id(),
            ]);
        }else{
            $base['updated_by']     = current_user_id();
        }

        if (empty($data['items'])) {
            return [$base, []];
        }

        $items = $this->prepareItemData(
            $data['items'],
            $companyId,
            $saleTypeDetail
        );
        $totals = $this->calculateTotals($items);

        $billSundries = $this->prepareBillSundries(array_merge($base, $totals), $data['bill_sundries']);

        $totals['net_amount'] = $billSundries['net_total'];
        $totals['grand_total'] = $billSundries['grand_total'];

        return [array_merge($base, $totals), $items, $billSundries['rows']];
    }

    protected function prepareBaseInvoiceData(array $data, int $companyId, array $saleTypeDetail): array
    {
        $salesOrderData = [];

        if (isset($data['sales_order_id']) && $data['sales_order_id'] > 0) {
            $salesOrderInfo = $this->salesOrderRepo->getSalesOrderSerialAndNumber($data['sales_order_id']);
            $salesOrderData = [
                'sales_order_id'            => $data['sales_order_id'],
                'sales_order_serial'        => $salesOrderInfo['order_serial'],
            ];
        }

        $prepareData = [
            'grn_number'                => $data['grn_number'] ?? null,
            'last_invoice_date'         => $data['last_invoice_date'],
            // 'kms'                       => $data['kms'],
            'invoice_date'              => $data['invoice_date'],
            'delivery_challan_number'   => $data['delivery_challan_number'] ?? null,
            'account_id'                => $data['account_id'],
            'sale_type_id'              => $data['sale_type_id'],
            'gst_type'                  => $saleTypeDetail['region'],
            // 'broker_id'              => $data['broker_id'],
            'show_date'                 => $data['invoice_date'],
            'vehicle_number'            => $data['vehicle_number'],
            'sales_invoice_number'      => $data['sales_invoice_number'] ?? null,
            'delivery_date'             => $data['delivery_date'],
            'remarks'                   => $data['remarks'] ?? null,
            'ewaybill_number'           => $data['ewaybill_number'] ?? null,
            'ewb_status'                => $data['ewaybill_number'] ? SalesInvoice::EWB_STATUS_GENERATED : SalesInvoice::EWB_STATUS_PENDING,
        ];
        $arrayData = array_merge($salesOrderData, $prepareData);
        return $arrayData;
    }

    /* -----------------------------------------
     | Edit Data
     |------------------------------------------
     */

    public function getEditData(int $salesInvoiceId, int $companyId, int $financialYearId)
    {
        $invoice = SalesInvoice::with(['details', 'billSundries', 'billSundries.sundry', 'billSundries.drAccount', 'billSundries.crAccount', 'saleType', 'details.item', 'details.item.unit', 'details.destination', 'details.condition', 'salesOrder'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->find($salesInvoiceId);

        if (!$invoice) {
            return null;
        }
        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $invoice->account_id);
        return [
            'invoice_id'    => $invoice->id,
            'invoice_date'  => $invoice->invoice_date,
            'account_id'    => $invoice->account_id,
            'account_city'  => $accountDetail['city'] ?? null,
            'account_name'  => $accountDetail['name'] ?? null,
            'gst_type'      => $accountDetail['gst_type'] ?? null,
            'kms'           => $invoice->kms ?: ($accountDetail['kms'] ?? 0),
            'last_invoice_date' => $invoice->last_invoice_date,

            'delivery_challan_number' => $invoice->delivery_challan_number,
            'sales_order_id' => $invoice->sales_order_id,
            'grn_number' => $invoice->grn_number,
            'vehicle_number' => $invoice->vehicle_number,
            'delivery_date' => $invoice->delivery_date,
            'sale_type_id' => $invoice->sale_type_id,
            'cgst_rate'   => $invoice->saleType->cgst ?? 0,
            'sgst_rate'   => $invoice->saleType->sgst ?? 0,
            'igst_rate'   => $invoice->saleType->igst ?? 0,
            'total_quantity' => $invoice->details->sum('quantity'),
            'total_amount' => $invoice->details->sum('amount'),
            'sale_order_id' => $invoice->salesOrder->id ?? null,
            'purchase_order_number' => $invoice->salesOrder->purchase_order_number ?? null,
            'sale_type' => $invoice->saleType,
            'purchase_order_with_destination' => ($invoice->salesOrder->purchase_order_number ?? '') . ' - ' .
                ($invoice->details->first()->destination->name ?? ''),

            'ewaybill_number'         => $invoice->ewaybill_number,
            'remarks'                 => $invoice->remarks,


            // ITEMS
            'details' => $invoice->details->map(function ($row)  use ($invoice) {
                return [
                    'item_id'           => $row->item_id,
                    'item_name'         => $row->item->name ?? null,
                    'unit_name'         => $row->item->unit->name ?? null,
                    'destination_id'    => $row->destination_id,
                    'destination_name'  => $row->destination->name ?? null,
                    'condition_id'      => $row->condition_id,
                    'condition_name'    => $row->condition->name ?? null,
                    'quantity'          => $row->quantity,
                    'cgst_rate'         => $invoice->saleType->cgst ?? 0,
                    'sgst_rate'         => $invoice->saleType->sgst ?? 0,
                    'igst_rate'         => $invoice->saleType->igst ?? 0,
                    'party_quantity'    => $row->party_quantity,
                    'rate'              => $row->rate,
                    'inclusive_rate'    => $row->inclusive_rate ?? 0,
                    'amount'            => $row->amount,
                    'bag_count'         => $row->bag_count,
                ];
            }),

            // BILL SUNDRIES
            'bill_sundries' => $invoice->billSundries->map(function ($bs) {
                return [
                    'sundry_id'              => $bs->sundry_id,
                    'code'                   => $bs->code,
                    'value'                  => $bs->value,
                    'amount'                 => $bs->amount,
                    'sundry_name'            => $bs->name,
                    'calculation_type'       => $bs->calculation_type,
                    'apply_on'               => $bs->apply_on,
                    'bill_sundry_modal_dr_id' => $bs->bill_sundry_modal_dr_id,
                    'bill_sundry_modal_cr_id' => $bs->bill_sundry_modal_cr_id,
                    'base_amount'            => $bs->base_amount,
                    'rate_percent'           => $bs->rate_percent,
                    'sort_order'             => $bs->sort_order,
                    'bill_sundry_type'       => $bs->bill_sundry_type,
                    'dr_account'             => $bs->drAccount ? ['name' => $bs->drAccount->name] : null,
                    'cr_account'             => $bs->crAccount ? ['name' => $bs->crAccount->name] : null,
                ];
            }),
        ];
    }

    /* -----------------------------------------
     | View Data
     |------------------------------------------
     */

    public function getViewData(int $salesInvoiceId, int $companyId, int $financialYearId): SalesInvoice | null
    {
        return $this->salesInvoiceRepository->getViewData($salesInvoiceId, $companyId, $financialYearId);
    }

    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function salesInvoiceList(int $companyId, int $financialYearId, array $filters): array
    {

        [$paginator, $rowCount]  = $this->salesInvoiceRepository->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'sales_invoice.view',
            'sales_invoice.update',
            'sales_invoice.delete',
        ], true);

        $filteredTotal = $this->salesInvoiceRepository->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->salesInvoiceRepository->countAll($companyId, $financialYearId);

        return [
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $filters['size']),
            'current_page' => $filters['page'],
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions,
            'count'        => $rowCount,
        ];
    }

    public function salesInvoiceListAll(int $companyId, int $financialYearId, array $filters)
    {
        return $this->salesInvoiceRepository->listAll($companyId, $financialYearId, $filters);
    }

    public function calculateDueDate(string $invoiceDate, int $deliveryDays): string
    {
        return Carbon::parse($invoiceDate)
            ->addDays($deliveryDays)
            ->format('Y-m-d');
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId)
    {
        return $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, $companyId, $financialYearId);
    }

    public function getInvoiceSerial($companyId, $financialYearId): Collection
    {
        return $this->salesInvoiceRepository->getInvoiceSerial($companyId, $financialYearId);
    }

    public function getGrnNumber($companyId, $financialYearId): Collection
    {
        return $this->salesInvoiceRepository->getGrnNumber($companyId, $financialYearId);
    }

    public function getPoNumbers($companyId, $financialYearId): Collection
    {
        return $this->salesInvoiceRepository->getPoNumbers($companyId, $financialYearId);
    }

    public function allOrders(int $companyId, ?string $search = null): Collection
    {
        $query = $this->salesOrderRepo->query()
            ->where('sales_orders.company_id', $companyId)   // ← fix here
            ->where('sales_orders.order_status', SalesOrder::STATUS_OPEN)   // ← fix here
            ->with(['details.destination'])
            ->select([
                'sales_orders.id',
                DB::raw("CONCAT(sales_orders.order_number, '-', destinations.name) AS name")
            ])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('destinations', 'destinations.id', '=', 'sales_order_items.destination_id')
            ->limit(20);

        if ($search) {
            $query->where('sales_orders.order_number', 'like', "%{$search}%");
        }
        // dd($query->get());
        return $query->get();
    }

    public function getSalesOrderDetails(int $companyId, string $purchaseOrderNumber): array
    {
        $items = $this->salesOrderRepo->query()
            ->with(['account', 'broker', 'details', 'account.preference'])
            ->where('company_id', $companyId)
            ->where('purchase_order_number', $purchaseOrderNumber)
            ->get();

        if ($items->isEmpty()) {
            return ['status' => 'not_found', 'data' => []];
        }

        $openItems = $items->where('order_status', \App\Models\SalesOrder::STATUS_OPEN);

        if ($openItems->isEmpty()) {
            return ['status' => 'closed', 'data' => []];
        }

        $mapped = $openItems->map(function ($item) {
            return [
                'sales_order_id' => $item->id,
                'po_number'      => $item->purchase_order_number ?? null,
                'order_serial'   => $item->order_serial ?? null,
                'account_id'     => $item->account_id ?? null,
                'account_name'   => $item->account->name ?? null,
                'kms'            => $item->account->preference->distance ?? 0,
                'city'           => $item->account->city ?? null,
                'gst_type'       => $item->account->gst_type ?? null,
                'broker_id'      => $item->broker_id ?? null,
                'broker_name'    => $item->broker->name ?? null,
                'po_date'        => $item->purchase_order_date ?? null,
                'delivery_date'  => $item->delivery_date ?? null,
                'delivery_days'  => $item->delivery_days ?? null,
                'due_date'       => $item->due_date ?? null,
                'total_quantity' => $item->total_quantity ?? null,
                'sub_total'      => $item->sub_total ?? null,
                'sales_type_id'  => $item->account->gst_type === 'interstate' 
                    ? ($item->details->first()->item->sale_type_interstate_id ?? null) 
                    : ($item->details->first()->item->sale_type_local_id ?? null),

                // details
                'details' => $item->details->map(function ($detail) use ($item) {
                    return [
                        'sales_order_id'   => $item->id,
                        'destination_id'   => $detail->destination_id ?? null,
                        'destination_name' => $detail->destination->name ?? null,
                        'condition_id'     => $detail->condition_id ?? null,
                        'condition_name'   => $detail->condition->name ?? null,
                        'ordered_qty'      => $detail->ordered_qty ?? 0,
                        'received_qty'     => $detail->received_qty ?? 0,
                        'rate'             => $detail->rate ?? 0,
                        'inclusive_rate'   => $detail->inclusive_rate ?? 0,
                        'taxable_amount'   => $detail->taxable_amount ?? 0,
                        'item_id'          => $detail->item_id ?? 0,
                        'item_name'        => $detail->item->name ?? '',
                        'unit_id'          => $detail->item->unit->id ?? "",
                        'unit_name'        => $detail->item->unit->name ?? "",
                    ];
                }),
            ];
        })->toArray();
        
        return ['status' => 'success', 'data' => array_values($mapped)];
    }

    protected function prepareItemData(array $items, int $companyId, array $salesTypeData): array
    {
        // dd('prepareItemData', $items, $salesTypeData);
        return collect($items)->map(function ($item) use ($companyId, $salesTypeData) {

            [$cgstPercent, $sgstPercent, $igstPercent] = match ($salesTypeData['region']) {
                SalesInvoice::TAX_LOCAL => [$salesTypeData['cgst'], $salesTypeData['sgst'], 0],
                default        => [0, 0, $salesTypeData['igst']],
            };

            $taxableAmount = (float)($item['rate'] ?? 0) * (float)($item['quantity'] ?? 0);

            $cgstAmount = round($taxableAmount * ($cgstPercent / 100), 2);
            $sgstAmount = round($taxableAmount * ($sgstPercent / 100), 2);
            $igstAmount = round($taxableAmount * ($igstPercent / 100), 2);

            $taxAmount = round($cgstAmount + $sgstAmount + $igstAmount, 2);
            $netAmount = round($taxableAmount + $taxAmount, 2);

            return [
                'item_id'             => $item['item_id'],
                'quantity'            => $item['quantity'] ?? 0,
                'party_quantity'      => $item['party_quantity'] ?? 0,
                'rate'                => $item['rate'] ?? 0,
                'inclusive_rate'      => $item['inclusive_rate'] ?? 0,
                'tax_amount'          => $taxAmount ?? 0,
                'amount'              => $item['amount'] ?? 0,
                'net_amount'          => $netAmount ?? 0,
                'cgst_rate'           => $cgstPercent,
                'sgst_rate'           => $sgstPercent,
                'igst_rate'           => $igstPercent,
                'bag_count'           => $item['bag_count'] ?? 0,
                'cgst_amount'         => $cgstAmount,
                'sgst_amount'         => $sgstAmount,
                'igst_amount'         => $igstAmount,
                'taxable_amount'      => $taxableAmount,
                'condition_id'        => $item['condition_id'] ?? null,
                'destination_id'      => $item['destination_id'] ?? null,
                'sales_order_id'      => $item['sales_order_id'] ?? null,
            ];
        })->toArray();
    }

    protected function calculateTotals(array $items): array
    {
        return [
            'total_quantity' => array_sum(array_column($items, 'quantity')),
            'taxable_amount' => array_sum(array_column($items, 'amount')),
            'tax_amount'      => array_sum(array_column($items, 'tax_amount')),
            'net_amount'      => 0,
            'grand_total'    => 0,
        ];
    }

    protected function prepareBillSundries(array $base, array $billSundryItems): array
    {
        if (empty($billSundryItems)) {
            return [
                'rows'        => [],
                'net_total'   => $base['taxable_amount'],
                'grand_total' => $base['taxable_amount'],
            ];
        }

        $billSundryIds = array_column($billSundryItems, 'bill_sundry_id');

        $billSundryRecords = $this->billSundryRepo->query()
            ->whereIn('id', $billSundryIds)
            ->get()
            ->keyBy('id');

        $baseAmount     = (float) $base['taxable_amount'];
        $runningTotal   = $baseAmount;
        $previousAmount = 0;

        $netTotal   = $baseAmount;
        $grandTotal = $baseAmount;

        $results = [];

        // Sort by sort_order first
        usort($billSundryItems, function ($a, $b) {
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        foreach ($billSundryItems as $index => $billSundry) {

            $record = $billSundryRecords->get($billSundry['bill_sundry_id']);
            if (!$record) continue;

            $rate      = (float) ($billSundry['bill_sundry_percentage'] ?? 0);
            $calcType  = $record->calculation_type;
            $applyOn   = $record->apply_on;

            // ---------- BASE FOR CALCULATION ----------
            switch ($applyOn) {
                case 'basic':
                    $baseForCalc = $baseAmount;
                    break;

                case 'running_total':
                    $baseForCalc = $runningTotal;
                    break;

                case 'previous_row':
                    $baseForCalc = $previousAmount;
                    break;

                case 'grand_total':
                    $baseForCalc = $runningTotal;
                    break;

                default:
                    $baseForCalc = $baseAmount;
            }

             // ---------- CALCULATION ----------
            $shouldRoundUp = (bool) ($record->bill_sundry_amount_round_off ?? false);

            // ---------- CALCULATION ----------
            if ($calcType === 'percentage') {
                 $rawValue = ($baseForCalc * $rate) / 100;
            } else {
                $rawValue = (float) ($billSundry['bill_sundry_value'] ?? 0);
            }

            $value = $shouldRoundUp ? round($rawValue) : round($rawValue, 2);

            // ---------- ADDITION / DEDUCTION ----------
            $amount = $record->bill_sundry_type === 'subtractive'
                ? -abs($value)
                : abs($value);

            // Update for next iteration
            $previousAmount = $amount;
            $runningTotal  += $amount;

            $isAdjustInPartyAmount = (bool) $record->sale_adjust_in_party_amount ?? false;

            // ---------- FINAL OUTPUT ROW ----------
            $results[] = [
                'sundry_id'               => $record->id,
                'code'                    => $record->code,
                'name'                    => $record->name,

                'bill_sundry_type'        => $record->bill_sundry_type,
                'calculation_type'        => $record->calculation_type,
                'apply_on'                => $record->apply_on,

                'bill_sundry_modal_dr_id' => $billSundry['bill_sundry_modal_dr_id'] ?? null,
                'bill_sundry_modal_cr_id' => $billSundry['bill_sundry_modal_cr_id'] ?? null,

                'base_amount'             => $baseForCalc,
                'rate_percent'            => $rate,
                'value'                   => $value,
                'amount'                  => $amount,

                'sort_order'              => $billSundry['sort_order'] ?? ($index + 1),

                'remarks'                 => $billSundry['remarks'] ?? null,
                'affect_net_total'        => $isAdjustInPartyAmount,
            ];

            if ($isAdjustInPartyAmount) {
                $netTotal += $amount;
            }

            $grandTotal += $amount;
        }

        return [
            'rows'        => $results,
            'net_total'   => round($netTotal, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    public function isDuplicateReference(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $invoiceId = null): bool
    {
        return $this->salesInvoiceRepository->existsByReferenceNumberAndAccount(
            companyId: $companyId,
            financialYearId: $financialYearId,
            referenceNumber: $referenceNumber,
            accountId: $accountId,
            invoiceId: $invoiceId
        );
    }

    public function getByReferenceNumberAndAccount(
        int $companyId,
        int $financialYearId,
        string $referenceNumber,
        int $accountId
    ): ?int {

        $record = $this->salesInvoiceRepository->findByReferenceNumberAndAccount([
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'reference_number'  => $referenceNumber,
            'account_id'        => $accountId,
        ]);

        return $record?->id;
    }


    public function getInvoicePrintDetails(int|array|string|null $salesInvoiceId, int $companyId, int $financialYearId, array $filters = []): array
    {
        $invoices = $this->salesInvoiceRepository->getInvoicePrintDetails($salesInvoiceId, $companyId, $financialYearId, $filters);
        $finalData = [];

        if ($invoices->isEmpty()) {
            return [];
        }

        $company = Company::with('state')->find($companyId);


        foreach ($invoices as $invoice) {
            $qrData = $invoice?->eInvoice?->signed_qr_code;
            $billType = 'Bill of Supply';

            $items = collect($invoice->details)->map(function ($subItem) {
                return [
                    'name'        => $subItem?->item?->name,
                    'net_quantity' => $subItem?->quantity ? number_format($subItem?->quantity, 3, ".", "") : 0,
                    'gross_quantity' => $subItem?->party_quantity ? number_format($subItem?->party_quantity, 3, ".", "") : 0,
                    'rate'        => $subItem?->rate ? number_format($subItem?->rate, 2, ".", "") : 0,
                    'amount'      => $subItem?->amount ? number_format($subItem?->amount, 2, ".", "") : 0,
                    'hsn_sac_code'    => $subItem?->item?->hsn_sac_code,
                    'unit'        => $subItem?->item->unit?->name,
                    'bag_count'   => $subItem->bag_count,
                ];
            })->toArray();

            $billSundries = [];

            foreach ($invoice?->billSundries as  $billSundry) {
                if ($billSundry->amount == 0) continue;
                if (in_array($billSundry->code, [1002, 1003, 1004])) {
                    $billType = 'Tax Invoice';
                }

                $billSundries[] = [
                    'name'             => $billSundry->name,
                    'bill_sundry_type' => $billSundry->bill_sundry_type,
                    'amount'           => $billSundry->amount,
                    'rate_percent'        => $billSundry->rate_percent != 0 ? $billSundry->rate_percent : '',
                    'is_gst'            => in_array($billSundry->code, [1002, 1003, 1004]) ? true : false
                ];
            }

            $finalData[] = (object)[
                'company_name'                 => $company->name,
                'gst_number'                   => $company->gst_number,
                'address_one'                  => $company->address_one,
                'address_two'                  => $company->address_two,
                'print_name'                   => $company->print_name,
                'postal_code'                  => $company->postal_code,
                'mobile_number'                => $company->mobile_number,
                'state_name'                   => $company?->state?->name,
                'gst_code'                     => $company?->state?->gst_code,
                'pan_no'                       => $company?->pan_no,

                'bill_type'                    => $billType,

                'receiver_name'                => $invoice?->account?->name,
                'receiver_city'                => $invoice?->account?->city,
                'receiver_address_one'         => $invoice?->account?->address_one,
                'receiver_address_two'         => $invoice?->account?->address_two,
                'receiver_address_postal_code' => $invoice?->account?->postal_code,
                'receiver_gst_code'            => $invoice?->account?->state?->gst_code,
                'receiver_state_name'          => $invoice?->account?->state?->name,
                'receiver_gst_number'          => $invoice?->account?->taxDetail?->gst_number,
                'receiver_mobile_number'       => $invoice?->account?->mobile_number,
                'receiver_postal_code'         => $invoice?->account?->postal_code,
                'receiver_pan_no'               => $invoice?->account?->taxDetail?->pan_no,

                'invoice_serial'               => $invoice->invoice_serial,
                'invoice_date'                 => $invoice->invoice_date ? format_date($invoice->invoice_date) : '',
                'vehicle_number'               => $invoice->vehicle_number,

                'purchase_order_number'        => $invoice?->salesOrder?->purchase_order_number,
                'delivery_date'                => $invoice->delivery_date ? format_date($invoice->delivery_date) : '',
                'destination_name'                  => $invoice?->details?->first()?->destination?->name,

                'broker_name'                  => 'SELF',
                'transport'                    => '',
                'lr_no'                        => '',
                'lr_date'                      => '',
                'grn_number'                   => $invoice->grn_number,
                'payment_terms'                => '',
                'remarks'                      => $invoice->remarks,

                'net_amount'                   => $invoice->net_amount,

                'eway_bill_number'             => $invoice?->eWayBill?->ewb_no,
                'irn'                          => $invoice?->eInvoice?->irn,
                'ack_no'                       => $invoice?->eInvoice?->ack_no,
                'ack_dt'                       => $invoice?->eInvoice?->ack_dt,

                'signed_qr_code'               => !empty($qrData)
                    ? QrCode::size(200)->generate($qrData)
                    : '',

                'items'                        => $items,
                'billSundries'                 => $billSundries,
            ];
        }

        return $finalData;
    }

    public function getPendingPurchaseOrders(int $companyId, int $financialYearId, ?int $currentOrderId = null): array
    {
        $orders = SalesOrder::with('details.destination')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where(function ($q) use ($currentOrderId) {
                $q->where('order_status', SalesOrder::STATUS_OPEN);

                if ($currentOrderId) {
                    $q->orWhere('id', $currentOrderId);
                }
            })
            ->get();

        $result = [];

        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $result[] = [
                    'id' => $order->id,
                    'order_number' => $order->purchase_order_number . ' - ' . $detail->destination->name,
                ];
            }
        }

        return $result;
    }

    public function validateGrn(int $companyId, string $grnNumber, ?int $salesInvoiceId, int $accountId): bool
    {
        return SalesInvoice::where('company_id', $companyId)
            ->where('grn_number', $grnNumber)
            ->where('account_id', $accountId)
            ->when($salesInvoiceId, fn($query) => $query->where('id', '!=', $salesInvoiceId))
            ->exists();
    }

    private function buildAuditValues(SalesInvoice $invoice): array
    {
        $oldVoucherLines = [];
        if ($invoice->voucher_id) {
            $transactions = \App\Models\VoucherTransaction::where('voucher_id', $invoice->voucher_id)->orderBy('id')->get();
            foreach ($transactions as $t) {
                $oldVoucherLines[] = [
                    'account_id' => $t->account_id,
                    'debit'      => $t->debit,
                    'credit'     => $t->credit,
                ];
            }
        }
        return $this->prepareAuditData($invoice, [], $oldVoucherLines, '');
    }

    private function logAudit(
        array           $data,
        SalesInvoice $invoice,
        array           $voucher,
        array           $voucherLines,
        string          $action
    ): mixed {
        if (!isAuditLog()) {
            return null;
        }

        $oldValues = $data['old_values'] ?? [];
        $newValues = [];

        if ($action === \App\Models\AuditTrail::ACTION_CREATE || $action === \App\Models\AuditTrail::ACTION_UPDATE) {
            if (!$invoice->relationLoaded('details') || !$invoice->relationLoaded('billSundries')) {
                $invoice->load('details', 'billSundries');
            }
            $newValues = $this->prepareAuditData($invoice, $voucher, $voucherLines, $action);
        }

        if ($action === \App\Models\AuditTrail::ACTION_UPDATE) {
            $oldDot = \Illuminate\Support\Arr::dot($oldValues);
            $newDot = \Illuminate\Support\Arr::dot($newValues);
            $allKeys = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));
            $hasChanges = false;
            foreach ($allKeys as $key) {
                if (($oldDot[$key] ?? null) != ($newDot[$key] ?? null)) {
                    $hasChanges = true;
                    break;
                }
            }
            if (!$hasChanges) {
                return null;
            }
        }

        $orgAmount   = (float) collect($oldValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();
        $finalAmount = (float) collect($newValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();

        return $this->auditService->log([
            'company_id'        => $invoice->company_id,
            'financial_year_id' => $invoice->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::SALES,
            'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => SalesInvoice::class,
            'source_id'         => $invoice->id,
            'voucher_id'        => $invoice->voucher_id,
            'reference_number'  => $invoice->reference_number,
            'org_amount'        => $orgAmount,
            'final_amount'      => $finalAmount,
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ]);
    }

    private function prepareAuditData(
        SalesInvoice $invoice,
        array           $voucher,
        array           $voucherLines,
        string          $action
    ): array {
        // Unset cached relations that might have changed to fetch fresh data
        $invoice->unsetRelation('saleType');
        $invoice->unsetRelation('account');
        $invoice->unsetRelation('salesOrder');

        if (!$invoice->relationLoaded('details') || !$invoice->relationLoaded('billSundries')) {
            $invoice->load('details.destination', 'details.condition', 'billSundries', 'saleType', 'account', 'salesOrder');
        } else {
            $invoice->load('saleType', 'account', 'salesOrder');
        }

        $itemIds = $invoice->details->pluck('item_id')->filter()->toArray();
        $itemsData = \App\Models\Item::with('unit')->whereIn('id', array_unique($itemIds))->get(['id', 'name', 'hsn_sac_code', 'unit_id'])->keyBy('id');

        $accountIds = collect($voucherLines)->pluck('account_id')->filter()->toArray();
        $accountsMap = \App\Models\Account::whereIn('id', array_unique($accountIds))->pluck('name', 'id');

        $data = [];

        $data['voucher_no']         = $invoice->invoice_serial;
        $data['reference_number']   = $invoice->reference_number;
        $data['invoice_date']       = $invoice->invoice_date instanceof \Carbon\Carbon
            ? $invoice->invoice_date->format('Y-m-d')
            : substr((string)$invoice->invoice_date, 0, 10);
        $data['delivery_date']       = $invoice->delivery_date instanceof \Carbon\Carbon
            ? $invoice->delivery_date->format('Y-m-d')
            : substr((string)$invoice->delivery_date, 0, 10);
        $data['account_id']         = $invoice->account_id;
        $data['account_name']       = $invoice->account->name ?? '';
        $data['sale_type_id']       = $invoice->sale_type_id;
        $data['sale_type_name']     = $invoice->saleType->name ?? '';
        $data['grn_number']         = $invoice->grn_number;
        $data['delivery_challan_number'] = $invoice->delivery_challan_number;
        $data['sales_order_id']     = $invoice->sales_order_id;
        $data['sales_order_serial'] = $invoice->salesOrder->order_serial ?? '';
        $data['vehicle_number']     = $invoice->vehicle_number;
        $data['ewaybill_number']    = $invoice->ewaybill_number;
        $data['taxable_amount']   = number_format((float) $invoice->taxable_amount, 2, '.', '');
        $data['tax_amount']       = number_format((float) $invoice->tax_amount,     2, '.', '');
        $data['net_amount']       = number_format((float) $invoice->net_amount,     2, '.', '');
        $data['grand_total']      = number_format((float) $invoice->grand_total,    2, '.', '');
        $data['remarks']          = $invoice->remarks;

        foreach (array_values($voucherLines) as $i => $line) {
            $data["details.{$i}.account_id"]   = $line['account_id'] ?? null;
            if (isset($line['account_id'])) {
                $data["details.{$i}.account_name"] = $accountsMap[$line['account_id']] ?? '';
            }
            $data["details.{$i}.debit"]        = number_format((float) ($line['debit']  ?? 0), 2, '.', '');
            $data["details.{$i}.credit"]       = number_format((float) ($line['credit'] ?? 0), 2, '.', '');
        }

        foreach ($invoice->details->values() as $i => $d) {
            $item = $itemsData->get($d->item_id);
            $data["items.{$i}.item_id"]        = $d->item_id;
            $data["items.{$i}.name"]           = $item->name ?? '';
            $data["items.{$i}.item_unit_name"] = $item->unit->name ?? '';
            if (!empty($item->hsn_sac_code)) {
                $data["items.{$i}.hsn_sac_code"] = $item->hsn_sac_code;
            }
            $data["items.{$i}.destination_id"]   = $d->destination_id;
            $data["items.{$i}.destination_name"] = $d->destination->name ?? '';
            $data["items.{$i}.condition_id"]     = $d->condition_id;
            $data["items.{$i}.condition_name"]   = $d->condition->name ?? '';
            $data["items.{$i}.cgst_rate"]        = number_format((float) $d->cgst_rate, 2, '.', '');
            $data["items.{$i}.sgst_rate"]        = number_format((float) $d->sgst_rate, 2, '.', '');
            $data["items.{$i}.igst_rate"]        = number_format((float) $d->igst_rate, 2, '.', '');
            $data["items.{$i}.bags"]             = $d->bag_count;

            $data["items.{$i}.party_quantity"] = number_format((float) $d->party_quantity, 4, '.', '');
            $data["items.{$i}.quantity"]       = number_format((float) $d->quantity, 4, '.', '');
            $data["items.{$i}.rate"]           = number_format((float) $d->rate,     4, '.', '');
            $data["items.{$i}.inclusive_rate"] = number_format((float) $d->inclusive_rate, 4, '.', '');
            $data["items.{$i}.amount"]         = number_format((float) $d->amount,   2, '.', '');
        }

        $si = 0;
        foreach ($invoice->billSundries as $s) {
            if ((float) $s->value == 0 && (float) $s->amount == 0) {
                continue;
            }
            $data["bill_sundries.{$si}.sundry_id"] = $s->sundry_id;
            $data["bill_sundries.{$si}.name"]      = $s->name;
            $data["bill_sundries.{$si}.value"]     = number_format((float) $s->value,  2, '.', '');
            $data["bill_sundries.{$si}.amount"]    = number_format((float) $s->amount, 2, '.', '');
            $si++;
        }

        return $data;
    }
}

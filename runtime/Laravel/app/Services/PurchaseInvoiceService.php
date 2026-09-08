<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Helpers\TaxHelper;
use App\Models\BillSundry;
use App\Models\Grn;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceSundry;
use App\Models\Reference;
use App\Models\VoucherType;
use App\Models\AuditTrail;
use App\Models\AuditTrailDetail;
use App\Repositories\ReferencesRepository;
use App\Repositories\BillSundryRepository;
use App\Repositories\GrnRepository;
use App\Repositories\PurchaseInvoiceRepository;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\StockVoucherService;
use App\Services\TdsEntryService;
use Carbon\Carbon;

// use Illuminate\Support\Collection;

class PurchaseInvoiceService
{
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;
    protected PurchaseOrderRepository $purchaseOrderRepo;
    protected LookupService $lookupService;
    protected GrnRepository $grnRepo;
    protected BillSundryRepository $billSundryRepo;
    protected PurchaseInvoiceRepository $purchaseInvoiceRepo;
    protected ReferencesRepository $billReferenceRepo;
    protected StockVoucherService $stockVoucherService;
    protected GstEntryService $gstEntryService;
    protected TdsEntryService $tdsEntryService;
    protected AuditService $auditService;

    public function __construct(VoucherService $voucherService, PurchaseOrderRepository $purchaseOrderRepo, LookupService $lookupService, GrnRepository $grnRepo, BillSundryRepository $billSundryRepo, PurchaseInvoiceRepository $purchaseInvoiceRepo, ReferencesRepository $billReferenceRepo, ReferenceService $referenceService, StockVoucherService $stockVoucherService, GstEntryService $gstEntryService, TdsEntryService $tdsEntryService, AuditService $auditService)
    {
        $this->voucherService = $voucherService;
        $this->lookupService = $lookupService;
        $this->purchaseOrderRepo = $purchaseOrderRepo;
        $this->grnRepo = $grnRepo;
        $this->billSundryRepo = $billSundryRepo;
        $this->purchaseInvoiceRepo = $purchaseInvoiceRepo;
        $this->billReferenceRepo = $billReferenceRepo;
        $this->referenceService = $referenceService;
        $this->stockVoucherService = $stockVoucherService;
        $this->gstEntryService = $gstEntryService;
        $this->tdsEntryService = $tdsEntryService;
        $this->auditService = $auditService;
    }

    public function fetchPendingGrns(int $companyId)
    {
        return $this->grnRepo->getStatusOpenGrnNumbers(companyId: $companyId);
    }

    public function createInvoice(array $data, int $companyId, int $financialYearId)
    {
        DB::beginTransaction();
        try {
            [$masterData, $items, $billSundry, $accountDetail, $purchaseTypeDetail, $billSundryRecords] = $this->prepareInvoicePayload($data, $companyId, $financialYearId);

            $invoice = $this->purchaseInvoiceRepo->create($masterData);

            $invoice->details()->createMany($items);

            if (!empty($billSundry)) {
                $invoice->billSundries()->createMany($billSundry);
            }

            if (!empty($masterData['grn_id'])) {
                $this->grnRepo->query()->where('id', $masterData['grn_id'])->update(['grn_status' => Grn::STATUS_BILLED]);
            }

            $voucherMaster = $this->prepareVoucherMaster($invoice);

            $voucherLines = $this->prepareVoucherLinesForPurchase(
                $companyId,
                $financialYearId,
                $masterData,
                $items,
                $billSundry,
                $purchaseTypeDetail,
                $billSundryRecords
            );

            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $invoice->update([
                'voucher_id' => $voucher->id,
            ]);

            if (!empty($accountDetail['is_billwise'])) {
                $referenceData = $this->prepareBillWiseReference($invoice, $voucher);
                $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
            }

            $invoice->loadMissing([
                'details.item.unit',
                'details.destination',
                'details.condition',
                'billSundries',
                'purchaseType',
                'account.taxDetail',
                'broker',
            ]);

            $this->createOrUpdateStockVoucher($companyId, $invoice, $voucher);
            $this->gstEntryService->storeForPurchaseInvoice($invoice, $voucher);
            $this->tdsEntryService->storeForPurchaseInvoice($invoice, $voucher);

            $this->logAudit([], $invoice, $voucher->toArray(), $voucherLines, AuditTrail::ACTION_CREATE);

            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createOrUpdateStockVoucher($companyId, $invoice, $voucher)
    {
        $stockTransactions = [];

        foreach ($invoice->details as $detail) {

            if ($detail->quantity <= 0) {
                continue;
            }
            $isMaintainStock = $detail->item
                ? (bool) $detail->item->is_maintain_stock_balance
                : (bool) ($this->lookupService->getItemDetails($companyId, $detail->item_id)['is_maintain_stock_balance'] ?? false);

            if (!$isMaintainStock) {
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
            'in_qty' => $detail->quantity,
            'out_qty' => 0,
            'rate' => $detail->rate,
            'amount' => $detail->amount,
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

    private function prepareVoucherMaster($invoice): array
    {
        return [
            'uuid'             => $invoice->uuid,
            'company_id'        => $invoice->company_id,
            'financial_year_id' => $invoice->financial_year_id,
            'voucher_date'      => $invoice->invoice_date,
            'voucher_type_id'   => VoucherType::PURCHASE_INVOICE,
            'source_id'         => $invoice->id,
            'source_type'       => SourceType::PURCHASE,
            'reference_number'  => $invoice->reference_number,
            'voucher_serial'    => $invoice->invoice_serial,
            'voucher_number'    => $invoice->invoice_number,
            'narration'         => $invoice->remarks,
        ];
    }

    private function prepareBillWiseReference($invoice, $voucher): array
    {
        return [
            'reference_number' => $invoice->reference_number,
            'reference_date' => $invoice->invoice_date,
            'parent_voucher_id' => $voucher->id,
            'reference_type' => 'new_ref',
            'file_number' => $invoice->file_number,
            'amount' => $invoice->net_amount,
            'direction' => config('ref_direction_map.direction.' . SourceType::PURCHASE),
            'account_id' => $invoice->account_id,
            'source_type' => SourceType::PURCHASE,
            'source_id' => $invoice->id,
            'voucher_id' => $voucher->id,
            'pending_amount' => $invoice->net_amount,
        ];
    }

    public function updateInvoice(array $data, int $companyId, int $financialYearId, int $invoiceId)
    {
        DB::beginTransaction();
        try {
            [$masterData, $items, $billSundry, $accountDetail, $purchaseTypeDetail, $billSundryRecords] = $this->prepareInvoicePayload(
                $data,
                $companyId,
                $financialYearId,
                'update',
                $invoiceId
            );

            $invoice = $this->purchaseInvoiceRepo->find($invoiceId);

            if (!$invoice) {
                throw new \Exception("Purchase invoice not found");
            }

            $oldRefExists = Reference::where('voucher_id', $invoice->voucher_id)->first();
            if ($oldRefExists && $oldRefExists->is_closed && $invoice->net_amount != $data['net_total']) {
                throw new \Exception("Purchase invoice already paid");
            }

            // Capture old values for audit trail before any modifications
            $invoice->loadMissing([
                'details.item.unit',
                'details.destination',
                'details.condition',
                'billSundries',
                'purchaseType',
                'account.taxDetail',
                'broker',
            ]);
            $oldValues = $this->buildAuditValues($invoice);

            

            $oldGrnId = $invoice->grn_id;
            $newGrnId = $masterData['grn_id'] ?? null;

            /** ---------------------------------
             *  Update Invoice Master
             * --------------------------------- */
            $invoice = $this->purchaseInvoiceRepo->update($invoice, $masterData);

            /** ---------------------------------
             *  Sync GRN Status
             * --------------------------------- */
            $this->syncGrnStatus($oldGrnId, $newGrnId);

            /** ---------------------------------
             *  Refresh Details & Sundries
             * --------------------------------- */
            $invoice->details()->delete();
            $invoice->details()->createMany($items);

            $invoice->billSundries()->delete();
            $invoice->billSundries()->createMany($billSundry);

            /** ---------------------------------
             *  Voucher Update
             * --------------------------------- */
            $voucherMaster = $this->prepareVoucherMaster($invoice);

            $voucherLines = $this->prepareVoucherLinesForPurchase(
                $companyId,
                $financialYearId,
                $masterData,
                $items,
                $billSundry,
                $purchaseTypeDetail,
                $billSundryRecords
            );

            $voucher = $this->voucherService->updateVoucher(
                $voucherMaster,
                $voucherLines,
                $invoice->voucher_id
            );

            if ($oldRefExists) {
                $oldRefExists->reference_date = $invoice->invoice_date;
                $oldRefExists->reference_number = $invoice->reference_number;
                $oldRefExists->file_number = $invoice->file_number;
                $oldRefExists->account_id = $invoice->account_id;
                $oldRefExists->amount = $invoice->net_amount;
                $oldRefExists->pending_amount = $oldRefExists->amount - $oldRefExists->settled_amount;
                $oldRefExists->is_closed = $oldRefExists->pending_amount == 0;
                $oldRefExists->closed_at = $oldRefExists->is_closed ? Carbon::now() : null;
                $oldRefExists->updated_by = current_user_id();
                $oldRefExists->updated_at = Carbon::now();
                $oldRefExists->save();
            } else {
                if (!empty($accountDetail['is_billwise'])) {
                    $referenceData = $this->prepareBillWiseReference($invoice, $voucher);
                    $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
                }
            }

            // Reload details and sundries since they were deleted and re-created
            $invoice->load([
                'details.item.unit',
                'details.destination',
                'details.condition',
                'billSundries',
                'purchaseType',
                'account.taxDetail',
                'broker',
            ]);

            $this->createOrUpdateStockVoucher($companyId, $invoice, $voucher);
            $this->gstEntryService->updateForPurchaseInvoice($invoice, $voucher);
            $this->tdsEntryService->updateForPurchaseInvoice($invoice, $voucher);

            $this->logAudit(['old_values' => $oldValues], $invoice, $voucher->toArray(), $voucherLines, AuditTrail::ACTION_UPDATE);

            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /*--------------------------------------------------------------
    | DELETE INVOICE
    --------------------------------------------------------------*/
    public function deleteInvoice(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // Capture old values before deletion
            $invoice->loadMissing([
                'details.item.unit',
                'details.destination',
                'details.condition',
                'billSundries',
                'purchaseType',
                'account',
                'broker',
            ]);
            $oldValues = $this->prepareAuditData($invoice, [], [], AuditTrail::ACTION_DELETE);

            // Prevent deletion if the invoice reference has been partially/fully settled
            $refExists = Reference::where('voucher_id', $invoice->voucher_id)->first();
            if ($refExists && $refExists->settled_amount > 0) {
                throw new \Exception('Purchase invoice cannot be deleted because it has already been (partially) paid.');
            }

            // Reopen GRN if linked
            if ($invoice->grn_id) {
                $this->grnRepo->query()->where('id', $invoice->grn_id)->update(['grn_status' => Grn::STATUS_OPEN]);
            }

            // Soft-delete the voucher
            if ($invoice->voucher_id) {
                $this->voucherService->deleteVoucher($invoice->voucher_id);
            }

            // Mark invoice as deleted
            $invoice->deleted_by = current_user_id();
            $invoice->save();
            $invoice->delete();

            $this->logAudit(['old_values' => $oldValues], $invoice, [], [], AuditTrail::ACTION_DELETE);
        });
    }

    private function syncGrnStatus(?int $oldGrnId, ?int $newGrnId): void
    {
        if ($oldGrnId && $oldGrnId !== $newGrnId) {
            $this->grnRepo->query()->where('id', $oldGrnId)->update(['grn_status' => Grn::STATUS_OPEN]);
        }

        if ($newGrnId && $oldGrnId !== $newGrnId) {
            $this->grnRepo->query()->where('id', $newGrnId)->update(['grn_status' => Grn::STATUS_BILLED]);
        }
    }


    private function prepareVoucherLinesForPurchase(
        $companyId,
        $financialYearId,
        $masterData,
        $items,
        $billSundry,
        ?array $purchaseTypeDetail = null,
        $billSundryRecords = null
    ) {
        $lines = [
            'purchase'       => [],
            'supplier'       => [],
            'over_and_above' => [],
        ];

        // 1. Base amounts
        $purchaseAmount = array_sum(array_column($items, 'amount'));
        $supplierAmount = $purchaseAmount;

        // 2. Bill sundry master
        if ($billSundryRecords === null) {
            $billSundryIds = array_column($billSundry, 'sundry_id');
            $billSundryRecords = $this->billSundryRepo->query()
                ->whereIn('id', $billSundryIds)
                ->get()
                ->keyBy('id');
        }

        // 3. Purchase ledger
        if ($purchaseTypeDetail === null) {
            $purchaseTypeDetail = $this->lookupService->getPurchaseTypeDetails(
                $companyId,
                $masterData['purchase_type_id']
            );
        }

        /**
         * Party detection (ONLY actual party)
         */
        $isPartyLedger = function ($accountId) use ($masterData) {
            return (int) $accountId === (int) $masterData['account_id'];
        };

        /**
         * Push voucher line
         */
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

        // 4. Bill sundry processing
        foreach ($billSundry as $row) {

            if ($row['value'] == 0 || $row['value'] == null || $row['value'] == ('0') || $row['value'] == '') continue;

            $record = $billSundryRecords->get($row['sundry_id']);
            if (!$record) {
                continue;
            }

            $value = (float) $row['value'];

            $isAdditive = $record->bill_sundry_type === BillSundry::ADDICTIVE
                || $record->bill_sundry_type === 'additive';

            $isSubtractive = $record->bill_sundry_type === BillSundry::SUBTRACTIVE
                || $record->bill_sundry_type === 'subtractive';

            $adjustInPurchase = (bool) $record->purchase_adjust_in_amount;
            $adjustInParty    = (bool) $record->purchase_adjust_in_party_amount;
            $postOverAbove    = (bool) $record->purchase_post_over_and_above;

            $purchaseLedger = $record->purchase_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->purchase_account_id
                : ($row['bill_sundry_modal_dr_id'] ?? null);

            $partyLedger = $record->purchase_party_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->purchase_party_account_id
                : ($row['bill_sundry_modal_cr_id'] ?? null);

            /**
             * Over & Above
             */
            if ($postOverAbove) {
                // Determine accounts
                $purchaseAccount = $purchaseLedger ?? $purchaseTypeDetail['account_id'];
                $partyAccount = $crAccount = $partyLedger ?? $masterData['account_id'];

                // For SUBTRACTIVE sundries (like TDS), reverse the entry
                // TDS reduces party liability and creates government liability
                if ($isSubtractive) {
                    // Dr. Party Account (reduce what we owe supplier)
                    $pushLine($lines['over_and_above'], $partyAccount, $value, 0, $purchaseAccount);
                    // Cr. TDS Account (government liability)
                    $pushLine($lines['over_and_above'], $purchaseAccount, 0, $value, $partyAccount);
                } else {
                    // For ADDITIVE sundries (like freight charges)
                    // Dr. Purchase/Expense Account
                    $pushLine($lines['over_and_above'], $purchaseAccount, $value, 0, $partyAccount);
                    // Cr. Party Account (increase what we owe)
                    $pushLine($lines['over_and_above'], $partyAccount, 0, $value, $purchaseAccount);
                }

                continue;
            }

            /**
             * Adjust both – amount only
             */
            if ($adjustInPurchase && $adjustInParty) {
                if ($isAdditive) {
                    $purchaseAmount += $value;
                    $supplierAmount += $value;
                } else {
                    $purchaseAmount -= $value;
                    $supplierAmount -= $value;
                }
                continue;
            }

            /**
             * Adjust Purchase only
             */
            if ($adjustInPurchase && !$adjustInParty) {
                $accountName = $partyLedger ?? $masterData['account_id'];

                if ($isAdditive) {
                    $purchaseAmount += $value;
                    $pushLine(
                        $lines['supplier'],
                        $accountName,
                        0,
                        $value,
                        $masterData['account_id']
                    );
                } else {
                    $purchaseAmount -= $value;
                    $pushLine(
                        $lines['purchase'],
                        $accountName,
                        $value,
                        0,
                        $masterData['account_id']
                    );
                }
                continue;
            }

            /**
             * Adjust Party only
             */
            if (!$adjustInPurchase && $adjustInParty) {
                $accountName = $purchaseLedger ?? $purchaseTypeDetail['account_id'];

                if ($isAdditive) {
                    $supplierAmount += $value;
                    $pushLine(
                        $lines['purchase'],
                        $accountName,
                        $value,
                        0,
                        $masterData['account_id']
                    );
                } else {
                    $supplierAmount -= $value;
                    $pushLine(
                        $lines['supplier'],
                        $accountName,
                        0,
                        $value,
                        $masterData['account_id']
                    );
                }
                continue;
            }
        }

        /**
         * Main purchase
         */
        $lines['purchase'][] = [
            'account_id'         => $purchaseTypeDetail['account_id'],
            'against_account_id' => $masterData['account_id'],
            'debit'              => $purchaseAmount,
            'credit'             => 0,
            'is_party_account'   => false,
        ];

        /**
         * Main party
         */
        $lines['supplier'][] = [
            'account_id'         => $masterData['account_id'],
            'against_account_id' => $purchaseTypeDetail['account_id'],
            'debit'              => 0,
            'credit'             => $supplierAmount,
            'is_party_account'   => true,
        ];

        /**
         * Validation
         */
        $totalDebit =
            array_sum(array_column($lines['purchase'], 'debit')) +
            array_sum(array_column($lines['over_and_above'], 'debit'));

        $totalCredit =
            array_sum(array_column($lines['supplier'], 'credit')) +
            array_sum(array_column($lines['over_and_above'], 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception(
                "Debit and Credit mismatch: {$totalDebit} != {$totalCredit}"
            );
        }

        /**
         * Flatten
         */
        $flatLines = [];
        foreach ($lines as $group) {
            $flatLines = array_merge($flatLines, $group);
        }

        return $flatLines;
    }




    public function prepareInvoicePayload(array $data, int $companyId, int $financialYearId, string $mode = 'create', ?int $invoiceId = null): array
    {
        // dd($data);

        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
        if (!$accountDetail) {
            throw ValidationException::withMessages([
                'purchase_type_id' => 'Invalid account for this company.'
            ]);
        }

        $purchaseTypeDetail = $this->lookupService->getPurchaseTypeDetails($companyId, $data['purchase_type_id']);
        if (!$purchaseTypeDetail) {
            throw ValidationException::withMessages([
                'purchase_type_id' => 'Invalid purchase type for this company.'
            ]);
        }

        $base = $this->prepareBaseInvoiceData($data, $companyId, $purchaseTypeDetail);
        

        if ($mode === 'create') {
            $serialInfo = $this->voucherService->getNextVoucherNumber(VoucherType::PURCHASE_INVOICE, $companyId, $financialYearId);

            $base = array_merge($base, [
                'uuid'                  => $data['uuid'],
                'company_id'            => $companyId,
                'financial_year_id'     => $financialYearId,
                'invoice_serial'        => $serialInfo->serial,
                'invoice_number'        => $serialInfo->voucher_number,
                'created_by'             => current_user_id()
            ]);
        } else {
            $base['updated_by']     = current_user_id();
        }

        if (empty($data['items'])) {
            return [$base, []];
        }

        $items = $this->prepareItemData($data['items'], $companyId, $purchaseTypeDetail);
        
        $totals = $this->calculateTotals($items);
        $billSundries = $mode === 'create' ? $this->prepareBillSundries(array_merge($base, $totals), $data['bill_sundries']) : $this->prepareBillSundries(array_merge($base, $totals), $data['bill_sundries'], true, $invoiceId);
        if ((float)$data['net_total'] != (float)$billSundries['net_total']) {
            throw ValidationException::withMessages([
                'net_total' => 'Net total does not match with bill sundries.'
            ]);
        }

        $totals['net_amount'] = $billSundries['net_total'];

        $totals['grand_total'] = $billSundries['grand_total'];

        return [
            array_merge($base, $totals),
            $items,
            $billSundries['rows'],
            $accountDetail,
            $purchaseTypeDetail,
            $billSundries['records'] ?? null,
        ];
    }

    protected function prepareBaseInvoiceData(array $data, int $companyId, array $purchaseTypeDetail): array
    {
        $grnInfo = $data['grn_id'] ? $this->grnRepo->getGrnSerialAndNumber($data['grn_id']) : [];

        return [

            'reference_number'      => $data['reference_number'],
            'invoice_date'          => $data['invoice_date'],
            'grn_id'                => $data['grn_id'] ?? null,
            'grn_number'            => $grnInfo['grn_number'] ?? null,
            'grn_serial'            => $grnInfo['grn_serial'] ?? null,
            'account_id'            => $data['account_id'],
            'purchase_type_id'      => $data['purchase_type_id'],
            'gst_type'              => $purchaseTypeDetail['region'],
            'broker_id'             => $data['broker_id'] ?? null,
            'show_date'             => $data['invoice_date'],
            'party_bill_date'       => $data['party_bill_date'] ?? null,
            'file_number'           => $data['file_number'],
            'sales_invoice_serial'  => $data['sales_invoice_serial'] ?? null,
            'vehicle_number'        => $data['vehicle_number'] ?? null,
            'remarks'               => $data['remarks'] ?? null,
            'rebate_from_analysis'  => isset($data['rebate_from_analysis']) ? $data['rebate_from_analysis'] : 0,
        ];
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

    protected function prepareBillSundries(array $base, array $billSundryItems, bool $isUpdate = false, ?int $invoiceId = null): array
    {
        if (empty($billSundryItems)) {
            return [
                'rows'        => [],
                'net_total'   => $base['taxable_amount'],
                'grand_total' => $base['taxable_amount'],
                'records'     => collect(),
            ];
        }



        $billSundryIds = array_column($billSundryItems, 'bill_sundry_id');

        $billSundryRecords = $this->billSundryRepo->query()
            ->whereIn('id', $billSundryIds)
            ->get()
            ->keyBy('id');
        // dd($billSundryItems, $billSundryRecords->toArray());

        $baseAmount     = (float) $base['taxable_amount'];

        // dd($baseAmount);

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
            // dd($billSundry);
            if ($invoiceId && $isUpdate && $record->code === '1009') {
                $originalBillSundry = PurchaseInvoiceSundry::where('purchase_invoice_id', $invoiceId)
                    ->where('sundry_id', $record->id)
                    ->first();
                
                if ((float)$originalBillSundry->value == (float)$billSundry['bill_sundry_value']) {
                    $amount = $originalBillSundry->amount; // Nature amount like positive and negative
                    $value  = $originalBillSundry->value;
                    $rate  = $originalBillSundry->rate_percent;
                    $baseForCalc  = $originalBillSundry->base_amount;
                } else {
                    $shouldRoundUp = (bool) ($record->bill_sundry_amount_round_off ?? false);

                    if ($calcType === 'percentage') {
                        $rawValue = ($baseForCalc * $rate) / 100;
                    } else {
                        $rawValue = (float) ($billSundry['bill_sundry_value'] ?? 0);
                    }
                    $value = $shouldRoundUp ? round($rawValue) : round($rawValue, 2);

                    $amount = $record->bill_sundry_type === 'subtractive'
                        ? -abs($value)
                        : abs($value);
                }

            } else {
                // ---------- CALCULATION ----------
                $shouldRoundUp = (bool) ($record->bill_sundry_amount_round_off ?? false);

                if ($calcType === 'percentage') {
                    $rawValue = ($baseForCalc * $rate) / 100;
                } else {
                    // fixed
                    $rawValue = (float) ($billSundry['bill_sundry_value'] ?? 0);
                }
                $value = $shouldRoundUp ? round($rawValue) : round($rawValue, 2);

                // ---------- ADDITION / DEDUCTION ----------
                $amount = $record->bill_sundry_type === 'subtractive'
                    ? -abs($value)
                    : abs($value);
            }
            // Update for next iteration
            $previousAmount = $amount;
            $runningTotal  += $amount;
            $isAdjustInPartyAmount = (bool) $record->purchase_adjust_in_party_amount ?? false;

            // ---------- FINAL OUTPUT ROW ----------
            $results[] = [
                'sundry_id'               => $record->id,
                'code'                    => $record->code,
                'name'                    => $record->name,
                'account_id'              => $record->purchase_account_type == BillSundry::SPECIFY_ACCOUNT ? $record->purchase_account_id : null,

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
                // 'affect_grand_total'      => $record->affect_grand_total,
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
            'records'     => $billSundryRecords,
        ];
    }

    protected function prepareItemData(array $items, int $companyId, array $purchaseTypeData): array
    {
        return collect($items)->map(function ($item) use ($companyId, $purchaseTypeData) {

            [$cgstPercent, $sgstPercent, $igstPercent] = match ($purchaseTypeData['region']) {
                PurchaseInvoice::TAX_LOCAL => [$purchaseTypeData['cgst'], $purchaseTypeData['sgst'], 0],
                default        => [0, 0, $purchaseTypeData['igst']],
            };

            $qty    = (float) ($item['quantity'] ?? 0);
            $rate   = (float) ($item['rate'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);

            $taxableAmount = (float)($item['rate'] ?? 0) * (float)($item['quantity'] ?? 0);

            $cgstAmount = round($taxableAmount * ($cgstPercent / 100), 2);
            $sgstAmount = round($taxableAmount * ($sgstPercent / 100), 2);
            $igstAmount = round($taxableAmount * ($igstPercent / 100), 2);

            $taxAmount = round($cgstAmount + $sgstAmount + $igstAmount, 2);
            $netAmount = round($taxableAmount + $taxAmount, 2);

            return [
                'item_id'                   => $item['item_id'],
                'quantity'                  => $qty,
                'party_quantity'            => $item['party_quantity'] ?? 0,
                'rate'                      => $rate ?? 0,
                'inclusive_rate'            => $item['inclusive_rate'] ?? 0,
                'tax_amount'                => $taxAmount ?? 0,
                'amount'                    => $amount,
                'net_amount'                => $netAmount ?? 0,
                'cgst_rate'                 => $cgstPercent,
                'sgst_rate'                 => $sgstPercent,
                'igst_rate'                 => $igstPercent,
                'bag_count'                 => $item['bag_count'] ?? 0,
                'cgst_amount'               => $cgstAmount,
                'sgst_amount'               => $sgstAmount,
                'igst_amount'               => $igstAmount,
                'taxable_amount'            => $taxableAmount,
                'condition_id'              => $item['condition_id'] ?? null,
                'destination_id'            => $item['destination_id'] ?? null,
                'purchase_order_serial'     => $item['purchase_order_serial'] ?? null,
                'purchase_order_id'         => $item['purchase_order_id'] ?? null,
                'purchase_order_item_id'  => $item['purchase_order_item_id'] ?? null,
            ];
        })->toArray();
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId)
    {
        return $this->voucherService->getNextVoucherNumber(VoucherType::PURCHASE_INVOICE, $companyId, $financialYearId);
    }
    /* -----------------------------------------
     | List (Data Grid)
     |------------------------------------------
     */
    public function purchaseInvoiceList(int $companyId, int $financialYearId, ?array $filters = []): array
    {
        $paginator = $this->purchaseInvoiceRepo->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'purchase_invoice.view',
            'purchase_invoice.update',
            // 'grn.delete',
        ], true);

        $filteredTotal = $this->purchaseInvoiceRepo->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->purchaseInvoiceRepo->countAll($companyId, $financialYearId);

        return [
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $filters['size']),
            'current_page' => $filters['page'],
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions
        ];
    }
    /* ------------------------------------------
     | List for Printing and Exporting
     |-------------------------------------------
    */
    public function purchaseInvoiceListAll(int $companyId, int $financialYearId, ?array $filters = []): Collection
    {
        return $this->purchaseInvoiceRepo->listAll($companyId, $financialYearId, $filters);
    }

    public function getPurchaseInvoiceSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->purchaseInvoiceRepo->getPurchaseInvoiceSerial($companyId, $financialYearId);
    }

    public function getViewData(int $purchaseInvoiceId, int $companyId, int $financialYearId): ?PurchaseInvoice
    {
        return $this->purchaseInvoiceRepo->getViewData($purchaseInvoiceId, $companyId, $financialYearId);
    }


    public function isDuplicateReference(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $invoiceId = null): bool
    {
        return $this->purchaseInvoiceRepo->existsByReferenceNumberAndAccount(
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
        int $accountId,
        ?int $excludeId = null
    ): ?int {

        $record = $this->purchaseInvoiceRepo->findByReferenceNumberAndAccount(
            filters: [
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'reference_number'  => $referenceNumber,
                'account_id'        => $accountId,
            ],
            excludeId: $excludeId
        );

        return $record?->id;
    }

    public function getEditData(int $invoiceId, int $companyId, int $financialYearId): ?PurchaseInvoice
    {
        return $this->purchaseInvoiceRepo->getEditData($invoiceId, $companyId, $financialYearId);
    }

    public function getInvoiceSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->purchaseInvoiceRepo->getInvoiceSerial($companyId, $financialYearId);
    }


    public function getGrnSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->purchaseInvoiceRepo->getGrnSerial($companyId, $financialYearId);
    }


    public function getGrnDetails(int $grnId): ?Grn
    {
        return $this->grnRepo->getGrnDetails($grnId);
    }

    private function updatePurchasePaymentStatus(PurchaseInvoice $invoice)
    {
        $total     = $invoice->net_amount;
        $settled   = $invoice->references()->sum('settled_amount');

        if ($settled <= 0) {
            $status = 'unpaid';
        } elseif ($settled < $total) {
            $status = 'partially_paid';
        } elseif ($settled == $total) {
            $status = 'fully_paid';
        } else {
            $status = 'overpaid';
        }

        $invoice->update([
            'payment_status' => $status
        ]);
    }

    public function getSupplierTurnOver(int $supplierId, int $companyId, int $financialYearId)
    {
        return PurchaseInvoice::where('account_id', $supplierId)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->sum('net_amount') ?? 0;
    }

    /*--------------------------------------------------------------
    | AUDIT HELPERS
    --------------------------------------------------------------*/

    /**
     * Snapshot the current invoice state into a prefix-keyed array that
     * AuditService::log() section-detection understands:
     *
     *  (no prefix)         → SECTION_MASTER       (1)  invoice header fields
     *  'details.'  prefix  → SECTION_VOUCHER_DETAILS (3) accounting debit/credit lines
     *  'items.'    prefix  → SECTION_ITEM_ENTRIES  (6)  product line items
     *  'bill_sundries.' prefix → SECTION_BILL_SUNDRIES (4) sundry rows
     */
    public function buildAuditValues(PurchaseInvoice $invoice): array
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

    public function logAudit(
        array           $data,
        PurchaseInvoice $invoice,
        array           $voucher,
        array           $voucherLines,
        string          $action
    ): mixed {
        if (!isAuditLog()) {
            return null;
        }

        $oldValues = $data['old_values'] ?? [];
        $newValues = [];

        if ($action === AuditTrail::ACTION_CREATE || $action === AuditTrail::ACTION_UPDATE) {
            if (!$invoice->relationLoaded('details') || !$invoice->relationLoaded('billSundries')) {
                $invoice->load('details', 'billSundries');
            }
            $newValues = $this->prepareAuditData($invoice, $voucher, $voucherLines, $action);
        }
        // For DELETE: newValues stay empty — old snapshot shows what was removed.

        // On UPDATE, skip logging if nothing actually changed
        if ($action === AuditTrail::ACTION_UPDATE) {
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

        // org_amount / final_amount: sum keys matching 'items.N.amount'
        $orgAmount   = (float) collect($oldValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();
        $finalAmount = (float) collect($newValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();

        return $this->auditService->log([
            'company_id'        => $invoice->company_id,
            'financial_year_id' => $invoice->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::PURCHASE,
            'record_type'       => AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => PurchaseInvoice::class,
            'source_id'         => $invoice->id,
            'voucher_id'        => $invoice->voucher_id,
            'reference_number'  => $invoice->reference_number,
            'org_amount'        => $orgAmount,
            'final_amount'      => $finalAmount,
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ]);
    }

    /**
     * Build the new_values array for AuditService::log().
     *
     * Key prefixes drive section assignment inside AuditService:
     *   (top-level)        → SECTION_MASTER (1)          — invoice header
     *   'details.'         → SECTION_VOUCHER_DETAILS (3) — accounting debit/credit lines
     *   'items.'           → SECTION_ITEM_ENTRIES (6)    — product line items
     *   'bill_sundries.'   → SECTION_BILL_SUNDRIES (4)   — bill sundry rows
     */
    public function prepareAuditData(
        PurchaseInvoice $invoice,
        array           $voucher,
        array           $voucherLines,
        string          $action
    ): array {
        $invoice->loadMissing([
            'details.destination',
            'details.condition',
            'details.item.unit',
            'billSundries',
            'purchaseType',
            'account',
            'broker',
        ]);

        $accountIds = collect($voucherLines)->pluck('account_id')->filter()->toArray();
        $accountsMap = !empty($accountIds)
            ? \App\Models\Account::whereIn('id', array_unique($accountIds))->pluck('name', 'id')
            : collect();

        $data = [];

        // ── SECTION_MASTER (1) — invoice header fields ────────────────────
        // Top-level scalar keys → AuditService assigns SECTION_MASTER
        $data['voucher_no']         = $invoice->invoice_serial;
        $data['reference_number']   = $invoice->reference_number;
        $data['invoice_date']       = $invoice->invoice_date instanceof \Carbon\Carbon
            ? $invoice->invoice_date->format('Y-m-d')
            : substr((string)$invoice->invoice_date, 0, 10);
        $data['account_id']         = $invoice->account_id;
        $data['account_name']       = $invoice->account->name ?? '';
        $data['party_bill_date'] = $invoice->party_bill_date instanceof \Carbon\Carbon
            ? $invoice->party_bill_date->format('Y-m-d')
            : substr((string)$invoice->party_bill_date, 0, 10);
        $data['purchase_type_id']   = $invoice->purchase_type_id;
        $data['purchase_type_name'] = $invoice->purchaseType->name ?? '';
        $data['broker_id']          = $invoice->broker_id;
        $data['broker_name']        = $invoice->broker->name ?? '';
        $data['grn_id']             = $invoice->grn_id;
        $data['file_number']        = $invoice->file_number;
        $data['sales_invoice_serial'] = $invoice->sales_invoice_serial;
        $data['vehicle_number']     = $invoice->vehicle_number;
        $data['taxable_amount']   = number_format((float) $invoice->taxable_amount, 2, '.', '');
        $data['tax_amount']       = number_format((float) $invoice->tax_amount,     2, '.', '');
        $data['net_amount']       = number_format((float) $invoice->net_amount,     2, '.', '');
        $data['grand_total']      = number_format((float) $invoice->grand_total,    2, '.', '');
        $data['remarks']          = $invoice->remarks;

        // ── SECTION_VOUCHER_DETAILS (3) — accounting debit/credit lines ───
        // Pre-dotted keys 'details.N.field' → AuditService assigns SECTION_VOUCHER_DETAILS
        // Each line stored as individual scalar rows — no nested arrays.
        foreach (array_values($voucherLines) as $i => $line) {
            $data["details.{$i}.account_id"]   = $line['account_id'] ?? null;
            if (isset($line['account_id'])) {
                $data["details.{$i}.account_name"] = $accountsMap[$line['account_id']] ?? '';
            }
            $data["details.{$i}.debit"]        = number_format((float) ($line['debit']  ?? 0), 2, '.', '');
            $data["details.{$i}.credit"]       = number_format((float) ($line['credit'] ?? 0), 2, '.', '');
        }

        // ── SECTION_ITEM_ENTRIES (6) — product line items ─────────────────
        // Pre-dotted keys 'items.N.field' → AuditService assigns SECTION_ITEM_ENTRIES
        foreach ($invoice->details->values() as $i => $d) {
            $item = $d->item;
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
            $data["items.{$i}.order_no"]         = $d->purchase_order_serial;

            $data["items.{$i}.party_quantity"] = number_format((float) $d->party_quantity, 4, '.', '');
            $data["items.{$i}.quantity"]       = number_format((float) $d->quantity, 4, '.', '');
            $data["items.{$i}.rate"]           = number_format((float) $d->rate,     4, '.', '');
            $data["items.{$i}.inclusive_rate"] = number_format((float) $d->inclusive_rate, 4, '.', '');
            $data["items.{$i}.amount"]         = number_format((float) $d->amount,   2, '.', '');
        }

        // ── SECTION_BILL_SUNDRIES (4) — sundry charge rows ────────────────
        // Pre-dotted keys 'bill_sundries.N.field' → AuditService assigns SECTION_BILL_SUNDRIES
        // Only sundries with non-zero values are stored to keep the log clean.
        $si = 0;
        foreach ($invoice->billSundries as $s) {
            if ((float) $s->value == 0 && (float) $s->amount == 0) {
                continue;
            }
            $data["bill_sundries.{$si}.sundry_id"] = $s->sundry_id;
            $data["bill_sundries.{$si}.name"]      = $s->name;
            $data["bill_sundries.{$si}.value"]     = number_format((float) $s->value,  2, '.', '');
            $data["bill_sundries.{$si}.amount"] = number_format((float) $s->amount, 2, '.', '');
            $si++;
        }

        return $data;
    }
}

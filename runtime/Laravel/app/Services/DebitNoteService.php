<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\BillSundry;
use App\Models\DebitNote;
use App\Models\Reference;
use App\Models\PurchaseInvoice;
use App\Models\VoucherType;
use App\Models\PurchaseType;
use App\Repositories\BillSundryRepository;
use App\Repositories\DebitNoteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebitNoteService
{
    protected VoucherService $voucherService;
    protected DebitNoteRepository $debitNoteRepository;
    protected LookupService $lookupService;
    protected BillSundryRepository $billSundryRepo;
    protected StockVoucherService $stockVoucherService;
    protected ReferenceService $referenceService;
    protected GstEntryService $gstEntryService;

    public function __construct(
        VoucherService $voucherService,
        DebitNoteRepository $debitNoteRepository,
        LookupService $lookupService,
        BillSundryRepository $billSundryRepo,
        StockVoucherService $stockVoucherService,
        ReferenceService $referenceService,
        GstEntryService $gstEntryService
    ) {
        $this->voucherService       = $voucherService;
        $this->debitNoteRepository  = $debitNoteRepository;
        $this->lookupService        = $lookupService;
        $this->billSundryRepo       = $billSundryRepo;
        $this->stockVoucherService  = $stockVoucherService;
        $this->referenceService     = $referenceService;
        $this->gstEntryService      = $gstEntryService;
    }

    /* -------------------------------------------------------
     | CREATE
     | ------------------------------------------------------ */
    public function createDebitNote(array $data, int $companyId, int $financialYearId): DebitNote
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            [$masterData, $items, $billSundry] = $this->prepareDebitNoteData($data, $companyId, $financialYearId, 'create');

            $debitNote = $this->debitNoteRepository->create($masterData);

            foreach ($items as $item) {
                $debitNote->details()->create($item);
            }
            foreach ($billSundry as $sundry) {
                $debitNote->billSundries()->create($sundry);
            }

            $voucherMaster = $this->prepareVoucherMaster($debitNote);
            $voucherLines  = $this->prepareVoucherLines($companyId, $financialYearId, $masterData, $items, $billSundry);
            $voucher       = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $debitNote->update(['voucher_id' => $voucher->id]);

            // Bill-wise reference: reduce outstanding on original invoice
            $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
            
            if ($accountDetail && $accountDetail['is_billwise']) {
                $referenceData = $this->prepareBillWiseReference($debitNote, $voucher);
                $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
            }

            $debitNote->load('details', 'billSundries');

            // Stock reversal: goods go back out
            $this->createOrUpdateStockVoucher($companyId, $debitNote, $voucher);

            // GST entry
            $this->gstEntryService->storeForDebitNote($debitNote, $voucher);

            return $debitNote;
        });
    }

    /* -------------------------------------------------------
     | UPDATE
     | ------------------------------------------------------ */
    public function updateDebitNote(int $debitNoteId, array $data, int $companyId, int $financialYearId): DebitNote
    {
        return DB::transaction(function () use ($debitNoteId, $data, $companyId, $financialYearId) {
            $debitNote = $this->debitNoteRepository->find($debitNoteId);
            if (!$debitNote) {
                throw new \Exception('Debit note not found.');
            }

            $oldRefExists = Reference::where('voucher_id', $debitNote->voucher_id)->first();
            if ($oldRefExists && $oldRefExists->settled_amount > 0) {
                throw new \Exception("Cannot edit. Debit Note is already paid or settled.");
            }

            [$masterData, $items, $billSundry] = $this->prepareDebitNoteData($data, $companyId, $financialYearId, 'update');

            $this->debitNoteRepository->update($debitNote, $masterData);

            $debitNote->details()->delete();
            $debitNote->details()->createMany($items);
            $debitNote->billSundries()->delete();
            $debitNote->billSundries()->createMany($billSundry);
            $debitNote->save();

            $voucherMaster = $this->prepareVoucherMaster($debitNote);
            $voucherLines  = $this->prepareVoucherLines($companyId, $financialYearId, $masterData, $items, $billSundry);
            $voucher       = $this->voucherService->updateVoucher($voucherMaster, $voucherLines, $debitNote->voucher_id);

            // Update bill-wise reference amount
            $oldRef = Reference::where('voucher_id', $voucher->id)->first();
            if ($oldRef) {
                $oldRef->reference_date  = $debitNote->debit_note_date;
                $oldRef->reference_number = $debitNote->reference_number;
                $oldRef->file_number      = 0;
                $oldRef->account_id      = $debitNote->account_id;
                $oldRef->amount          = $debitNote->net_amount;
                $oldRef->pending_amount  = $oldRef->amount - $oldRef->settled_amount;
                $oldRef->is_closed       = $oldRef->pending_amount == 0;
                $oldRef->closed_at       = $oldRef->is_closed ? Carbon::now() : null;
                $oldRef->updated_by      = current_user_id();
                $oldRef->updated_at      = Carbon::now();
                $oldRef->save();
            } else {
                $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
                if ($accountDetail && $accountDetail['is_billwise']) {
                    $referenceData = $this->prepareBillWiseReference($debitNote, $voucher);
                    $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
                }
            }

            $debitNote->load('details', 'billSundries');
            $this->createOrUpdateStockVoucher($companyId, $debitNote, $voucher);
            $this->gstEntryService->updateForDebitNote($debitNote, $voucher);

            return $debitNote;
        });
    }

    /* -------------------------------------------------------
     | DELETE
     | ------------------------------------------------------ */
    public function deleteDebitNote(int $debitNoteId): void
    {
        DB::transaction(function () use ($debitNoteId) {
            $debitNote = $this->debitNoteRepository->find($debitNoteId);
            if (!$debitNote) {
                throw new \Exception('Debit note not found.');
            }

            $ref = Reference::where('voucher_id', $debitNote->voucher_id)->first();
            if ($ref && $ref->settled_amount > 0) {
                throw new \Exception('Cannot delete. Debit Note is already paid or settled.');
            }

            $userId = current_user_id();

            $debitNote->deleted_by = $userId;
            $debitNote->save();

            if ($debitNote->voucher_id) {
                $voucher = \App\Models\Voucher::find($debitNote->voucher_id);
                if ($voucher) {
                    $voucher->deleted_by = $userId;
                    $voucher->save();
                    $voucher->delete();
                }

                if ($ref) {
                    $ref->deleted_by = $userId;
                    $ref->save();
                    $ref->delete();
                }
            }

            $debitNote->delete();
        });
    }

    /* -------------------------------------------------------
     | DATA PREPARATION
     | ------------------------------------------------------ */
    public function prepareDebitNoteData(array $data, int $companyId, int $financialYearId, string $mode = 'create'): array
    {
        $purchaseTypeDetail = $this->lookupService->getPurchaseTypeDetails($companyId, $data['purchase_type_id']);
        if (!$purchaseTypeDetail) {
            throw new ValidationException(
                validator([], ['purchase_type_id' => 'required']),
            );
        }

        $base = $this->prepareBaseData($data, $companyId, $purchaseTypeDetail);

        if ($mode === 'create') {
            $serialInfo = $this->voucherService->getNextVoucherNumber(VoucherType::PURCHASE_RETURN, $companyId, $financialYearId);
            $base = array_merge($base, [
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'debit_note_serial' => $serialInfo->serial,
                'debit_note_number' => $serialInfo->voucher_number,
                'created_by'         => current_user_id(),
            ]);
        } else {
            $base = array_merge($base, [
                'updated_by' => current_user_id(),
            ]);
        }

        if (empty($data['items'])) {
            return [$base, [], []];
        }

        $items       = $this->prepareItemData($data['items'], $companyId, $purchaseTypeDetail);
        $totals      = $this->calculateTotals($items);
        $billSundries = $this->prepareBillSundries(array_merge($base, $totals), $data['bill_sundries'] ?? []);

        $totals['net_amount']   = $billSundries['net_total'];
        $totals['grand_total']  = $billSundries['grand_total'];

        return [array_merge($base, $totals), $items, $billSundries['rows']];
    }

    protected function prepareBaseData(array $data, int $companyId, array $purchaseTypeDetail): array
    {
        return [
            'debit_note_date'     => $data['debit_note_date'],
            'reference_number'     => $data['reference_number'] ?? null,
            'account_id'           => $data['account_id'],
            'purchase_type_id'         => $data['purchase_type_id'],
            'gst_type'             => $purchaseTypeDetail['region'],
            'purchase_invoice_id'     => $data['purchase_invoice_id'] ?? null,
            'purchase_invoice_serial' => $data['purchase_invoice_serial'] ?? null,
            'remarks'              => $data['remarks'] ?? null,
        ];
    }

    /* -------------------------------------------------------
     | VOUCHER MASTER
     | ------------------------------------------------------ */
    private function prepareVoucherMaster(DebitNote $debitNote): array
    {
        return [
            'uuid'              => $debitNote->uuid,
            'company_id'        => $debitNote->company_id,
            'financial_year_id' => $debitNote->financial_year_id,
            'voucher_date'      => $debitNote->debit_note_date,
            'voucher_type_id'   => VoucherType::PURCHASE_RETURN,
            'source_id'         => $debitNote->id,
            'source_type'       => SourceType::PURCHASE_RETURN,
            'reference_number'  => $debitNote->reference_number,
            'voucher_serial'    => $debitNote->debit_note_serial,
            'voucher_number'    => $debitNote->debit_note_number,
            'narration'         => $debitNote->remarks,
        ];
    }

    /* -------------------------------------------------------
     | VOUCHER LINES — DEBIT NOTE ACCOUNTING
     |
     | Purchase Invoice (forward):  Purchase DR, Supplier CR
     | Debit Note (reverse):        Supplier DR, Purchase Returns CR
     | ------------------------------------------------------ */
    private function prepareVoucherLines(
        int $companyId,
        int $financialYearId,
        array $masterData,
        array $items,
        array $billSundry
    ): array {
        $lines = [
            'returns'        => [],   // credit side (purchase returns)
            'party'          => [],   // debit side (supplier)
            'over_and_above' => [],
        ];

        $returnsAmount = array_sum(array_column($items, 'amount'));
        $partyAmount   = $returnsAmount;

        // Bill sundry master records
        $billSundryIds     = array_column($billSundry, 'sundry_id');
        $billSundryRecords = $this->billSundryRepo->query()
            ->whereIn('id', $billSundryIds)
            ->get()
            ->keyBy('id');

        // Purchase type → purchase/returns account
        $purchaseTypeDetail = $this->lookupService->getPurchaseTypeDetails($companyId, $masterData['purchase_type_id']);

        $isPartyLedger = fn($accountId) => (int) $accountId === (int) $masterData['account_id'];

        // Helper to push a voucher line
        $pushLine = function (&$group, $accountId, $debit, $credit, $againstAccountId = null) use ($isPartyLedger) {
            $group[] = [
                'account_id'         => $accountId,
                'against_account_id' => $againstAccountId,
                'debit'              => $debit,
                'credit'             => $credit,
                'is_party_account'   => $isPartyLedger($accountId),
            ];
        };

        /* --------------------------------------------------
         | Bill Sundry Processing
         | In a Debit Note all DR/CR are the mirror of Purchase Invoice
         | -------------------------------------------------- */
        foreach ($billSundry as $row) {
            $record = $billSundryRecords->get($row['sundry_id']);
            if (!$record) continue;

            $value = (float) $row['value'];
            if ($value == 0) continue;

            $isAdditive    = in_array($record->bill_sundry_type, [BillSundry::ADDICTIVE, 'additive']);
            $isSubtractive = in_array($record->bill_sundry_type, [BillSundry::SUBTRACTIVE, 'subtractive']);

            $adjustInPurchase  = (bool) $record->purchase_adjust_in_amount;
            $adjustInParty  = (bool) $record->purchase_adjust_in_party_amount;
            $postOverAbove  = (bool) $record->purchase_post_over_and_above;

            $returnsLedger = $record->purchase_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->purchase_account_id
                : ($row['bill_sundry_modal_cr_id'] ?? $purchaseTypeDetail['account_id']); // Usually credit returns

            $partyLedger = $record->purchase_party_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->purchase_party_account_id
                : ($row['bill_sundry_modal_dr_id'] ?? $masterData['account_id']);

            /* --------------------------------------------------
             | Over & Above — reversed from Purchase Invoice
             | -------------------------------------------------- */
            if ($postOverAbove) {
                if ($isSubtractive) {
                    // Purchase Invoice had: partyLedger DR, returnsLedger CR
                    // Debit Note reverses: returnsLedger DR, partyLedger CR
                    $pushLine($lines['over_and_above'], $returnsLedger,  $value, 0,      $partyLedger);
                    $pushLine($lines['over_and_above'], $partyLedger,    0,      $value,  $returnsLedger);
                } else {
                    // Purchase Invoice had: returnsLedger DR, partyLedger CR
                    // Debit Note reverses: partyLedger DR, returnsLedger CR
                    $pushLine($lines['over_and_above'], $partyLedger,    $value, 0,      $returnsLedger);
                    $pushLine($lines['over_and_above'], $returnsLedger,  0,      $value,  $partyLedger);
                }
                continue;
            }

            /* Adjust in BOTH — just affects amounts */
            if ($adjustInPurchase && $adjustInParty) {
                $returnsAmount += $isAdditive ? $value : -$value;
                $partyAmount   += $isAdditive ? $value : -$value;
                continue;
            }

            /* Adjust RETURNS only */
            if ($adjustInPurchase && !$adjustInParty) {
                $returnsAmount += $isAdditive ? $value : -$value;
                $accountName    = $partyLedger ?? $masterData['account_id'];
                if ($isAdditive) {
                    // Purchase invoice additive normally debits returns
                    // Reversing: credit returns (handled by amount diff), debit party
                    $pushLine($lines['party'], $accountName, $value, 0, $masterData['account_id']);
                } else {
                    $pushLine($lines['returns'], $accountName, 0, $value, $masterData['account_id']);
                }
                continue;
            }

            /* Adjust PARTY only */
            if (!$adjustInPurchase && $adjustInParty) {
                $partyAmount   += $isAdditive ? $value : -$value;
                $accountName    = $returnsLedger ?? $purchaseTypeDetail['account_id'];
                if ($isAdditive) {
                    // Purchase invoice additive normally credits party
                    // Reversing: debit party (handled by amount diff), credit returns
                    $pushLine($lines['returns'], $accountName, 0, $value, $masterData['account_id']);
                } else {
                    $pushLine($lines['party'], $accountName, $value, 0, $masterData['account_id']);
                }
            }
        }

        /* --------------------------------------------------
         | Main Party / Supplier entry (DR)
         | -------------------------------------------------- */
        $lines['party'][] = [
            'account_id'         => $masterData['account_id'],
            'against_account_id' => $purchaseTypeDetail['account_id'],
            'debit'              => round($partyAmount, 2),
            'credit'             => 0,
            'is_party_account'   => true,
        ];

        /* --------------------------------------------------
         | Main Purchase Returns entry (CR)
         | -------------------------------------------------- */
        $lines['returns'][] = [
            'account_id'         => $purchaseTypeDetail['account_id'],
            'against_account_id' => $masterData['account_id'],
            'debit'              => 0,
            'credit'             => round($returnsAmount, 2),
            'is_party_account'   => false,
        ];

        /* Validate debit == credit */
        $totalDebit  = array_sum(array_column($lines['returns'], 'debit'))
                     + array_sum(array_column($lines['party'], 'debit'))
                     + array_sum(array_column($lines['over_and_above'], 'debit'));

        $totalCredit = array_sum(array_column($lines['party'], 'credit'))
                     + array_sum(array_column($lines['returns'], 'credit'))
                     + array_sum(array_column($lines['over_and_above'], 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception("Debit/Credit mismatch in Debit Note: {$totalDebit} != {$totalCredit}");
        }

        $flat = [];
        foreach ($lines as $group) {
            $flat = array_merge($flat, $group);
        }
        return $flat;
    }

    /* -------------------------------------------------------
     | STOCK VOUCHER — goods returned (stock OUT)
     | ------------------------------------------------------ */
    private function createOrUpdateStockVoucher(int $companyId, DebitNote $debitNote, $voucher): void
    {
        $stockTransactions = [];

        foreach ($debitNote->details as $detail) {
            if ($detail->quantity <= 0) continue;

            $itemDetail = $this->lookupService->getItemDetails($companyId, $detail->item_id);
            if (!$itemDetail['is_maintain_stock_balance']) continue;

            $stockTransactions[] = [
                'item_id' => $detail->item_id,
                'in_qty'  => 0,
                'out_qty' => $detail->quantity,   // stock goes OUT
                'rate'    => $detail->rate,
                'amount'  => $detail->amount,
            ];
        }

        $stockMaster = [
            'voucher_id'        => $voucher->id,
            'company_id'        => $debitNote->company_id,
            'financial_year_id' => $debitNote->financial_year_id,
            'voucher_type_id'   => VoucherType::PURCHASE_RETURN,
            'voucher_number'    => $debitNote->debit_note_number,
            'voucher_serial'    => $debitNote->debit_note_serial,
            'reference_number'  => $debitNote->reference_number,
            'voucher_date'      => $debitNote->debit_note_date,
        ];

        $this->stockVoucherService->upsertStockVoucher($stockMaster, $stockTransactions);
    }

    /* -------------------------------------------------------
     | BILL-WISE REFERENCE (reduces party outstanding)
     | ------------------------------------------------------ */
    private function prepareBillWiseReference(DebitNote $debitNote, $voucher): array
    {
        return [
            'reference_number' => $debitNote->reference_number,
            'reference_date'   => $debitNote->debit_note_date,
            'parent_voucher_id'=> $voucher->id,
            'reference_type'   => 'new_ref',
            'amount'           => $debitNote->net_amount,
            'direction'        => config('ref_direction_map.direction.' . SourceType::PURCHASE_RETURN),
            'account_id'       => $debitNote->account_id,
            'source_type'      => SourceType::PURCHASE_RETURN,
            'source_id'        => $debitNote->id,
            'voucher_id'       => $voucher->id,
            'pending_amount'   => $debitNote->net_amount,
            'file_number'      => 0,
        ];
    }

    /* -------------------------------------------------------
     | ITEM DATA
     | ------------------------------------------------------ */
    protected function prepareItemData(array $items, int $companyId, array $purchaseTypeData): array
    {
        return collect($items)->map(function ($item) use ($purchaseTypeData) {
            [$cgstPercent, $sgstPercent, $igstPercent] = match ($purchaseTypeData['region']) {
                DebitNote::TAX_LOCAL => [$purchaseTypeData['cgst'], $purchaseTypeData['sgst'], 0],
                default               => [0, 0, $purchaseTypeData['igst']],
            };

            $taxableAmount = (float) ($item['rate'] ?? 0) * (float) ($item['quantity'] ?? 0);
            $cgstAmount    = round($taxableAmount * ($cgstPercent / 100), 2);
            $sgstAmount    = round($taxableAmount * ($sgstPercent / 100), 2);
            $igstAmount    = round($taxableAmount * ($igstPercent / 100), 2);
            $taxAmount     = round($cgstAmount + $sgstAmount + $igstAmount, 2);
            $netAmount     = round($taxableAmount + $taxAmount, 2);

            return [
                'item_id'        => $item['item_id'],
                'quantity'       => $item['quantity']        ?? 0,
                'rate'           => $item['rate']            ?? 0,
                'inclusive_rate' => $item['inclusive_rate']  ?? 0,
                'amount'         => $item['amount']          ?? 0,
                'net_amount'     => $netAmount,
                'tax_amount'     => $taxAmount,
                'taxable_amount' => $taxableAmount,
                'cgst_rate'      => $cgstPercent,
                'sgst_rate'      => $sgstPercent,
                'igst_rate'      => $igstPercent,
                'cgst_amount'    => $cgstAmount,
                'sgst_amount'    => $sgstAmount,
                'igst_amount'    => $igstAmount,
                'bag_count'      => $item['bag_count']       ?? 0,
                'condition_id'   => $item['condition_id']    ?? null,
                'destination_id' => $item['destination_id']  ?? null,
            ];
        })->toArray();
    }

    protected function calculateTotals(array $items): array
    {
        return [
            'total_quantity' => array_sum(array_column($items, 'quantity')),
            'taxable_amount' => array_sum(array_column($items, 'amount')),
            'tax_amount'     => array_sum(array_column($items, 'tax_amount')),
            'net_amount'     => 0,
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

        $billSundryIds     = array_column($billSundryItems, 'bill_sundry_id');
        $billSundryRecords = $this->billSundryRepo->query()
            ->whereIn('id', $billSundryIds)
            ->get()
            ->keyBy('id');

        $baseAmount     = (float) $base['taxable_amount'];
        $runningTotal   = $baseAmount;
        $previousAmount = 0;
        $netTotal       = $baseAmount;
        $grandTotal     = $baseAmount;
        $results        = [];

        usort($billSundryItems, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        foreach ($billSundryItems as $index => $billSundry) {
            $record = $billSundryRecords->get($billSundry['bill_sundry_id']);
            if (!$record) continue;

            $rate     = (float) ($billSundry['bill_sundry_percentage'] ?? 0);
            $calcType = $record->calculation_type;
            $applyOn  = $record->apply_on;

            $baseForCalc = match ($applyOn) {
                'basic'         => $baseAmount,
                'running_total' => $runningTotal,
                'previous_row'  => $previousAmount,
                'grand_total'   => $runningTotal,
                default         => $baseAmount,
            };

            $shouldRoundUp = (bool) ($record->bill_sundry_amount_round_off ?? false);
            $rawValue      = $calcType === 'percentage' ? ($baseForCalc * $rate) / 100 : (float) ($billSundry['bill_sundry_value'] ?? 0);
            $value         = $shouldRoundUp ? round($rawValue) : round($rawValue, 2);

            $amount         = $record->bill_sundry_type === 'subtractive' ? -abs($value) : abs($value);
            $previousAmount = $amount;
            $runningTotal  += $amount;

            $isAdjustInPartyAmount = (bool) ($record->purchase_adjust_in_party_amount ?? false);

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

    /* -------------------------------------------------------
     | QUERY HELPERS
     | ------------------------------------------------------ */
    public function debitNoteList(int $companyId, int $financialYearId, array $filters): array
    {
        [$paginator, $rowCount] = $this->debitNoteRepository->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'debit_note.view',
            'debit_note.update',
            'debit_note.delete',
        ], true);

        $items = collect($paginator->items());
        $voucherIds = $items->pluck('voucher_id')->filter()->unique()->toArray();
        $references = Reference::whereIn('voucher_id', $voucherIds)->get()->keyBy('voucher_id');

        $data = $items->map(function ($item) use ($references) {
            $ref = $references->get($item->voucher_id);
            $item->is_paid = $ref && $ref->settled_amount > 0;
            return $item;
        })->toArray();

        $filteredTotal = $this->debitNoteRepository->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->debitNoteRepository->countAll($companyId, $financialYearId);

        return [
            'data'         => $data,
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / ($filters['size'] ?? 50)),
            'current_page' => $filters['page'] ?? 1,
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions,
            'count'        => $rowCount,
        ];
    }

    public function getViewData(int $id, int $companyId, int $financialYearId): ?DebitNote
    {
        return $this->debitNoteRepository->getViewData($id, $companyId, $financialYearId);
    }

    public function getEditData(int $id, int $companyId, int $financialYearId): ?array
    {
        $dn = DebitNote::with([
            'details.item.unit',
            'details.condition',
            'details.destination',
            'billSundries.sundry',
            'billSundries.drAccount',
            'billSundries.crAccount',
            'purchaseType',
            'purchaseInvoice',
        ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->find($id);

        if (!$dn) return null;

        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $dn->account_id);

        return [
            'debit_note_id'       => $dn->id,
            'debit_note_date'     => $dn->debit_note_date,
            'account_id'           => $dn->account_id,
            'account_name'         => $accountDetail['name']     ?? null,
            'account_city'         => $accountDetail['city']     ?? null,
            'gst_type'             => $accountDetail['gst_type'] ?? null,
            'purchase_type_id'         => $dn->purchase_type_id,
            'reference_number'     => $dn->reference_number,
            'purchase_invoice_id'     => $dn->purchase_invoice_id,
            'purchase_invoice_serial' => $dn->purchase_invoice_serial,
            'remarks'              => $dn->remarks,
            'cgst_rate'            => $dn->purchaseType->cgst ?? 0,
            'sgst_rate'            => $dn->purchaseType->sgst ?? 0,
            'igst_rate'            => $dn->purchaseType->igst ?? 0,
            'total_quantity'       => $dn->details->sum('quantity'),
            'total_amount'         => $dn->details->sum('amount'),

            'details' => $dn->details->map(fn($row) => [
                'item_id'        => $row->item_id,
                'item_name'      => $row->item->name       ?? null,
                'unit_name'      => $row->item->unit->name ?? null,
                'condition_id'   => $row->condition_id,
                'condition_name' => $row->condition->name  ?? null,
                'destination_id' => $row->destination_id,
                'destination_name'=> $row->destination->name ?? null,
                'quantity'       => $row->quantity,
                'rate'           => $row->rate,
                'inclusive_rate' => $row->inclusive_rate ?? 0,
                'amount'         => $row->amount,
                'bag_count'      => $row->bag_count,
                'cgst_rate'      => $dn->purchaseType->cgst ?? 0,
                'sgst_rate'      => $dn->purchaseType->sgst ?? 0,
                'igst_rate'      => $dn->purchaseType->igst ?? 0,
            ]),

            'bill_sundries' => $dn->billSundries->map(fn($bs) => [
                'sundry_id'               => $bs->sundry_id,
                'code'                    => $bs->code,
                'value'                   => $bs->value,
                'amount'                  => $bs->amount,
                'sundry_name'             => $bs->name,
                'calculation_type'        => $bs->calculation_type,
                'apply_on'                => $bs->apply_on,
                'bill_sundry_modal_dr_id' => $bs->bill_sundry_modal_dr_id,
                'bill_sundry_modal_cr_id' => $bs->bill_sundry_modal_cr_id,
                'base_amount'             => $bs->base_amount,
                'rate_percent'            => $bs->rate_percent,
                'sort_order'              => $bs->sort_order,
                'bill_sundry_type'        => $bs->bill_sundry_type,
                'dr_account'              => $bs->drAccount ? ['name' => $bs->drAccount->name] : null,
                'cr_account'              => $bs->crAccount ? ['name' => $bs->crAccount->name] : null,
            ]),
        ];
    }

    public function getNextVoucherNumber(int $companyId, int $financialYearId)
    {
        return $this->voucherService->getNextVoucherNumber(VoucherType::PURCHASE_RETURN, $companyId, $financialYearId);
    }

    public function getDebitNoteSerials(int $companyId, int $financialYearId): Collection
    {
        return $this->debitNoteRepository->getDebitNoteSerials($companyId, $financialYearId);
    }

    /** Returns purchase invoices for a given supplier to pick for a return */
    public function getPurchaseInvoicesForSupplier(int $companyId, int $financialYearId, int $accountId): Collection
    {
        return PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('account_id', $accountId)
            ->orderByDesc('invoice_date')
            ->get(['id', 'reference_number as purchase_invoice_serial', 'invoice_date as bill_date', 'net_amount', 'purchase_type_id']);
    }
}

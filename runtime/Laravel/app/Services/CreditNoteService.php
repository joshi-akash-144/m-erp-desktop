<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\BillSundry;
use App\Models\CreditNote;
use App\Models\Reference;
use App\Models\SalesInvoice;
use App\Models\VoucherType;
use App\Models\SaleType;
use App\Repositories\BillSundryRepository;
use App\Repositories\CreditNoteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditNoteService
{
    protected VoucherService $voucherService;
    protected CreditNoteRepository $creditNoteRepository;
    protected LookupService $lookupService;
    protected BillSundryRepository $billSundryRepo;
    protected StockVoucherService $stockVoucherService;
    protected ReferenceService $referenceService;
    protected GstEntryService $gstEntryService;

    public function __construct(
        VoucherService $voucherService,
        CreditNoteRepository $creditNoteRepository,
        LookupService $lookupService,
        BillSundryRepository $billSundryRepo,
        StockVoucherService $stockVoucherService,
        ReferenceService $referenceService,
        GstEntryService $gstEntryService
    ) {
        $this->voucherService       = $voucherService;
        $this->creditNoteRepository = $creditNoteRepository;
        $this->lookupService        = $lookupService;
        $this->billSundryRepo       = $billSundryRepo;
        $this->stockVoucherService  = $stockVoucherService;
        $this->referenceService     = $referenceService;
        $this->gstEntryService      = $gstEntryService;
    }

    /* -------------------------------------------------------
     | CREATE
     | ------------------------------------------------------ */
    public function createCreditNote(array $data, int $companyId, int $financialYearId): CreditNote
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            [$masterData, $items, $billSundry] = $this->prepareCreditNoteData($data, $companyId, $financialYearId, 'create');

            $creditNote = $this->creditNoteRepository->create($masterData);

            foreach ($items as $item) {
                $creditNote->details()->create($item);
            }
            foreach ($billSundry as $sundry) {
                $creditNote->billSundries()->create($sundry);
            }

            $voucherMaster = $this->prepareVoucherMaster($creditNote);
            $voucherLines  = $this->prepareVoucherLines($companyId, $financialYearId, $masterData, $items, $billSundry);
            $voucher       = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $creditNote->update(['voucher_id' => $voucher->id]);

            // Bill-wise reference: reduce outstanding on original invoice
            $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
            
            if ($accountDetail && $accountDetail['is_billwise']) {
                $referenceData = $this->prepareBillWiseReference($creditNote, $voucher);
                $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
            }

            $creditNote->load('details', 'billSundries');

            // Stock reversal: goods come back in
            $this->createOrUpdateStockVoucher($companyId, $creditNote, $voucher);

            // GST entry
            $this->gstEntryService->storeForCreditNote($creditNote, $voucher);

            return $creditNote;
        });
    }

    /* -------------------------------------------------------
     | UPDATE
     | ------------------------------------------------------ */
    public function updateCreditNote(int $creditNoteId, array $data, int $companyId, int $financialYearId): CreditNote
    {
        return DB::transaction(function () use ($creditNoteId, $data, $companyId, $financialYearId) {
            $creditNote = $this->creditNoteRepository->find($creditNoteId);
            if (!$creditNote) {
                throw new \Exception('Credit note not found.');
            }

            $oldRefExists = Reference::where('voucher_id', $creditNote->voucher_id)->first();
            if ($oldRefExists && $oldRefExists->settled_amount > 0) {
                throw new \Exception("Cannot edit. Credit Note is already paid or settled.");
            }

            [$masterData, $items, $billSundry] = $this->prepareCreditNoteData($data, $companyId, $financialYearId, 'update');

            $this->creditNoteRepository->update($creditNote, $masterData);

            $creditNote->details()->delete();
            $creditNote->details()->createMany($items);
            $creditNote->billSundries()->delete();
            $creditNote->billSundries()->createMany($billSundry);
            $creditNote->save();

            $voucherMaster = $this->prepareVoucherMaster($creditNote);
            $voucherLines  = $this->prepareVoucherLines($companyId, $financialYearId, $masterData, $items, $billSundry);
            $voucher       = $this->voucherService->updateVoucher($voucherMaster, $voucherLines, $creditNote->voucher_id);

            // Update bill-wise reference amount
            $oldRef = Reference::where('voucher_id', $voucher->id)->first();
            if ($oldRef) {
                $oldRef->reference_date  = $creditNote->credit_note_date;
                $oldRef->reference_number = $creditNote->reference_number;
                $oldRef->file_number      = 0;
                $oldRef->account_id      = $creditNote->account_id;
                $oldRef->amount          = $creditNote->net_amount;
                $oldRef->pending_amount  = $oldRef->amount - $oldRef->settled_amount;
                $oldRef->is_closed       = $oldRef->pending_amount == 0;
                $oldRef->closed_at       = $oldRef->is_closed ? Carbon::now() : null;
                $oldRef->updated_by      = current_user_id();
                $oldRef->updated_at      = Carbon::now();
                $oldRef->save();
            } else {
                $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $data['account_id']);
                if ($accountDetail && $accountDetail['is_billwise']) {
                    $referenceData = $this->prepareBillWiseReference($creditNote, $voucher);
                    $this->referenceService->createReference($referenceData, $companyId, $financialYearId);
                }
            }

            $creditNote->load('details', 'billSundries');
            $this->createOrUpdateStockVoucher($companyId, $creditNote, $voucher);
            $this->gstEntryService->updateForCreditNote($creditNote, $voucher);

            return $creditNote;
        });
    }

    /* -------------------------------------------------------
     | DELETE
     | ------------------------------------------------------ */
    public function deleteCreditNote(int $creditNoteId): void
    {
        DB::transaction(function () use ($creditNoteId) {
            $creditNote = $this->creditNoteRepository->find($creditNoteId);
            if (!$creditNote) {
                throw new \Exception('Credit note not found.');
            }

            $ref = Reference::where('voucher_id', $creditNote->voucher_id)->first();
            if ($ref && $ref->settled_amount > 0) {
                throw new \Exception('Cannot delete. Credit Note is already paid or settled.');
            }

            $userId = current_user_id();

            $creditNote->deleted_by = $userId;
            $creditNote->save();

            if ($creditNote->voucher_id) {
                $voucher = \App\Models\Voucher::find($creditNote->voucher_id);
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

            $creditNote->delete();
        });
    }

    /* -------------------------------------------------------
     | DATA PREPARATION
     | ------------------------------------------------------ */
    public function prepareCreditNoteData(array $data, int $companyId, int $financialYearId, string $mode = 'create'): array
    {
        $saleTypeDetail = $this->lookupService->getSaleTypeDetails($companyId, $data['sale_type_id']);
        if (!$saleTypeDetail) {
            throw new ValidationException(
                validator([], ['sale_type_id' => 'required']),
            );
        }

        $base = $this->prepareBaseData($data, $companyId, $saleTypeDetail);

        if ($mode === 'create') {
            $serialInfo = $this->voucherService->getNextVoucherNumber(VoucherType::SALES_RETURN, $companyId, $financialYearId);
            $base = array_merge($base, [
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'credit_note_serial' => $serialInfo->serial,
                'credit_note_number' => $serialInfo->voucher_number,
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

        $items       = $this->prepareItemData($data['items'], $companyId, $saleTypeDetail);
        $totals      = $this->calculateTotals($items);
        $billSundries = $this->prepareBillSundries(array_merge($base, $totals), $data['bill_sundries'] ?? []);

        $totals['net_amount']   = $billSundries['net_total'];
        $totals['grand_total']  = $billSundries['grand_total'];

        return [array_merge($base, $totals), $items, $billSundries['rows']];
    }

    protected function prepareBaseData(array $data, int $companyId, array $saleTypeDetail): array
    {
        return [
            'credit_note_date'     => $data['credit_note_date'],
            'reference_number'     => $data['reference_number'] ?? null,
            'account_id'           => $data['account_id'],
            'sale_type_id'         => $data['sale_type_id'],
            'gst_type'             => $saleTypeDetail['region'],
            'sales_invoice_id'     => $data['sales_invoice_id'] ?? null,
            'sales_invoice_serial' => $data['sales_invoice_serial'] ?? null,
            'remarks'              => $data['remarks'] ?? null,
        ];
    }

    /* -------------------------------------------------------
     | VOUCHER MASTER
     | ------------------------------------------------------ */
    private function prepareVoucherMaster(CreditNote $creditNote): array
    {
        return [
            'uuid'              => $creditNote->uuid,
            'company_id'        => $creditNote->company_id,
            'financial_year_id' => $creditNote->financial_year_id,
            'voucher_date'      => $creditNote->credit_note_date,
            'voucher_type_id'   => VoucherType::SALES_RETURN,
            'source_id'         => $creditNote->id,
            'source_type'       => SourceType::SALES_RETURN,
            'reference_number'  => $creditNote->reference_number,
            'voucher_serial'    => $creditNote->credit_note_serial,
            'voucher_number'    => $creditNote->credit_note_number,
            'narration'         => $creditNote->remarks,
        ];
    }

    /* -------------------------------------------------------
     | VOUCHER LINES — CREDIT NOTE ACCOUNTING
     |
     | Sales Invoice (forward):  Party DR,        Sales CR
     | Credit Note (reverse):    Sales Returns DR, Party CR
     | ------------------------------------------------------ */
    private function prepareVoucherLines(
        int $companyId,
        int $financialYearId,
        array $masterData,
        array $items,
        array $billSundry
    ): array {
        $lines = [
            'returns'        => [],   // debit side (sales return / purchase account)
            'party'          => [],   // credit side (customer)
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

        // Sale type → sales/returns account
        $saleTypeDetail = $this->lookupService->getSaleTypeDetails($companyId, $masterData['sale_type_id']);

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
         | In a Credit Note all DR/CR are the mirror of Sales Invoice
         | -------------------------------------------------- */
        foreach ($billSundry as $row) {
            $record = $billSundryRecords->get($row['sundry_id']);
            if (!$record) continue;

            $value = (float) $row['value'];
            if ($value == 0) continue;

            $isAdditive    = in_array($record->bill_sundry_type, [BillSundry::ADDICTIVE, 'additive']);
            $isSubtractive = in_array($record->bill_sundry_type, [BillSundry::SUBTRACTIVE, 'subtractive']);

            $adjustInSales  = (bool) $record->sale_adjust_in_amount;
            $adjustInParty  = (bool) $record->sale_adjust_in_party_amount;
            $postOverAbove  = (bool) $record->sale_post_over_and_above;

            $returnsLedger = $record->sale_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->sale_account_id
                : ($row['bill_sundry_modal_dr_id'] ?? $saleTypeDetail['account_id']);

            $partyLedger = $record->sale_party_account_type === BillSundry::SPECIFY_ACCOUNT
                ? $record->sale_party_account_id
                : ($row['bill_sundry_modal_cr_id'] ?? $masterData['account_id']);

            /* --------------------------------------------------
             | Over & Above — reversed from Sales Invoice
             | -------------------------------------------------- */
            if ($postOverAbove) {
                if ($isSubtractive) {
                    // Sales Invoice had: partyLedger DR, returnsLedger CR
                    // Credit Note reverses: returnsLedger DR, partyLedger CR
                    $pushLine($lines['over_and_above'], $returnsLedger,  $value, 0,      $partyLedger);
                    $pushLine($lines['over_and_above'], $partyLedger,    0,      $value,  $returnsLedger);
                } else {
                    // Sales Invoice had: returnsLedger DR, partyLedger CR
                    // Credit Note reverses: partyLedger DR, returnsLedger CR
                    $pushLine($lines['over_and_above'], $partyLedger,    $value, 0,      $returnsLedger);
                    $pushLine($lines['over_and_above'], $returnsLedger,  0,      $value,  $partyLedger);
                }
                continue;
            }

            /* Adjust in BOTH — just affects amounts */
            if ($adjustInSales && $adjustInParty) {
                $returnsAmount += $isAdditive ? $value : -$value;
                $partyAmount   += $isAdditive ? $value : -$value;
                continue;
            }

            /* Adjust RETURNS only */
            if ($adjustInSales && !$adjustInParty) {
                $returnsAmount += $isAdditive ? $value : -$value;
                $accountName    = $partyLedger ?? $masterData['account_id'];
                if ($isAdditive) {
                    $pushLine($lines['party'], $accountName, 0, $value, $masterData['account_id']);
                } else {
                    $pushLine($lines['returns'], $accountName, $value, 0, $masterData['account_id']);
                }
                continue;
            }

            /* Adjust PARTY only */
            if (!$adjustInSales && $adjustInParty) {
                $partyAmount   += $isAdditive ? $value : -$value;
                $accountName    = $returnsLedger ?? $saleTypeDetail['account_id'];
                if ($isAdditive) {
                    $pushLine($lines['returns'], $accountName, $value, 0, $masterData['account_id']);
                } else {
                    $pushLine($lines['party'], $accountName, 0, $value, $masterData['account_id']);
                }
            }
        }

        /* --------------------------------------------------
         | Main Sales Returns entry (DR)
         | -------------------------------------------------- */
        $lines['returns'][] = [
            'account_id'         => $saleTypeDetail['account_id'],
            'against_account_id' => $masterData['account_id'],
            'debit'              => round($returnsAmount, 2),
            'credit'             => 0,
            'is_party_account'   => false,
        ];

        /* --------------------------------------------------
         | Main Party / Customer entry (CR)
         | -------------------------------------------------- */
        $lines['party'][] = [
            'account_id'         => $masterData['account_id'],
            'against_account_id' => $saleTypeDetail['account_id'],
            'debit'              => 0,
            'credit'             => round($partyAmount, 2),
            'is_party_account'   => true,
        ];

        /* Validate debit == credit */
        $totalDebit  = array_sum(array_column($lines['returns'], 'debit'))
                     + array_sum(array_column($lines['over_and_above'], 'debit'));

        $totalCredit = array_sum(array_column($lines['party'], 'credit'))
                     + array_sum(array_column($lines['over_and_above'], 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception("Debit/Credit mismatch in Credit Note: {$totalDebit} != {$totalCredit}");
        }

        $flat = [];
        foreach ($lines as $group) {
            $flat = array_merge($flat, $group);
        }
        return $flat;
    }

    /* -------------------------------------------------------
     | STOCK VOUCHER — goods returned (stock IN)
     | ------------------------------------------------------ */
    private function createOrUpdateStockVoucher(int $companyId, CreditNote $creditNote, $voucher): void
    {
        $stockTransactions = [];

        foreach ($creditNote->details as $detail) {
            if ($detail->quantity <= 0) continue;

            $itemDetail = $this->lookupService->getItemDetails($companyId, $detail->item_id);
            if (!$itemDetail['is_maintain_stock_balance']) continue;

            $stockTransactions[] = [
                'item_id' => $detail->item_id,
                'in_qty'  => $detail->quantity,   // stock comes BACK in
                'out_qty' => 0,
                'rate'    => $detail->rate,
                'amount'  => $detail->amount,
            ];
        }

        $stockMaster = [
            'voucher_id'        => $voucher->id,
            'company_id'        => $creditNote->company_id,
            'financial_year_id' => $creditNote->financial_year_id,
            'voucher_type_id'   => VoucherType::SALES_RETURN,
            'voucher_number'    => $creditNote->credit_note_number,
            'voucher_serial'    => $creditNote->credit_note_serial,
            'reference_number'  => $creditNote->reference_number,
            'voucher_date'      => $creditNote->credit_note_date,
        ];

        $this->stockVoucherService->upsertStockVoucher($stockMaster, $stockTransactions);
    }

    /* -------------------------------------------------------
     | BILL-WISE REFERENCE (reduces party outstanding)
     | ------------------------------------------------------ */
    private function prepareBillWiseReference(CreditNote $creditNote, $voucher): array
    {
        return [
            'reference_number' => $creditNote->reference_number,
            'reference_date'   => $creditNote->credit_note_date,
            'parent_voucher_id'=> $voucher->id,
            'reference_type'   => 'new_ref',
            'amount'           => $creditNote->net_amount,
            'direction'        => config('ref_direction_map.direction.' . SourceType::SALES_RETURN),
            'account_id'       => $creditNote->account_id,
            'source_type'      => SourceType::SALES_RETURN,
            'source_id'        => $creditNote->id,
            'voucher_id'       => $voucher->id,
            'pending_amount'   => $creditNote->net_amount,
            'file_number'      => 0,
        ];
    }

    /* -------------------------------------------------------
     | ITEM DATA
     | ------------------------------------------------------ */
    protected function prepareItemData(array $items, int $companyId, array $salesTypeData): array
    {
        return collect($items)->map(function ($item) use ($salesTypeData) {
            [$cgstPercent, $sgstPercent, $igstPercent] = match ($salesTypeData['region']) {
                CreditNote::TAX_LOCAL => [$salesTypeData['cgst'], $salesTypeData['sgst'], 0],
                default               => [0, 0, $salesTypeData['igst']],
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

            $isAdjustInPartyAmount = (bool) ($record->sale_adjust_in_party_amount ?? false);

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
    public function creditNoteList(int $companyId, int $financialYearId, array $filters): array
    {
        [$paginator, $rowCount] = $this->creditNoteRepository->list($companyId, $financialYearId, $filters);

        $permissions = userPermissions([
            'credit_note.view',
            'credit_note.update',
            'credit_note.delete',
        ], true);

        $items = collect($paginator->items());
        $voucherIds = $items->pluck('voucher_id')->filter()->unique()->toArray();
        $references = \App\Models\Reference::whereIn('voucher_id', $voucherIds)->get()->keyBy('voucher_id');

        $data = $items->map(function ($item) use ($references) {
            $ref = $references->get($item->voucher_id);
            $item->is_paid = $ref && $ref->settled_amount > 0;
            return $item;
        })->toArray();

        $filteredTotal = $this->creditNoteRepository->countFiltered($companyId, $financialYearId, $filters);
        $grandTotal    = $this->creditNoteRepository->countAll($companyId, $financialYearId);

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

    public function getViewData(int $id, int $companyId, int $financialYearId): ?CreditNote
    {
        return $this->creditNoteRepository->getViewData($id, $companyId, $financialYearId);
    }

    public function getEditData(int $id, int $companyId, int $financialYearId): ?array
    {
        $cn = CreditNote::with([
            'details.item.unit',
            'details.condition',
            'details.destination',
            'billSundries.sundry',
            'billSundries.drAccount',
            'billSundries.crAccount',
            'saleType',
            'salesInvoice',
        ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->find($id);

        if (!$cn) return null;

        $accountDetail = $this->lookupService->getPartyAccountDetails($companyId, $cn->account_id);

        return [
            'credit_note_id'       => $cn->id,
            'credit_note_date'     => $cn->credit_note_date,
            'account_id'           => $cn->account_id,
            'account_name'         => $accountDetail['name']     ?? null,
            'account_city'         => $accountDetail['city']     ?? null,
            'gst_type'             => $accountDetail['gst_type'] ?? null,
            'sale_type_id'         => $cn->sale_type_id,
            'reference_number'     => $cn->reference_number,
            'sales_invoice_id'     => $cn->sales_invoice_id,
            'sales_invoice_serial' => $cn->sales_invoice_serial,
            'remarks'              => $cn->remarks,
            'cgst_rate'            => $cn->saleType->cgst ?? 0,
            'sgst_rate'            => $cn->saleType->sgst ?? 0,
            'igst_rate'            => $cn->saleType->igst ?? 0,
            'total_quantity'       => $cn->details->sum('quantity'),
            'total_amount'         => $cn->details->sum('amount'),

            'details' => $cn->details->map(fn($row) => [
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
                'cgst_rate'      => $cn->saleType->cgst ?? 0,
                'sgst_rate'      => $cn->saleType->sgst ?? 0,
                'igst_rate'      => $cn->saleType->igst ?? 0,
            ]),

            'bill_sundries' => $cn->billSundries->map(fn($bs) => [
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
        return $this->voucherService->getNextVoucherNumber(VoucherType::SALES_RETURN, $companyId, $financialYearId);
    }

    public function getCreditNoteSerials(int $companyId, int $financialYearId): Collection
    {
        return $this->creditNoteRepository->getCreditNoteSerials($companyId, $financialYearId);
    }

    /** Returns sales invoices for a given customer to pick for a return */
    public function getSalesInvoicesForCustomer(int $companyId, int $financialYearId, int $accountId): Collection
    {
        return SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('account_id', $accountId)
            ->orderByDesc('invoice_date')
            ->get(['id', 'invoice_serial', 'invoice_date', 'net_amount','sale_type_id']);
    }
}

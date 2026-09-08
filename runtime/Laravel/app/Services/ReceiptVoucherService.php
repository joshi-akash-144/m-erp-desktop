<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\SalesInvoice;
use App\Models\Voucher;
use App\Models\VoucherRow;
use App\Models\VoucherReference;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use App\Models\AuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReceiptVoucherService
{
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;

    public function __construct(VoucherService $voucherService, ReferenceService $referenceService)
    {
        $this->voucherService   = $voucherService;
        $this->referenceService = $referenceService;
    }

    /*--------------------------------------------------------------
    | VALIDATE ROWS
    --------------------------------------------------------------*/
    private function validateRows(array $rows): void
    {
        if (empty($rows)) {
            throw new \Exception('Please enter at least one voucher row.');
        }

        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;

            if (empty($row['account_id'])) {
                throw new \Exception("Row {$rowNum}: Account is required.");
            }
            if (empty($row['dr_cr'])) {
                throw new \Exception("Row {$rowNum}: Dr/Cr type is required.");
            }

            $debit  = (float) ($row['debit_amount']  ?? 0);
            $credit = (float) ($row['credit_amount'] ?? 0);

            if ($debit <= 0 && $credit <= 0) {
                throw new \Exception("Row {$rowNum}: Debit or Credit amount is required.");
            }

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception(
                'Voucher is not balanced. Debit: ' . number_format($totalDebit, 2) . ' | Credit: ' . number_format($totalCredit, 2)
            );
        }
    }

    /*--------------------------------------------------------------
    | CREATE VOUCHER
    --------------------------------------------------------------*/
    public function createVoucher(array $data, $companyId, $financialYearId)
    {
        $this->validateRows($data['rows'] ?? []);

        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $checkUUID = $this->voucherService->checkUUIDExists($data['uuid']);
            if ($checkUUID) {
                throw new \Exception("This voucher reference already exists. Please refresh and try again.");
            }

            $payload = $this->prepareVoucherPayload($data, $companyId, $financialYearId);

            $voucher = $this->voucherService->createVoucher($payload['voucher'], $payload['rows']);

            $totalCredit = 0;
            foreach ($payload['rows'] as $key => $value) {
                if($value['credit'] > 0){
                    $totalCredit += $value['credit'];
                }
            }

            $receipt = ReceiptVoucher::create([
                'voucher_id'        => $voucher->id,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'entry_from'        => ReceiptVoucher::ENTRY_FROM_RECEIPT_VOUCHER,
                'received_amount'   => $totalCredit,
                'is_received'       => true,
                'is_approved'       => true,
            ]);

            Voucher::where('id', $voucher->id)->update([
                'source_type' => SourceType::RECEIPT,
                'source_id'   => $receipt->id,
            ]);

            $this->createOrSettleReferences($data, $voucher, $receipt, $companyId, $financialYearId);

            $freshVoucher = $voucher->fresh();

            $this->logAudit($data, $freshVoucher, AuditTrail::ACTION_CREATE);

            return $freshVoucher;
        });
    }

    /*--------------------------------------------------------------
    | PREPARE PAYLOAD
    --------------------------------------------------------------*/
    public function prepareVoucherPayload(array $data, $companyId, $financialYearId)
    {
        $serialInfo = $this->voucherService->getNextVoucherNumber(
            VoucherType::RECEIPT,
            $companyId,
            $financialYearId
        );

        return [
            'rows'    => $this->prepareVoucherRows($data['rows']),
            'voucher' => $this->prepareVoucherData($data, $serialInfo, $companyId, $financialYearId),
        ];
    }

    private function prepareVoucherData(array $data, $serialInfo, $companyId, $financialYearId)
    {
        return [
            'uuid'              => $data['uuid'],
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_date'      => $data['voucher_date'],
            'voucher_type_id'   => VoucherType::RECEIPT,
            'reference_id'      => null,
            'reference_type'    => Reference::Receipt,
            'reference_number'  => "",
            'voucher_serial'    => $serialInfo->serial,
            'voucher_number'    => $serialInfo->voucher_number,
            'narration'         => $data['narration'] ?? null,
        ];
    }

    /*--------------------------------------------------------------
    | PREPARE ROWS
    --------------------------------------------------------------*/
    private function prepareVoucherRows(array $rows)
    {
        $final       = [];
        $drAgainstId = null;
        $crAgainstId = null;
        $isFirstDr   = true;
        $isFirstCr   = true;

        foreach ($rows as $r) {
            if ($r['dr_cr'] === 'DR' && $isFirstDr) {
                $drAgainstId = $r['account_id'];
                $isFirstDr   = false;
            }
            if ($r['dr_cr'] === 'CR' && $isFirstCr) {
                $crAgainstId = $r['account_id'];
                $isFirstCr   = false;
            }
        }

        $line = 0;
        foreach ($rows as $row) {
            $accountId        = $row['account_id'];
            $isParty          = $this->isPartyLedger($accountId);
            $transactionType  = $row['dr_cr'];
            $againstAccountId = $transactionType === 'DR' ? $crAgainstId : $drAgainstId;

            $final[] = [
                'account_id'         => $accountId,
                'against_account_id' => $againstAccountId,
                'debit'              => $row['debit_amount'],
                'credit'             => $row['credit_amount'],
                'is_party_account'   => $isParty,
                'line_no'            => ++$line,
            ];
        }

        return $final;
    }

    /*--------------------------------------------------------------
    | CREATE OR SETTLE REFERENCES
    --------------------------------------------------------------*/
    private function createOrSettleReferences(array $data, $voucher, $receipt, $companyId, $financialYearId)
    {
        foreach ($data['rows'] as $r) {
            $references = $r['references'] ?? [];
            if (empty($references)) continue;

            foreach ($references as $ref) {
                $direction = $r['dr_cr'] === 'DR' ? 'receipt_debit' : 'receipt_credit';
                $amount    = $ref['ref_amount'];

                if ($ref['method'] === 'new_ref') {
                    Reference::create([
                        'reference_number'  => $ref['ref_number'],
                        'reference_date'    => $data['voucher_date'],
                        'reference_type'    => Reference::NewReference,
                        'file_number'       => $ref['file_no'] ?? null,
                        'amount'            => $amount,
                        'direction'         => config('ref_direction_map.direction.' . $direction),
                        'account_id'        => $r['account_id'],
                        'source_type'       => SourceType::RECEIPT,
                        'source_id'         => $receipt->id,
                        'voucher_id'        => $voucher->id,
                        'pending_amount'    => $amount,
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'created_by' => current_user_id(),
                    ]);
                }

                if ($ref['method'] === 'against_ref' && $ref['ref_id']) {
                    $referenceRecord = Reference::where('id', $ref['ref_id'])->lockForUpdate()->first();
                    if (!$referenceRecord) continue;

                    $referenceRecord->settled_amount += $ref['ref_amount'];
                    $referenceRecord->pending_amount -= $ref['ref_amount'];
                    $isClosed = round(abs($referenceRecord->amount), 2) <= round($referenceRecord->settled_amount, 2);
                    $referenceRecord->is_closed = $isClosed;
                    $referenceRecord->closed_at = $isClosed ? Carbon::now() : null;
                    $referenceRecord->save();

                    ReferenceAllocation::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_id'        => $voucher->id,
                        'reference_id'      => $referenceRecord->id,
                        'account_id'        => $referenceRecord->account_id,
                        'amount'            => $amount,
                        'allocation_type'   => ReferenceAllocation::AGAINST_REF,
                        'source_type'       => SourceType::RECEIPT,
                        'source_id'         => $referenceRecord->source_id,
                    ]);
                }
            }
        }
    }

    /*--------------------------------------------------------------
    | HELPERS
    --------------------------------------------------------------*/
    private function isPartyLedger($accountId): bool
    {
        $account = Account::select('account_group_id')->find($accountId);
        if (!$account) return false;

        $group = AccountGroup::select('is_party_group')->find($account->account_group_id);
        if (!$group) return false;

        return (bool) $group->is_party_group;
    }

    public function getNextVoucherNumber($companyId, $financialYearId)
    {
        return $this->voucherService->getNextVoucherNumber(
            VoucherType::RECEIPT,
            $companyId,
            $financialYearId
        );
    }

    /*--------------------------------------------------------------
    | LIST
    --------------------------------------------------------------*/
    public function list($companyId, $financialYearId, $filter = [], $page = 1, $size = 50)
    {
        $offset = ($page - 1) * $size;

        $baseQuery = DB::table('vouchers as v')
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.company_id', $companyId)
            ->where('v.voucher_type_id', VoucherType::RECEIPT)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1);

        $grandTotal = (clone $baseQuery)->count();

        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $baseQuery->whereBetween('v.voucher_date', [$filter['start_date'], $filter['end_date']]);
        }

        if (!empty($filter['account_id'])) {
            $baseQuery->whereExists(function ($query) use ($filter) {
                $query->select(DB::raw(1))
                    ->from('voucher_transactions as vt')
                    ->whereColumn('vt.voucher_id', 'v.id')
                    ->where('vt.account_id', $filter['account_id']);
            });
        }

        if (!empty($filter['voucher_no'])) {
            $baseQuery->where('v.id', $filter['voucher_no']);
        }
        $totalVouchers = $baseQuery->count();
        $lastPage      = (int) ceil($totalVouchers / $size);

        $voucherIds = (clone $baseQuery)
            ->orderByDesc('v.voucher_serial')
            ->limit($size)
            ->offset($offset)
            ->pluck('v.id')
            ->toArray();

        $permissions = userPermissions([
            'receipt_voucher.view',
            'receipt_voucher.update',
            'receipt_voucher.delete',
        ], true);

        if (empty($voucherIds)) {
            return [
                'data'        => [],
                'last_page'   => 1,
                'total'       => 0,
                'grand_total' => $grandTotal,
                'permissions' => $permissions,
            ];
        }

        $transactions = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'vt.account_id')
            ->leftJoin('users as created', 'created.id', '=', 'v.created_by')
            ->leftJoin('voucher_types as vty', 'vty.id', '=', 'v.voucher_type_id')
            ->whereIn('v.id', $voucherIds)
            ->orderByDesc('v.voucher_serial')
            ->orderBy('vt.line_no')
            ->get([
                'v.id as voucher_id',
                'v.voucher_date',
                'v.voucher_number',
                'v.voucher_serial',
                'v.reference_number',
                'vty.name as voucher_type',
                'vt.account_id',
                'a.name as account_name',
                'vt.debit',
                'vt.credit',
                'vt.narration as short_narration',
                'v.narration as full_narration',
                'created.name as created_by_name',
            ]);

        $showNarration = !empty($filter['narration']) && $filter['narration'] == '1';
        $data = $this->injectNarrationRows($transactions, $showNarration);

        return [
            'data'        => $data,
            'last_page'   => $lastPage,
            'total'       => $totalVouchers,
            'grand_total' => $grandTotal,
            'permissions' => $permissions,
        ];
    }

    private function injectNarrationRows($transactions, bool $show): array
    {
        $result   = [];
        $lastId   = null;
        $lastNarration = null;

        foreach ($transactions as $row) {
            $row = (array) $row;
            $row['row_type'] = 'transaction';

            if ($row['voucher_id'] !== $lastId) {
                if ($show && $lastId !== null && !empty(trim((string) ($lastNarration ?? '')))) {
                    $result[] = $this->narrationRow($lastId, $lastNarration);
                }
                $lastId   = $row['voucher_id'];
                $lastNarration = $row['full_narration'] ?? null;
            }

            $result[] = $row;
        }

        if ($show && $lastId !== null && !empty(trim((string) ($lastNarration ?? '')))) {
            $result[] = $this->narrationRow($lastId, $lastNarration);
        }

        return $result;
    }

    private function narrationRow($voucherId, string $narration): array
    {
        return [
            'voucher_id'       => $voucherId,
            'voucher_date'     => null,
            'voucher_number'   => null,
            'voucher_serial'   => null,
            'reference_number' => null,
            'voucher_type'     => null,
            'account_id'       => null,
            'account_name'     => $narration,
            'debit'            => null,
            'credit'           => null,
            'short_narration'  => null,
            'full_narration'   => null,
            'created_by_name'  => null,
            'row_type'         => 'narration',
        ];
    }

    /*--------------------------------------------------------------
    | EXPORT DATA
    --------------------------------------------------------------*/
    public function getVoucherDataForExport($companyId, $financialYearId, array $filter = [])
    {
        $query = DB::table('vouchers as v')
            ->join('voucher_transactions as vt', 'vt.voucher_id', '=', 'v.id')
            ->leftJoin('accounts as a', 'a.id', '=', 'vt.account_id')
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.company_id', $companyId)
            ->where('v.voucher_type_id', VoucherType::RECEIPT)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1);

        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $query->whereBetween('v.voucher_date', [$filter['start_date'], $filter['end_date']]);
        }

        if (!empty($filter['account_id'])) {
            $query->whereExists(function ($q) use ($filter) {
                $q->select(DB::raw(1))
                    ->from('voucher_transactions as vt2')
                    ->whereColumn('vt2.voucher_id', 'v.id')
                    ->where('vt2.account_id', $filter['account_id']);
            });
        }

        if (!empty($filter['voucher_no'])) {
            $query->where('v.id', $filter['voucher_no']);
        }

        return $query
            ->orderBy('v.voucher_date')
            ->orderBy('v.id')
            ->orderBy('vt.line_no')
            ->get([
                'v.id as voucher_id',
                'v.voucher_date',
                'v.voucher_serial',
                'v.narration as full_narration',
                'a.name as account_name',
                'vt.debit',
                'vt.credit',
            ]);
    }

    /*--------------------------------------------------------------
    | LOCK CHECK
    --------------------------------------------------------------*/
    public function isVoucherLocked($voucherId): bool
    {
        return Reference::where('voucher_id', $voucherId)
            // ->where('reference_type', Reference::NewReference)
            ->where('settled_amount', '>', 0)
            ->exists();
    }

    public function lockedRefs($voucherId): array
    {
        return Reference::where('voucher_id', $voucherId)
            ->where('reference_type', Reference::NewReference)
            ->where('settled_amount', '>', 0)
            ->get(['reference_number', 'amount', 'settled_amount', 'pending_amount'])
            ->toArray();
    }

    /*--------------------------------------------------------------
    | DETAILS (for edit / reference preview)
    --------------------------------------------------------------*/
    public function details($id)
    {
        $voucher = Voucher::with([
            'details' => fn($q) => $q->orderBy('id'),
            'details.account:id,name'
        ])->findOrFail($id);
        $receipt = ReceiptVoucher::where('voucher_id', $id)->first();

        $isLocked   = $this->isVoucherLocked($id);
        $lockedRefs = $isLocked ? $this->lockedRefs($id) : [];

        $newRefs = Reference::where('voucher_id', $id)
            ->where('reference_type', Reference::NewReference)
            ->get()
            ->groupBy('account_id');

        $againstRefs = ReferenceAllocation::with('reference')
            ->where('voucher_id', $id)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get()
            ->groupBy('account_id');

        // Fetch Sales Invoices to enrich against_ref allocations
        $salesInvoiceIds = [];
        foreach ($againstRefs as $accountId => $allocs) {
            foreach ($allocs as $alloc) {
                if ($alloc->reference && $alloc->reference->source_type == Reference::SalesInvoice) {
                    $salesInvoiceIds[] = $alloc->reference->source_id;
                }
            }
        }

        $salesInvoices = collect();
        if (!empty($salesInvoiceIds)) {
            $salesInvoices = SalesInvoice::with([
                'salesOrder:id,purchase_order_number',
                'details.item:id,name',
                'details.destination:id,name'
            ])->whereIn('id', array_unique($salesInvoiceIds))->get()->keyBy('id');
        }
        $voucher->details->each(function ($txn) use ($newRefs, $againstRefs, $salesInvoices) {
            $txn->account_name = $txn->account->name ?? '';
            $transactionType = $txn->debit > 0 ? 'DR' : 'CR';
            $refs = [];

            foreach ($newRefs->get($txn->account_id, collect()) as $ref) {
                $refs[] = [
                    'ref_id'           => null,
                    'method'           => 'new_ref',
                    'ref_number'       => $ref->reference_number,
                    'ref_amount'       => abs($ref->amount),
                    'file_no'          => $ref->file_number,
                    'transaction_type' => $transactionType,
                    'ref_date'         => $ref->reference_date,
                    'po_number'        => '',
                    'delivery_date'    => '',
                    'product'          => '',
                    'destination'      => '',
                    'qty'              => '',
                ];
            }

            foreach ($againstRefs->get($txn->account_id, collect()) as $alloc) {
                $refData = [
                    'ref_id'           => $alloc->reference_id,
                    'method'           => 'against_ref',
                    'ref_number'       => $alloc->reference->reference_number ?? '',
                    'ref_amount'       => abs($alloc->amount),
                    'file_no'          => $alloc->reference->file_number ?? null,
                    'transaction_type' => $transactionType,
                    'ref_date'         => $alloc->reference->reference_date ?? '',
                    'po_number'        => '',
                    'delivery_date'    => '',
                    'product'          => '',
                    'destination'      => '',
                    'qty'              => '',
                ];

                if ($alloc->reference && $alloc->reference->source_type == Reference::SalesInvoice) {
                    $invoice = $salesInvoices->get($alloc->reference->source_id);
                    if ($invoice) {
                        $refData['ref_date']      = $invoice->invoice_date;
                        $refData['po_number']     = $invoice->salesOrder->purchase_order_number ?? '';
                        $refData['delivery_date'] = $invoice->delivery_date ?? '';
                        
                        $firstDetail = $invoice->details->first();
                        if ($firstDetail) {
                            $refData['product']     = $firstDetail->item->name ?? '';
                            $refData['destination'] = $firstDetail->destination->name ?? '';
                            $refData['qty']         = $firstDetail->quantity;
                        }
                    }
                }

                $refs[] = $refData;
            }

            $txn->refs = $refs;
        });

        return [
            'voucher'     => $voucher,
            'receipt'     => $receipt,
            'is_locked'   => $isLocked,
            'locked_refs' => $lockedRefs,
        ];
    }

    public function voucherSerials($companyId, $financialYearId)
    {
        return Voucher::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type_id', VoucherType::RECEIPT)
            ->get(['voucher_serial', 'id']);
    }

    /*--------------------------------------------------------------
    | UPDATE VOUCHER
    --------------------------------------------------------------*/
    public function updateVoucher($voucherId, array $data, $companyId, $financialYearId)
    {
        $this->validateRows($data['rows'] ?? []);

        return DB::transaction(function () use ($voucherId, $data, $companyId, $financialYearId) {
            $voucherId = (int) $voucherId;

            $voucher = Voucher::with('details.account')->findOrFail($voucherId);
            $receipt = ReceiptVoucher::where('voucher_id', $voucherId)->firstOrFail();

            // Prepare old values for auditing
            $data['old_values'] = [
                'voucher_date' => $voucher->voucher_date,
                'narration'    => $voucher->narration,
                'details'      => $voucher->details->map(function ($detail) {
                    return [
                        'account_id'    => $detail->account_id,
                        'account_name'  => $detail->account?->name ?? 'Unknown',
                        'dr_cr'         => $detail->debit > 0 ? 'DR' : 'CR',
                        'debit_amount'  => number_format((float)$detail->debit, 2, '.', ''),
                        'credit_amount' => number_format((float)$detail->credit, 2, '.', ''),
                    ];
                })->toArray(),
            ];

            $this->reverseReferences($voucherId);

            $rows = $this->prepareVoucherRows($data['rows']);

            $this->voucherService->updateVoucher(
                [
                    'voucher_date' => $data['voucher_date'],
                    'narration'    => $data['narration'] ?? null,
                ],
                $rows,
                $voucherId
            );

            $this->createOrSettleReferences($data, $voucher, $receipt, $companyId, $financialYearId);

            $freshVoucher = $voucher->fresh();

            $this->logAudit($data, $freshVoucher, AuditTrail::ACTION_UPDATE);

            return $freshVoucher;
        });
    }

    /*--------------------------------------------------------------
    | DELETE VOUCHER
    --------------------------------------------------------------*/
    public function deleteVoucher(int $voucherId): void
    {
        DB::transaction(function () use ($voucherId) {
            $voucher        = Voucher::with('details.account')->findOrFail($voucherId);
            $receiptVoucher = ReceiptVoucher::where('voucher_id', $voucherId)->first();

            // Capture source_id before any deletion — handles legacy vouchers where
            // source_id may be null in the DB and retry scenarios where the receipt
            // row was already removed.
            $capturedSourceId = $receiptVoucher?->id ?? $voucher->source_id;

            $data['old_values'] = [
                'voucher_date' => $voucher->voucher_date,
                'narration'    => $voucher->narration,
                'details'      => $voucher->details->map(function ($detail) {
                    return [
                        'account_id'    => $detail->account_id,
                        'account_name'  => $detail->account?->name ?? 'Unknown',
                        'dr_cr'         => $detail->debit > 0 ? 'DR' : 'CR',
                        'debit_amount'  => number_format((float)$detail->debit, 2, '.', ''),
                        'credit_amount' => number_format((float)$detail->credit, 2, '.', ''),
                    ];
                })->toArray(),
            ];

            $this->reverseReferences($voucherId);

            VoucherTransaction::where('voucher_id', $voucherId)->delete();

            if ($receiptVoucher) {
                $receiptVoucher->delete();
            }

            $voucher->deleted_by = current_user_id();
            $voucher->is_active  = 0;
            $voucher->save();
            $voucher->delete();

            // Restore captured source_id so logAudit always gets a non-null value.
            $voucher->source_id = $capturedSourceId;

            $this->logAudit($data, $voucher, AuditTrail::ACTION_DELETE);
        });
    }

    /*--------------------------------------------------------------
    | RECEIPT REGISTER LIST
    --------------------------------------------------------------*/
    public function getReceiptRegisterList(int $companyId, int $financialYearId, array $filters = [], int $page = 1, int $size = 100): array
    {
        
        $offset = ($page - 1) * $size;

        $query = DB::table('vouchers as v')
            ->join('voucher_transactions as vt', function ($join) {
                $join->on('vt.voucher_id', '=', 'v.id')
                     ->where('vt.credit', '>', 0);
            })
            ->join('accounts as a', 'a.id', '=', 'vt.account_id')
            ->leftJoin('users as u', 'u.id', '=', 'v.created_by')
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.voucher_type_id', VoucherType::RECEIPT)
            ->whereNull('v.deleted_at')
            ->where('v.is_active', 1);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('v.voucher_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['account_id'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                    ->from('voucher_transactions as vt_f')
                    ->whereColumn('vt_f.voucher_id', 'v.id')
                    ->where('vt_f.account_id', $filters['account_id']);
            });
        }

        if (!empty($filters['voucher_no'])) {
            $query->where('v.id', $filters['voucher_no']);
        }

        $total = $query->count();

        $rows = (clone $query)
            // ->orderByDesc('v.voucher_date')
            ->orderByDesc('v.voucher_serial')
            ->orderByDesc('vt.credit')
            ->offset($offset)
            ->limit($size)
            ->get([
                'v.id as voucher_id',
                'v.voucher_date',
                'v.voucher_serial',
                'v.narration',
                'a.name as party_name',
                'vt.credit as received_amount',
                'u.name as creator_name',
            ]);

        $permissions = userPermissions([
            'receipt_register.view',
            'receipt_register.delete',
            'receipt_register.print',
        ], true);

        return [
            'data'        => $rows,
            'total'       => $total,
            'last_page'   => (int) ceil($total / $size),
            'permissions' => $permissions,
        ];
    }

    /*--------------------------------------------------------------
    | REVERSE REFERENCES
    --------------------------------------------------------------*/
    protected function reverseReferences(int $voucherId): void
    {
        $allocations = ReferenceAllocation::with(['reference' => fn($q) => $q->lockForUpdate()])
            ->where('voucher_id', $voucherId)
            // ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get();

        foreach ($allocations as $alloc) {
            $ref = $alloc->reference;
            if (!$ref) continue;

            $restoredAmount = $alloc->amount;

            $ref->settled_amount = $ref->settled_amount - $restoredAmount;
            $ref->pending_amount += $restoredAmount;

            $isClosed    = $ref->amount == $ref->settled_amount;
            $ref->is_closed = $isClosed;
            $ref->closed_at = $isClosed ? $ref->closed_at : null;

            $ref->save();
        }

        ReferenceAllocation::where('voucher_id', $voucherId)->delete();

        Reference::where('voucher_id', $voucherId)->delete();
    }

    /*--------------------------------------------------------------
    | AUDIT LOG
    --------------------------------------------------------------*/
    private function logAudit($data, $voucher, $action)
    {
        if (!isAuditLog()) {
            return null;
        }

        $version = 0;

        $oldValues = $data['old_values'] ?? [];
        $newValues = [];

        if ($action == AuditTrail::ACTION_CREATE || $action == AuditTrail::ACTION_UPDATE) {
            $newValues = [
                'voucher_no'   => $voucher->voucher_id ?? null,
                'voucher_date' => $data['voucher_date'] ?? null,
                'narration'    => $data['narration'] ?? null,
            ];

            $accountIds = collect($data['rows'])->pluck('account_id')->filter()->unique()->toArray();
            $accounts   = Account::whereIn('id', $accountIds)->pluck('name', 'id');

            $details = [];
            if (!empty($data['rows'])) {
                foreach ($data['rows'] as $row) {
                    $details[] = [
                        'account_id'    => $row['account_id'] ?? null,
                        'account_name'  => $accounts[$row['account_id'] ?? 0] ?? 'Unknown',
                        'dr_cr'         => $row['dr_cr'] ?? null,
                        'debit_amount'  => number_format((float)($row['debit_amount'] ?? 0), 2, '.', ''),
                        'credit_amount' => number_format((float)($row['credit_amount'] ?? 0), 2, '.', ''),
                    ];
                }
            }
            $newValues['details'] = $details;
        } elseif ($action == AuditTrail::ACTION_DELETE) {
            // New values remain empty for delete
        }

        if ($action == AuditTrail::ACTION_UPDATE) {
            $oldDot = \Illuminate\Support\Arr::dot($oldValues);
            $newDot = \Illuminate\Support\Arr::dot($newValues);

            $hasChanges = false;
            $allKeys    = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));

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

        $origionalAmount = collect($oldValues['details'] ?? [])->sum(function ($item) {
            return (float)($item['debit_amount'] ?? 0);
        });
        $finalAmount     = collect($newValues['details'] ?? [])->sum(function ($item) {
            return (float)($item['debit_amount'] ?? 0);
        });

        $auditData = [
            'company_id'        => $voucher->company_id,
            'financial_year_id' => $voucher->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::RECEIPT,
            'record_type'       => AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => ReceiptVoucher::class,
            'source_id'         => $voucher->source_id ?? null,
            'voucher_id'        => $voucher->id,
            'reference_number'  => $voucher->reference_number,
            'org_amount'        => $origionalAmount,
            'final_amount'      => $finalAmount,
            'version'           => $version,
            'http_method'       => request()->method(),
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ];

        return app(\App\Services\AuditService::class)->log($auditData);
    }
}

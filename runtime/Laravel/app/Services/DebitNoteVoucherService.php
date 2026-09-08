<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\PurchaseInvoice;
use App\Models\AccountGroup;
use App\Models\DebitNoteVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\VoucherTransaction;
use App\Models\Voucher;
use App\Models\VoucherRow;
use App\Models\VoucherReference;
use App\Models\VoucherType;
use App\Models\AuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DebitNoteVoucherService
{
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;

    public function __construct(VoucherService $voucherService, ReferenceService $referenceService)
    {
        $this->voucherService = $voucherService;
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
    | PUBLIC FUNCTION – ENTRY POINT
    --------------------------------------------------------------*/
    public function createVoucher(array $data, $companyId, $financialYearId)
    {
        $this->validateRows($data['rows'] ?? []);

        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $checkUUID = $this->voucherService->checkUUIDExists($data['uuid']);
            if ($checkUUID) {
                throw new \Exception("This voucher reference already exists. Please Refresh and try again.");
            }

            // 1. Prepare an insert-ready payload
            $payload = $this->prepareVoucherPayload(
                $data,
                $companyId,
                $financialYearId
            );

            // 2. Prepare voucher master
            $voucherMaster = $payload['voucher'];

            // 3. Prepare voucher lines
            $voucherLines = $payload['rows'];
            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $journal = DebitNoteVoucher::create([
                'voucher_id'        => $voucher->id,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'gst_nature'        => $data['gst_nature'],
                'bill_date'         => $data['bill_date'] ?? null,
            ]);
            $voucher->update([
                'source_type' => SourceType::DEBIT_NOTE,
                'source_id' => $journal->id
            ]);

            $this->createOrSettleReferences($data, $voucher, $journal, $companyId, $financialYearId);



            $this->logAudit($data, $voucher, AuditTrail::ACTION_CREATE);

            return $voucher;  // return final voucher with all rows saved
        });
    }

    /*--------------------------------------------------------------
    | 2. PREPARE COMPLETE PAYLOAD
    --------------------------------------------------------------*/
    public function prepareVoucherPayload(array $data, $companyId, $financialYearId)
    {
        $serialInfo = $this->voucherService->getNextVoucherNumber(
            VoucherType::DEBIT_NOTE,
            $companyId,
            $financialYearId
        );

        return [
            'rows'    => $this->prepareVoucherRows($data['rows']),
            'voucher' => $this->prepareVoucherData($data, $serialInfo, $companyId, $financialYearId),
            'journal' => $this->prepareDebitNote($data, $serialInfo, $companyId, $financialYearId)
        ];
    }


    private function prepareDebitNote($data, $serialInfo, $companyId, $financialYearId)
    {
        return [
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'gst_nature'        => $data['gst_nature'],
        ];
    }

    /*--------------------------------------------------------------
    | 3. PREPARE VOUCHER MAIN HEAD
    --------------------------------------------------------------*/
    private function prepareVoucherData(array $data, $serialInfo, $companyId, $financialYearId)
    {
        return [
            'uuid'             => $data['uuid'],
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_date'      => $data['voucher_date'],
            'voucher_type_id'  => VoucherType::DEBIT_NOTE,
            'reference_id'     => null,
            'reference_type'   => Reference::DebitNote,
            'reference_number' => $data['reference_number'] ?? "",
            'voucher_serial'   => $serialInfo->serial,
            'voucher_number'   => $serialInfo->voucher_number,
            'narration'        => $data['narration'],
        ];
    }

    /*--------------------------------------------------------------
    | 4. PREPARE ROWS WITH REFERENCES
    --------------------------------------------------------------*/
    private function prepareVoucherRows(array $rows)
    {
        $final = [];

        $drAgainstId = null;
        $crAgainstId = null;
        $isFirstDr = true;
        $isFirstCr = true;

        foreach ($rows as $r) {
            if ($r['dr_cr'] == 'DR' && $isFirstDr) {
                $drAgainstId = $r['account_id'];
                $isFirstDr = false;
            }

            if ($r['dr_cr'] == 'CR' && $isFirstCr) {
                $crAgainstId = $r['account_id'];
                $isFirstCr = false;
            }

        }

        // Step 2: Build final rows
        $line = 0;
        foreach ($rows as $row) {
            $accountId = $row['account_id'];
            $isParty = $this->isPartyLedger($accountId);
            $transactionType = $row['dr_cr'];

            $againstAccountId = $transactionType == 'DR' ? $crAgainstId : $drAgainstId;

            // Build processed row
            $processed = [
                'account_id'         => $accountId,
                'against_account_id' => $againstAccountId,
                'debit'              => $row['debit_amount'],
                'credit'             => $row['credit_amount'],
                'is_party_account'   => $isParty,
                'line_no'            => ++$line,
            ];
            $final[] = $processed;
        }
        return $final;
    }

    /*--------------------------------------------------------------
    | 5. CREATE OR SETTLE REFERENCES
    --------------------------------------------------------------*/
    private function createOrSettleReferences(array $data, $voucher, $journal, $companyId, $financialYearId)
    {
        $rows = $data['rows'];

        $final = [
            'new_ref' => [],
            'against_ref' => [],
        ];

        foreach ($rows as $r) {
            $references = $r['references'];
            if (empty($references)) {
                continue;
            }

            foreach ($references as $ref) {
                $direction = $r['dr_cr'] == 'DR' ? 'journal_debit' : 'journal_credit';
                $amount = $ref['ref_amount'];

                if ($ref['method'] ==  'new_ref') {
                    Reference::create([
                        'reference_number' => $ref['ref_number'],
                        'reference_date' => $data['voucher_date'],
                        'reference_type' => Reference::NewReference,
                        'file_number' => $ref['file_no'] ?? null,
                        'amount' => $amount,
                        'direction' => config('ref_direction_map.direction.' . $direction),
                        'account_id' => $r['account_id'],
                        'source_type' => SourceType::DEBIT_NOTE,
                        'source_id' => $journal->id,
                        'voucher_id' => $voucher->id,
                        'pending_amount' => $amount,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'created_by' => current_user_id(),
                    ]);
                }

                if ($ref['method'] ==  'against_ref' && $ref['ref_id']) {
                    $referenceRecord = Reference::where('id', $ref['ref_id'])->lockForUpdate()->first();
                    if (!$referenceRecord) continue;

                    $referenceRecord->settled_amount += $ref['ref_amount'];
                    $referenceRecord->pending_amount -= $ref['ref_amount'];
                    $isClosed = round(abs($referenceRecord->amount), 2) <= round($referenceRecord->settled_amount, 2);
                    $referenceRecord->is_closed = $isClosed;
                    $referenceRecord->closed_at = $isClosed ? Carbon::now() : null;
                    $referenceRecord->save();

                    ReferenceAllocation::create([
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_id' => $voucher->id,
                        'reference_id' => $referenceRecord->id,
                        'account_id' => $referenceRecord->account_id,
                        'amount' => $amount,
                        'allocation_type' => ReferenceAllocation::AGAINST_REF,
                        'source_type' => SourceType::DEBIT_NOTE,
                        'source_id' => $referenceRecord->source_id,
                    ]);
                }
            }
        }
        return $final;
    }

    /*--------------------------------------------------------------
    | 6. HELPER – SUM FUNCTION
    --------------------------------------------------------------*/
    private function sumValues(array $rows, string $key)
    {
        return collect($rows)->sum($key);
    }

    private function isPartyLedger($accountId)
    {
        // 1. Fetch account
        $account = Account::select('account_group_id')->find($accountId);

        if (!$account) {
            return false;
        }

        // 2. Fetch account group
        $group = AccountGroup::select('is_party_group')->find($account->account_group_id);

        if (!$group) {
            return false;
        }

        // 3. Return TRUE if this group is marked as party group
        return (bool) $group->is_party_group;
    }

    /*--------------------------------------------------------------
    | 7. HELPER – GET NEXT VOUCHER NUMBER
    --------------------------------------------------------------*/
    public function getNextVoucherNumber($companyId, $financialYearId)
    {
        return $this->voucherService->getNextVoucherNumber(
            VoucherType::DEBIT_NOTE,
            $companyId,
            $financialYearId
        );
    }



    // Update your list method
    public function list($companyId, $financialYearId, $filter = [], $page = 1, $size = 50)
    {
        $voucherTypeId = VoucherType::DEBIT_NOTE;
        $offset = ($page - 1) * $size;

        // Base query for vouchers
        $baseQuery = DB::table('vouchers as v')
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.company_id', $companyId)
            ->where('v.voucher_type_id', $voucherTypeId);

        if (empty($filter['show_deleted'])) {
            $baseQuery->where('v.is_active', 1);
        }

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

        // Get total count of vouchers (not transactions)
        $totalVouchers = $baseQuery->count();
        $lastPage = (int) ceil($totalVouchers / $size);

        // Get paginated voucher IDs
        $voucherIds = (clone $baseQuery)
            ->orderByDesc('v.voucher_serial')
            ->limit($size)
            ->offset($offset)
            ->pluck('v.id')
            ->toArray();

        $permissions = userPermissions([
            'debit_note_voucher.view',
            'debit_note_voucher.update',
            'debit_note_voucher.delete',
            'debit_note_voucher.print-voucher',
        ], true);

        if (empty($voucherIds)) {
            return [
                'data' => [],
                'last_page' => 1,
                'total' => 0,
                'permissions' => $permissions
            ];
        }

        $transactions = DB::table('vouchers as v')
            ->leftJoin('voucher_transactions as vt', 'v.id', '=', 'vt.voucher_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'vt.account_id')
            ->leftJoin('users as created', 'created.id', '=', 'v.created_by')
            ->leftJoin('users as updated', 'updated.id', '=', 'v.updated_by')
            ->leftJoin('users as deleted', 'deleted.id', '=', 'v.deleted_by')
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
                'v.deleted_at',
                'vty.name as voucher_type',
                'vt.account_id',
                'a.name as account_name',
                'vt.debit',
                'vt.credit',
                'vt.narration as short_narration',
                'v.narration as full_narration',

                'created.name as created_by_name',
                'updated.name as updated_by_name',
                'deleted.name as deleted_by_name',
                DB::raw('CASE WHEN 
                    EXISTS(SELECT 1 FROM salaries WHERE salaries.voucher_id = v.id) OR 
                    EXISTS(SELECT 1 FROM multi_expense_voucher_items WHERE multi_expense_voucher_items.voucher_id = v.id) OR 
                    EXISTS(SELECT 1 FROM driver_expenses WHERE driver_expenses.voucher_id = v.id) 
                    THEN 1 ELSE 0 END as is_system_generated'),
            ]);



        $showNarration = !empty($filter['narration']) && $filter['narration'] == '1';
        $data = $this->injectNarrationRows($transactions, $showNarration);

        return [
            'data'        => $data,
            'last_page'   => $lastPage,
            'total'       => $totalVouchers,
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

    public function getVoucherDataForExport($companyId, $financialYearId, array $filter = [])
    {
        $voucherTypeId = VoucherType::DEBIT_NOTE;

        $query = DB::table('vouchers as v')
            ->join('voucher_transactions as vt', 'vt.voucher_id', '=', 'v.id')
            ->leftJoin('accounts as a', 'a.id', '=', 'vt.account_id')
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.company_id', $companyId)
            ->where('v.voucher_type_id', $voucherTypeId)
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
    | CHECK IF VOUCHER IS LOCKED FOR EDITING
    |   A voucher is locked when any new_ref it created has been
    |   partially or fully settled by another voucher (payment/receipt).
    --------------------------------------------------------------*/
    public function isVoucherLocked(int $voucherId): bool
    {
        return Reference::where('voucher_id', $voucherId)
            // ->where('reference_type', Reference::NewReference)
            ->where('settled_amount', '>', 0)
            ->exists();
    }

    /*--------------------------------------------------------------
    | LOCKED REFS — returns the list of settled new_refs so the
    |   frontend can show a meaningful error to the user.
    --------------------------------------------------------------*/
    public function lockedRefs($voucherId): array
    {
        return Reference::where('voucher_id', $voucherId)
            ->where('reference_type', Reference::NewReference)
            ->where('settled_amount', '>', 0)
            ->get(['reference_number', 'amount', 'settled_amount', 'pending_amount'])
            ->toArray();
    }

    public function details($id)
    {
        $voucher = Voucher::withTrashed()->with([
            'details' => fn($q) => $q->orderBy('id'),
            'details.account:id,name'
        ])->findOrFail($id);
        $journal = DebitNoteVoucher::withTrashed()->where('voucher_id', $id)->first();

        // Check if any new_ref created by this voucher has been settled
        $isLocked   = $this->isVoucherLocked($id);
        $lockedRefs = $isLocked ? $this->lockedRefs($id) : [];

        // New refs created by this voucher, grouped by account_id
        $newRefs = Reference::where('voucher_id', $id)
            ->where('reference_type', Reference::NewReference)
            ->get()
            ->groupBy('account_id');

        // Against-ref allocations for this voucher, grouped by account_id
        $againstRefs = ReferenceAllocation::with('reference')
            ->where('voucher_id', $id)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get()
            ->groupBy('account_id');

        $purchaseInvoiceIds = [];
        foreach ($againstRefs as $accountId => $allocs) {
            foreach ($allocs as $alloc) {
                if ($alloc->reference && $alloc->reference->source_type == Reference::PurchaseInvoice) {
                    $purchaseInvoiceIds[] = $alloc->reference->source_id;
                }
            }
        }

        $purchaseInvoices = collect();
        if (!empty($purchaseInvoiceIds)) {
            $purchaseInvoices = PurchaseInvoice::with([
                'details.item:id,name',
                'billSundries'
            ])->whereIn('id', array_unique($purchaseInvoiceIds))->get()->keyBy('id');
        }

        $newRefsArray = [];
        foreach ($newRefs as $accountId => $refs) {
            $newRefsArray[$accountId] = $refs->all();
        }

        $againstRefsArray = [];
        foreach ($againstRefs as $accountId => $allocs) {
            $againstRefsArray[$accountId] = $allocs->all();
        }

        // Attach per-row references so the frontend can restore dataStore
        $voucher->details->each(function ($txn) use (&$newRefsArray, &$againstRefsArray, $purchaseInvoices) {
            $txn->account_name = $txn->account->name ?? '';
            $transactionType = $txn->debit > 0 ? 'DR' : 'CR';
            $txnAmount = $txn->debit > 0 ? $txn->debit : $txn->credit;
            
            $refs = [];
            $accumulatedAmount = 0;
            $accountId = $txn->account_id;

            if (isset($newRefsArray[$accountId])) {
                foreach ($newRefsArray[$accountId] as $key => $ref) {
                    $refDir = $ref->direction ?? null;
                    $expectedDir = $transactionType === 'DR' ? 'debit' : 'credit';
                    
                    if ($refDir === $expectedDir) {
                        $refAmount = abs($ref->amount);
                        $refs[] = [
                            'ref_id'           => null,
                            'method'           => 'new_ref',
                            'ref_number'       => $ref->reference_number,
                            'ref_date'         => $ref->reference_date,
                            'ref_amount'       => $refAmount,
                            'file_no'          => $ref->file_number,
                            'transaction_type' => $transactionType,
                            'show_date'        => '',
                            'qty'              => '',
                            'p_qty'            => '',
                            'cd'               => '',
                            'tds'              => '',
                            'premium'          => '',
                        ];
                        
                        $accumulatedAmount += $refAmount;
                        unset($newRefsArray[$accountId][$key]);
                        
                        if (abs($accumulatedAmount - $txnAmount) < 0.001) break;
                    }
                }
            }

            if (abs($accumulatedAmount - $txnAmount) > 0.001 && isset($againstRefsArray[$accountId])) {
                foreach ($againstRefsArray[$accountId] as $key => $alloc) {
                    $refDir = $alloc->reference->direction ?? null;
                    $expectedDir = $transactionType === 'DR' ? 'credit' : 'debit';
                    
                    if ($refDir === $expectedDir) {
                        $allocAmount = abs($alloc->amount);
                        $refData = [
                            'ref_id'           => $alloc->reference_id,
                            'method'           => 'against_ref',
                            'ref_number'       => $alloc->reference->reference_number ?? '',
                            'ref_date'         => $alloc->reference->reference_date ?? '',
                            'ref_amount'       => $allocAmount,
                            'file_no'          => $alloc->reference->file_number ?? null,
                            'transaction_type' => $transactionType,
                            'show_date'        => '',
                            'qty'              => '',
                            'p_qty'            => '',
                            'cd'               => '',
                            'tds'              => '',
                            'premium'          => '',
                        ];

                        if ($alloc->reference && $alloc->reference->source_type == Reference::PurchaseInvoice) {
                            $invoice = $purchaseInvoices->get($alloc->reference->source_id);
                            if ($invoice) {
                                $refData['show_date'] = $invoice->show_date ?? '';
                                $firstDetail = $invoice->details->first();
                                if ($firstDetail) {
                                    $refData['qty']   = $firstDetail->quantity;
                                    $refData['p_qty'] = $firstDetail->party_quantity;
                                }

                                $cdAmount = 0;
                                $tdsAmount = 0;
                                $premiumAmount = 0;

                                if ($invoice->billSundries) {
                                    foreach ($invoice->billSundries as $sundry) {
                                        $name = strtolower($sundry->name ?? '');
                                        $code = (string)($sundry->code ?? '');

                                        if ($code === '1001' || str_contains($name, 'cd') || str_contains($name, 'cash discount')) {
                                            $cdAmount += abs($sundry->amount);
                                        } elseif ($code === '1009' || str_contains($name, 'tds')) {
                                            $tdsAmount += abs($sundry->amount);
                                        } elseif (str_contains($name, 'premium')) {
                                            $premiumAmount += abs($sundry->amount);
                                        }
                                    }
                                }

                                $refData['cd']      = $cdAmount > 0 ? $cdAmount : '';
                                $refData['tds']     = $tdsAmount > 0 ? $tdsAmount : '';
                                $refData['premium'] = $premiumAmount > 0 ? $premiumAmount : '';
                            }
                        }

                        $refs[] = $refData;
                        $accumulatedAmount += $allocAmount;
                        unset($againstRefsArray[$accountId][$key]);
                        
                        if (abs($accumulatedAmount - $txnAmount) < 0.001) break;
                    }
                }
            }

            $txn->refs = $refs;
        });

        return [
            'voucher'     => $voucher,
            'journal'     => $journal,
            'is_locked'   => $isLocked,
            'locked_refs' => $lockedRefs,
        ];
    }


    public function voucherSerials(int $companyId, int $financialYearId){
        
        return  Voucher::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type_id', VoucherType::DEBIT_NOTE)
            // ->where('journal_entry_from', DebitNoteVoucher::ENTRY_FORM_VOUCHER)
            ->whereNull('deleted_at')
            ->get(['voucher_serial', 'id']);
        
    }

    /*--------------------------------------------------------------
    | UPDATE VOUCHER  (full replace: reverse → update → re-settle)
    --------------------------------------------------------------*/
    public function updateVoucher($voucherId, array $data, $companyId, $financialYearId)
    {
        $this->checkSystemGeneratedVoucher($voucherId);

        $this->validateRows($data['rows'] ?? []);

        return DB::transaction(function () use ($voucherId, $data, $companyId, $financialYearId) {
            $voucherId = (int) $voucherId;

            $voucher = Voucher::with('details.account')->findOrFail($voucherId);
            $journal = DebitNoteVoucher::where('voucher_id', $voucherId)->firstOrFail();

            // Prepare old values for auditing
            $data['old_values'] = [
                'voucher_date'     => $voucher->voucher_date,
                'narration'        => $voucher->narration,
                'gst_nature'       => $journal->gst_nature,
                'bill_date'        => $journal->bill_date,
                'reference_number' => $voucher->reference_number,
                'details'          => $voucher->details->map(function ($detail) {
                    return [
                        'account_id'    => $detail->account_id,
                        'account_name'  => $detail->account?->name ?? 'Unknown',
                        'dr_cr'         => $detail->debit > 0 ? 'DR' : 'CR',
                        'debit_amount'  => number_format((float)$detail->debit, 2, '.', ''),
                        'credit_amount' => number_format((float)$detail->credit, 2, '.', ''),
                    ];
                })->toArray(),
            ];

            /*
             * STEP 1 — Restore original reference amounts.
             *   For every against_ref allocation linked to this voucher:
             *     • Add the allocated amount back to the original reference's pending_amount
             *     • Subtract it from settled_amount
             *     • Reopen the reference if it was closed solely by this voucher
             *   Then delete all reference_allocations rows for this voucher.
             *   Then delete all new_ref Reference records created by this voucher.
             */
            $this->reverseReferences($voucherId);

            /*
             * STEP 2 — Replace voucher_transactions.
             *   Prepare the new rows through the same validation / party-ledger
             *   logic used in create, then hand off to VoucherService which
             *   deletes the old rows and inserts the new ones.
             */
            $rows = $this->prepareVoucherRows($data['rows']);

            $this->voucherService->updateVoucher(
                [
                    'voucher_date' => $data['voucher_date'],
                    'narration'    => $data['narration'] ?? null,
                    'reference_number' => $data['reference_number'] ?? "",
                ],
                $rows,
                $voucherId
            );

            /*
             * STEP 3 — Update the journal record (gst_nature, etc.).
             */
            $journal->update([
                'gst_nature' => $data['gst_nature'],
                'bill_date'  => $data['bill_date'] ?? null,
            ]);

            /*
             * STEP 4 — Re-create references and allocations using the same
             *   logic as createVoucher:
             *     new_ref  → insert a new row in the references table
             *     against_ref → settle the existing reference + insert a
             *                   row in reference_allocations
             */
            $this->createOrSettleReferences($data, $voucher, $journal, $companyId, $financialYearId);



            $freshVoucher = $voucher->fresh();
            
            $this->logAudit($data, $freshVoucher, AuditTrail::ACTION_UPDATE);

            return $freshVoucher;
        });
    }

    /*--------------------------------------------------------------
    | REVERSE REFERENCES — restores the state that existed before
    |   this voucher settled any bills.
    --------------------------------------------------------------*/
    private function reverseReferences(int $voucherId): void
    {
        /*
         * Load every against_ref allocation that belongs to this voucher.
         * Lock the parent reference rows to avoid concurrent modification.
         */
        $allocations = ReferenceAllocation::with(['reference' => fn($q) => $q->lockForUpdate()])
            ->where('voucher_id', $voucherId)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get();

        foreach ($allocations as $alloc) {
            $ref = $alloc->reference;
            if (!$ref) continue;

            /*
             * The original createOrSettleReferences did:
             *   $ref->settled_amount += $ref_amount   (always positive)
             *   $ref->pending_amount -= $ref_amount   (always positive subtracted)
             *
             * $alloc->amount stores the signed direction value, so abs() gives
             * back the positive ref_amount that was originally applied.
             */
            $restoredAmount = abs($alloc->amount);

            $ref->settled_amount = max(0, $ref->settled_amount - $restoredAmount);
            $ref->pending_amount += $restoredAmount;

            /*
             * Re-evaluate closure: closed only when settled_amount fully
             * covers the absolute reference amount.
             */
            $isClosed = round(abs($ref->amount), 2) <= round($ref->settled_amount, 2);
            $ref->is_closed  = $isClosed;
            $ref->closed_at  = $isClosed ? $ref->closed_at : null;

            $ref->save();
        }

        // Delete every reference_allocation row tied to this voucher
        ReferenceAllocation::where('voucher_id', $voucherId)->delete();

        // Delete every new_ref Reference created by this voucher
        Reference::where('voucher_id', $voucherId)
            // ->where('reference_type', Reference::NewReference)
            ->delete();
    }
    
    /*--------------------------------------------------------------
    | DELETE VOUCHER
    --------------------------------------------------------------*/
    public function deleteVoucher(int $voucherId): void
    {
        $this->checkSystemGeneratedVoucher($voucherId);

        DB::transaction(function () use ($voucherId) {
            $voucher        = Voucher::with('details')->findOrFail($voucherId);
            $journalVoucher = DebitNoteVoucher::where('voucher_id', $voucherId)->first();

            $data = [];
            $data['old_values'] = [
                'voucher_date'     => $voucher->voucher_date,
                'narration'        => $voucher->narration,
                'gst_nature'       => $journalVoucher->gst_nature ?? null,
                'bill_date'        => $journalVoucher->bill_date ?? null,
                'reference_number' => $voucher->reference_number,
                'details'          => $voucher->details->map(function ($detail) {
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

            // We do not physically delete VoucherTransaction here.
            // Soft-deleting the Voucher ensures these records are ignored in accounting logic,
            // while preserving them for display (e.g. showing details of deleted vouchers).

            if ($journalVoucher) {
                $journalVoucher->delete();
            }

            $voucher->deleted_by = current_user_id();
            $voucher->is_active  = 0;
            $voucher->save();
            $voucher->delete();

            $this->logAudit($data, $voucher, AuditTrail::ACTION_DELETE);
        });
    }

    /**
     * Check if the voucher was generated by another module.
     * @throws \Exception
     */
    private function checkSystemGeneratedVoucher($voucherId): void
    {
        if (DB::table('salaries')->where('voucher_id', $voucherId)->exists()) {
            throw new \Exception('This voucher was generated by the Salary module. Please update it there.');
        }

        if (DB::table('multi_expense_voucher_items')->where('voucher_id', $voucherId)->exists()) {
            throw new \Exception('This voucher was generated by the Multi Expense module. Please update it there.');
        }

        if (DB::table('driver_expenses')->where('voucher_id', $voucherId)->exists()) {
            throw new \Exception('This voucher was generated by the Driver Expense module. Please update it there.');
        }
    }

    private function logAudit($data, $voucher, $action)
    {
        if(!isAuditLog()){
            return null;
        }

        $version = 0;

        $oldValues = $data['old_values'] ?? [];
        $newValues = [];

        if ($action == AuditTrail::ACTION_CREATE || $action == AuditTrail::ACTION_UPDATE) {
            $newValues = [
                'voucher_no'       => $voucher->voucher_id ?? null,
                'voucher_date'     => $data['voucher_date'] ?? null,
                'narration'        => $data['narration'] ?? null,
                'gst_nature'       => $data['gst_nature'] ?? null,
                'bill_date'        => $data['bill_date'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
            ];

            $accountIds = collect($data['rows'])->pluck('account_id')->filter()->unique()->toArray();
            $accounts = Account::whereIn('id', $accountIds)->pluck('name', 'id');

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
            $allKeys = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));
            
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

        $origionalAmount = collect($oldValues['details'] ?? [])->sum(function($item) { return (float)($item['debit_amount'] ?? 0); });
        $finalAmount = collect($newValues['details'] ?? [])->sum(function($item) { return (float)($item['debit_amount'] ?? 0); });

        $auditData = [
            'company_id'        => $voucher->company_id,
            'financial_year_id' => $voucher->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::DEBIT_NOTE,
            'record_type'       => AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => DebitNoteVoucher::class,
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


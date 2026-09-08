<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\PurchaseInvoice;
use App\Models\DebitNote;
use App\Models\PurchaseOrder;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use App\Repositories\PurchaseOrderRepository;
use Carbon\Carbon;
use App\Models\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PaymentVoucherService
{
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;
    protected CompanyService $companyService;
    protected PurchaseOrderRepository $purchaseOrderRepo;


    public function __construct(VoucherService $voucherService, ReferenceService $referenceService, PurchaseOrderRepository $purchaseOrderRepo, CompanyService $companyService)
    {
        $this->voucherService   = $voucherService;
        $this->referenceService = $referenceService;
        $this->companyService = $companyService;
        $this->purchaseOrderRepo = $purchaseOrderRepo;
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

            $chequeAction = $data['cheque_action'] ?? 'voucher_only'; // 'voucher_only' | 'print_cheque' | 'pass_rtgs'
            $isRtgsFormPrint = false;
            $payment      = null;
            $isPaid      = false;
            $isPassToRTGS = $chequeAction === 'pass_rtgs';
            $isApproved = true; // by default set to true, if approval is required then set to false
            if ($chequeAction === 'print_cheque') {
                $payment = $this->storeChequeDetails($data, $companyId, $financialYearId, $voucher);
                $isPaid = true;
            }
            $isVoucherOnly = false;
            // if Voucher only is selected and approval is not required then set is_paid to true, otherwise it will be set to true when cheque details are stored or when approval is given
            if ($chequeAction === 'voucher_only') {
                $isPaid = true;
                $isVoucherOnly = true;
            }

            // if approval is required before payment then set is_paid and is_approved to false, otherwise set is_paid to true
            if ($chequeAction == 'approval') {
                $isApproved = false;
                $isPassToRTGS = true; // if approval is required then it will be passed to RTGS after approval, so set this to true
            }

            $partyAccountId = $this->findPartyAccountFromRows($data['rows']);
            [$bankId, $bankAmount] = $this->resolveBankFromRows($data['rows'], $data['bank_account_id'] ?? null, (float) ($data['bank_amount'] ?? 0));

            $paymentVoucher = PaymentVoucher::create([
                'voucher_id'        => $voucher->id,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'payment_id'        => $payment ? $payment->id : null,
                'paid_amount'       => $bankAmount,
                'bank_id'           => $bankId,
                'account_id'        => $partyAccountId,
                'is_paid'           => $isPaid,
                'entry_from'        => PaymentVoucher::ENTRY_FROM_PAYMENT_VOUCHER,
                'is_pass_to_rtgs'   => $isPassToRTGS,
                'is_approved'       => $isApproved,
                'is_voucher_only'   => $isVoucherOnly,
            ]);

            $cheque = $data['cheque'] ?? [];
            $secondaryNarration = null;
            if ($chequeAction === 'print_cheque' && !empty($cheque['cheque_date']) && !empty($cheque['cheque_no'])) {
                $secondaryNarration = 'Chq. Dated ' . format_date($cheque['cheque_date']) . ' Chq No. ' . $cheque['cheque_no'];
            }

            Voucher::where('id', $voucher->id)->update([
                'source_type'         => SourceType::PAYMENT,
                'source_id'           => $paymentVoucher->id,
                'narration'           => $data['narration'] ?? null,
                'secondary_narration' => $secondaryNarration ?? '',
            ]);


            $this->createOrSettleReferences($data, $voucher, $paymentVoucher, $companyId, $financialYearId);

            $freshVoucher = $voucher->fresh();

            $this->logAudit($data, $freshVoucher, AuditTrail::ACTION_CREATE);

            // if cheque print is true than prepare HTML and return in response
            $chequePrintHtml = $chequeAction === 'print_cheque' ? (new ChequeService())->buildPrintData($payment->id) : null;
            $isRtgsFormPrint = $chequeAction === 'print_cheque' ? $data['cheque']['rtgs'] == 'Y' ?? false : false;

            $rtgsPrintHtml = null;
            if ($isRtgsFormPrint) {
                $rtgsPrintHtml = (new RtgsService())->buildPrintData([$paymentVoucher->id], $companyId);
            }
            return [
                'voucher'            => $freshVoucher,
                'payment'            => $paymentVoucher->fresh(),
                'cheque_print_html'  => $chequePrintHtml['html'] ?? null,
                'rtgs_form_html'     => $rtgsPrintHtml['html'] ?? null,
                'is_cheque_print'    => $chequeAction === 'print_cheque',
                'is_rtgs_form_print' => $isRtgsFormPrint,
            ];
        });
    }

    public function storeChequeDetails(array $data, $companyId, $financialYearId, $voucher)
    {
        $cheque = $data['cheque'] ?? [];
        return Payment::create([
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'cheque_name'       => $cheque['cheque_name']  ?? null,
            'bank_id'           => $data['bank_account_id'] ?? null,
            'payment_date'      => $voucher->voucher_date,
            'cheque_date'       => $cheque['cheque_date']  ?? null,
            'mode'              => 'cheque',
            'amount'            => $cheque['amount']       ?? null,
            'ac_pay'            => $cheque['ac_pay']       ?? null,
            'cheque_time'       => $cheque['cheque_time']  ?? Carbon::now()->format('H:i:s'),
            'rtgs'              => $cheque['rtgs']         ?? null,
            'cheque_alpha_number' => $cheque['cheque_alpha_number'] ?? null,
            'cheque_number'     => $cheque['cheque_no']    ?? null,
        ]);
    }

    /*--------------------------------------------------------------

    /*--------------------------------------------------------------
    | PREPARE PAYLOAD
    --------------------------------------------------------------*/
    public function prepareVoucherPayload(array $data, $companyId, $financialYearId)
    {
        $serialInfo = $this->voucherService->getNextVoucherNumber(
            VoucherType::PAYMENT,
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
            'voucher_type_id'   => VoucherType::PAYMENT,
            'reference_id'      => null,
            'reference_type'    => Reference::Payment,
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
    private function createOrSettleReferences(array $data, $voucher, $payment, $companyId, $financialYearId)
    {
        foreach ($data['rows'] as $r) {
            $references = $r['references'] ?? [];
            if (empty($references)) continue;

            foreach ($references as $ref) {
                $direction = $r['dr_cr'] === 'DR' ? 'payment_debit' : 'payment_credit';
                $amount    = $ref['ref_amount'];
                if ($ref['method'] === 'new_ref' || ($ref['method'] === 'advance')) {
                    Reference::create([
                        'reference_number'    => $ref['ref_number'],
                        'reference_date'      => $data['voucher_date'],
                        'reference_type'      => $ref['method'] === 'advance' ? Reference::Advance : Reference::NewReference,
                        'file_number'         => $ref['file_no'] ?? null,
                        'amount'              => $amount,
                        'direction'           => config('ref_direction_map.direction.' . $direction),
                        'account_id'          => $r['account_id'],
                        'source_type'         => SourceType::PAYMENT,
                        'source_id'           => $payment->id,
                        'voucher_id'          => $voucher->id,
                        'pending_amount'      => $amount,
                        'company_id'          => $companyId,
                        'financial_year_id'   => $financialYearId,
                        'purchase_order_id'   => $ref['purchase_order_id'] ?? null,
                        'purchase_order_number' => $ref['purchase_order_number'] ?? null,
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
                        'source_type'       => SourceType::PAYMENT,
                        'source_id'         => $referenceRecord->source_id,
                    ]);
                }
            }
        }
    }

    /*--------------------------------------------------------------
    | HELPERS
    --------------------------------------------------------------*/
    private function findPartyAccountFromRows(array $rows): ?int
    {
        $firstDrId = null;
        foreach ($rows as $row) {
            if ($row['dr_cr'] !== 'DR' || empty($row['account_id'])) continue;
            if ($firstDrId === null) $firstDrId = (int) $row['account_id'];
            if ($this->isPartyLedger($row['account_id'])) return (int) $row['account_id'];
        }
        return $firstDrId;
    }

    // Returns [bank_account_id, paid_amount].
    // Uses frontend-provided values when available; falls back to first CR row when not.
    private function resolveBankFromRows(array $rows, ?int $bankId, float $bankAmount): array
    {
        if ($bankId && $bankAmount > 0) return [$bankId, $bankAmount];

        foreach ($rows as $row) {
            if ($row['dr_cr'] !== 'CR' || empty($row['account_id'])) continue;
            $amount = (float) ($row['credit_amount'] ?? 0);
            if ($amount <= 0) continue;
            return [
                $bankId ?? (int) $row['account_id'],
                $bankAmount > 0 ? $bankAmount : $amount,
            ];
        }

        return [$bankId, $bankAmount];
    }

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
            VoucherType::PAYMENT,
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
            ->where('v.voucher_type_id', VoucherType::PAYMENT);

        if (empty($filter['show_deleted'])) {
            $baseQuery->where('v.is_active', 1);
        }

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
            'payment_voucher.view',
            'payment_voucher.update',
            'payment_voucher.delete',
            'payment_voucher.print-voucher',
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

        $transactions = DB::table('vouchers as v')
            ->leftJoin('voucher_transactions as vt', 'v.id', '=', 'vt.voucher_id')
            ->leftJoin('payment_vouchers as pv', 'pv.voucher_id', '=', 'v.id')
            ->leftJoin('payments as p', 'p.id', '=', 'pv.payment_id')
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
                'p.cheque_number',
                'pv.payment_id',
                'pv.is_approved',
                'pv.is_paid',
                'pv.entry_from',
                'pv.is_voucher_only',
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
        $result        = [];
        $lastId        = null;
        $lastNarration = null;

        foreach ($transactions as $row) {
            $row = (array) $row;
            $row['row_type'] = 'transaction';

            if ($row['voucher_id'] !== $lastId) {
                if ($show && $lastId !== null && !empty(trim((string) ($lastNarration ?? '')))) {
                    $result[] = $this->narrationRow($lastId, $lastNarration);
                }
                $lastId        = $row['voucher_id'];
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
            ->where('v.voucher_type_id', VoucherType::PAYMENT)
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
        $payment = PaymentVoucher::with('payment')->where('voucher_id', $id)->first();

        $isLocked   = $this->isVoucherLocked($id);
        $lockedRefs = $isLocked ? $this->lockedRefs($id) : [];

        $newRefs = Reference::where('voucher_id', $id)
            ->whereIn('reference_type', [Reference::NewReference, Reference::Advance])
            ->get()
            ->groupBy('account_id');

        $againstRefs = ReferenceAllocation::with('reference')
            ->where('voucher_id', $id)
            ->whereIn('allocation_type', [ReferenceAllocation::AGAINST_REF, ReferenceAllocation::ADVANCE_ADJUSTMENT])
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

        $voucher->details->each(function ($txn) use ($newRefs, $againstRefs, $purchaseInvoices) {
            $txn->account_name = $txn->account->name ?? '';
            $transactionType = $txn->debit > 0 ? 'DR' : 'CR';
            $refs = [];

            foreach ($newRefs->get($txn->account_id, collect()) as $ref) {
                $refs[] = [
                    'ref_id'                => null,
                    'method'                => $ref->reference_type,
                    'ref_number'            => $ref->reference_number,
                    'ref_date'              => $ref->reference_date,
                    'ref_amount'            => abs($ref->amount),
                    'file_no'               => $ref->file_number,
                    'transaction_type'      => $transactionType,
                    'purchase_order_id'     => $ref->purchase_order_id,
                    'purchase_order_number' => $ref->purchase_order_number,
                    'show_date'             => '',
                    'qty'                   => '',
                    'p_qty'                 => '',
                    'cd'                    => '',
                    'tds'                   => '',
                    'premium'               => '',
                ];
            }

            foreach ($againstRefs->get($txn->account_id, collect()) as $alloc) {
                $refData = [
                    'ref_id'           => $alloc->reference_id,
                    'method'           => $alloc->allocation_type,
                    'ref_number'       => $alloc->reference?->reference_number ?? '',
                    'ref_date'         => $alloc->reference?->reference_date ?? '',
                    'ref_amount'       => abs($alloc->amount),
                    'file_no'          => $alloc->reference?->file_number ?? null,
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
            }

            $txn->refs = $refs;
        });

        // Use already-loaded $newRefs to get purchase_order_ids, then fetch order_serials
        $poIds = $newRefs->flatten()
            ->pluck('purchase_order_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $purchaseOrderSerials = '';
        if (!empty($poIds)) {
            $purchaseOrderSerials = PurchaseOrder::whereIn('id', $poIds)
                ->pluck('order_serial')
                ->filter()
                ->implode(', ');
        }

        return [
            'voucher'                => $voucher,
            'payment'                => $payment,
            'is_locked'              => $isLocked,
            'is_paid'                => (bool) ($payment?->is_paid),
            'entry_from'             => $payment?->entry_from,
            'is_voucher_only'        => (bool) ($payment?->is_voucher_only),
            'locked_refs'            => $lockedRefs,
            'purchase_order_serials' => $purchaseOrderSerials ?: null,
        ];
    }

    public function voucherSerials($companyId, $financialYearId)
    {
        return Voucher::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type_id', VoucherType::PAYMENT)
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
            $payment = PaymentVoucher::with('payment')->where('voucher_id', $voucherId)->firstOrFail();

            // Capture old values for auditing before any changes
            $data['old_values'] = [
                'voucher_date' => $voucher->voucher_date,
                'narration'    => $voucher->narration,
                'cheque_no'    => $payment->payment?->cheque_number ?? null,
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

            $partyAccountId = $this->findPartyAccountFromRows($data['rows']);
            [$bankId, $bankAmount] = $this->resolveBankFromRows($data['rows'], null, 0);
            $payment->account_id = $partyAccountId;
            $payment->bank_id    = $bankId;
            $payment->paid_amount = $bankAmount;
            $payment->save();

            $this->createOrSettleReferences($data, $voucher, $payment, $companyId, $financialYearId);

            $freshVoucher = $voucher->fresh();

            $this->logAudit($data, $freshVoucher, AuditTrail::ACTION_UPDATE);

            return $freshVoucher;
        });
    }

    /*--------------------------------------------------------------
    | DELETE VOUCHER
    --------------------------------------------------------------*/
    public function deleteVoucher(array $voucherIds): void
    {
        DB::transaction(function () use ($voucherIds) {
            $vouchers = Voucher::with('details.account')->whereIn('id', $voucherIds)->get();
            foreach ($vouchers as $voucher) {
                $voucherId      = $voucher->id;
                $paymentVoucher = PaymentVoucher::with('payment')->where('voucher_id', $voucherId)->first();

                // Capture source_id before any deletion — handles legacy vouchers where
                // source_id may be null in the DB and retry scenarios where the payment
                // row was already removed.
                $capturedSourceId = $paymentVoucher?->id ?? $voucher->source_id;

                $deleteData['old_values'] = [
                    'voucher_date' => $voucher->voucher_date,
                    'narration'    => $voucher->narration,
                    'cheque_no'    => $paymentVoucher?->payment?->cheque_number ?? null,
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

                // Cancel associated cheque/payment if any
                if ($paymentVoucher && $paymentVoucher->payment_id) {
                    PaymentVoucher::where('payment_id', $paymentVoucher->payment_id)->update([
                        'payment_id' => null,
                        'is_paid'    => 0,
                        'updated_at' => now(),
                    ]);
                    Payment::withTrashed()
                        ->where('id', $paymentVoucher->payment_id)
                        ->update([
                            'cheque_status' => 'cancelled',
                            'cancelled_by'  => current_user_id(),
                            'cancelled_at'  => now(),
                            'deleted_at'    => now(),
                        ]);
                }

                // Reverse all reference allocations and reopen settled references
                $this->reverseReferences($voucherId);

                // We do not physically delete VoucherTransaction or PaymentVoucher here.
                // Soft-deleting the Voucher ensures these records are ignored in accounting logic,
                // while preserving them for display (e.g. showing details of deleted vouchers).

                // Soft-delete the voucher
                $voucher->deleted_by = current_user_id();
                $voucher->is_active  = 0;
                $voucher->save();
                $voucher->delete();

                if ($paymentVoucher) {
                    $paymentVoucher->delete();
                }

                // Restore captured source_id so logAudit always gets a non-null value.
                $voucher->source_id = $capturedSourceId;

                $this->logAudit($deleteData, $voucher, AuditTrail::ACTION_DELETE);
            }
        });
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

        Reference::where('voucher_id', $voucherId)->update([
            'deleted_by' => current_user_id(),
        ]);
        Reference::where('voucher_id', $voucherId)->delete();
    }

    public function fetchPendingPurchaseOrders(int $companyId, int $financialYearId, ?array $filters = []): Collection
    {
        $mainSelect = ['id', 'order_serial', 'destination_id', 'contract_number', 'order_date', 'due_date', 'status', 'delivery_days', 'order_number', 'broker_id'];
        $detailsSelect = ['id', 'purchase_order_id', 'item_id', 'ordered_qty', 'received_qty', 'rate', 'inclusive_rate', 'cgst_rate', 'sgst_rate', 'igst_rate', 'condition_id'];

        return $this->purchaseOrderRepo->fetchPendingPurchaseOrders($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);
    }

    /*--------------------------------------------------------------
    | RTGS — Save/Update cheque no.
    --------------------------------------------------------------*/
    public function saveUpdateChequeNoForRTGS(array $paymentVoucherIds, array $chequeData, int $companyId, int $financialYearId): void
    {
        DB::transaction(function () use ($paymentVoucherIds, $chequeData, $companyId, $financialYearId) {

            // 1. Calculate total amount and fetch payment_id from vouchers
            $totalChequeAmount = 0;
            $paymentId = null;

            foreach ($paymentVoucherIds as $pvId) {
                $pv = PaymentVoucher::lockForUpdate()->findOrFail($pvId);

                $totalChequeAmount += $pv->paid_amount;

                // Take first non-null payment_id
                if (!$paymentId && $pv->payment_id) {
                    $paymentId = $pv->payment_id;
                }
            }

            // 2. If linked payment exists, check cheque number mismatch
            if ($paymentId) {

                $payment = Payment::findOrFail($paymentId);


                // If cheque number changed -> cancel old payment
                if ($payment->cheque_number != $chequeData['chq_number']) {

                    Payment::withTrashed()
                        ->where('id', $paymentId)
                        ->update([
                            'cheque_status' => 'cancelled',
                            'cancelled_by'  => current_user_id(),
                            'cancelled_at'  => now(),
                            'deleted_at'    => now(),
                        ]);

                    // Force new payment creation
                    $paymentId = null;
                } else {
                    $payment->cheque_date = $chequeData['payment_date'];
                    $payment->ac_pay = $chequeData['ac_payee'] === 'yes' ? 'Y' : 'N';
                    $payment->bank_id = $chequeData['bank_id'];
                    $payment->cheque_name = $chequeData['chq_name'];
                    $payment->cheque_alpha_number = $chequeData['chq_alpha_number'];
                    $payment->cheque_number = $chequeData['chq_number'];
                    $payment->payment_date = $chequeData['payment_date'];
                    $payment->amount = $totalChequeAmount;
                    $payment->cheque_time = now();
                    $payment->save();
                }
            }

            // 3. Create new payment if needed
            if (!$paymentId) {
                $payment = Payment::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'payment_date'      => $chequeData['payment_date'],
                    'mode'              => 'cheque',
                    'ac_pay'            => $chequeData['ac_payee'] === 'yes' ? 'Y' : 'N',
                    'amount'            => $totalChequeAmount,
                    'bank_id'           => $chequeData['bank_id'],
                    'cheque_name'       => $chequeData['chq_name'],
                    'cheque_alpha_number'   => $chequeData['chq_alpha_number'],
                    'cheque_number'     => $chequeData['chq_number'],
                    'rtgs'              => 'Y',
                    'cheque_date'       => $chequeData['payment_date'],
                    'cheque_time'       => now(),
                ]);

                $paymentId = $payment->id;
            }

            // 4. Update payment_id on all vouchers
            foreach ($paymentVoucherIds as $paymentVoucherId) {
                $paymentVoucher = PaymentVoucher::with('payment')
                    ->findOrFail($paymentVoucherId);

                $voucher = Voucher::with('details.account')
                    ->findOrFail($paymentVoucher->voucher_id);

                // Capture old values for audit before making any changes
                $capturedSourceId = $paymentVoucher->id ?? $voucher->source_id;
                $auditData = [
                    'old_values' => [
                        'voucher_date' => $voucher->voucher_date,
                        'narration'    => $voucher->narration,
                        'cheque_no'    => $paymentVoucher->payment?->cheque_number ?? null,
                        'details'      => $voucher->details->map(function ($detail) {
                            return [
                                'account_id'    => $detail->account_id,
                                'account_name'  => $detail->account?->name ?? 'Unknown',
                                'dr_cr'         => $detail->debit > 0 ? 'DR' : 'CR',
                                'debit_amount'  => number_format((float)$detail->debit, 2, '.', ''),
                                'credit_amount' => number_format((float)$detail->credit, 2, '.', ''),
                            ];
                        })->toArray(),
                    ],
                    'voucher_date' => $voucher->voucher_date,
                    'narration'    => $voucher->narration,
                    'cheque'       => ['cheque_no' => $chequeData['chq_number'] ?? null],
                    'rows'         => $voucher->details->map(function ($detail) {
                        return [
                            'account_id'    => $detail->account_id,
                            'dr_cr'         => $detail->debit > 0 ? 'DR' : 'CR',
                            'debit_amount'  => $detail->debit,
                            'credit_amount' => $detail->credit,
                        ];
                    })->toArray(),
                ];

                $paymentVoucher->payment_id = $paymentId;
                $paymentVoucher->is_paid    = true;
                $paymentVoucher->save();

                // update narration for voucher
                Voucher::where('id', $paymentVoucher->voucher_id)->update([
                    'secondary_narration' => 'RTGS Payment by Cheque No. ' . $chequeData['chq_number'] . ' Dated ' . format_date($chequeData['payment_date']),
                    'updated_at'          => now(),
                    'updated_by'          => current_user_id(),
                ]);

                $freshVoucher = $voucher->fresh();
                $freshVoucher->source_id = $capturedSourceId;

                $this->logAudit($auditData, $freshVoucher, AuditTrail::ACTION_UPDATE);
            }
        });
    }

    public function fetchPendingPaymentForRTGSScreen($companyId, $financialYearId, array $filters = []): array
    {
        $rtgsData = PaymentVoucher::with('voucher:voucher_date,id,voucher_serial', 'account:id,name,city,email,mobile_number', 'account.bankDetail:id,account_id,bank_name,bank_branch_name,bank_account_number,bank_ifsc')
            ->where('is_pass_to_rtgs', 1)
            ->where('is_paid', 0)->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('is_approved', 1)
            ->where('bank_id', $filters['bank_id'])
            ->when(isset($filters['file_number']) && !empty($filters['file_number']), function ($q) use ($filters) {
                $q->where('file_number', $filters['file_number']);
            })
            ->whereHas('voucher', function ($q) use ($filters) {
                if (!empty($filters['date'])) {
                    $q->where('voucher_date', $filters['date']);
                }
            })
            ->get([
                'id as payment_voucher_id',
                'voucher_id',
                'account_id',
                'paid_amount',
                'bank_id',
                'payment_id',
                'file_number'
            ]);

        $data = [];
        // dd($rtgsData->toArray());
        foreach ($rtgsData as $index => $item) {
            $data[] = [
                'voucher_serial'     => $item->voucher->voucher_serial ?? null,
                'sr_no'              => $index + 1,
                'file_number'        => $item->file_number ?? null,
                'payee_name'         => $item->account->name ?? null,
                'city'               => $item->account->city ?? null,
                'paid_amount'        => $item->paid_amount ?? 0,
                'payment_date'       => $item->voucher->voucher_date ?? null,
                'bank_name'          => $item->account?->bankDetail?->bank_name ?? null,
                'bank_branch_name'   => $item->account?->bankDetail?->bank_branch_name ?? null,
                'bank_account_number' => $item->account?->bankDetail?->bank_account_number ?? null,
                'bank_ifsc'          => $item->account?->bankDetail?->bank_ifsc ?? null,
                'email'              => $item->account->email ?? null,
                'mobile_number'      => $item->account->mobile_number ?? null,
                'payment_voucher_id' => $item->payment_voucher_id,
                'voucher_id'         => $item->voucher_id,

            ];
        }
        return $data;
    }

    public function getChequePrintDataForRTGS(array $paymentVoucherIds, int $companyId, int $financialYearId): array
    {
        $chequePrintData = [];
        foreach ($paymentVoucherIds as $pvId) {
            $pv = PaymentVoucher::with('voucher')->findOrFail($pvId);
            if (!$pv->payment_id) continue;

            $chequeData = (new ChequeService())->buildPrintData($pv->payment_id);
            if ($chequeData) {
                $chequePrintData['html'] = $chequeData['html'] ?? null;
            }
        }
        return $chequePrintData;
    }

    public function getPaymentRegisterPrint(array $filter, int $companyId, int $financialYearId): array
    {
        // $companyId       = company_id();
        // $financialYearId = financial_year_id();

        $payment = Payment::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('payment_date', $filter['date'])
            ->where('cheque_number', $filter['cheque_no'])
            ->where('bank_id', $filter['bank_id'])
            ->latest('id')
            ->first();
        // dd($payment);
        if (!$payment) {
            return [
                'html' => null,
                'error' => 'NO_PAYMENT_ID_FOUND'
            ];
        }

        $paymentVoucher = PaymentVoucher::where('payment_id', $payment->id)
            ->with('account', 'account.bankDetail')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get()
            ->sortBy(fn($pv) => $pv->account->name ?? '');

        if ($paymentVoucher->isEmpty()) {
            return [
                'html' => null,
                'error' => 'NO_PAYMENT_VOUCHER_FOUND'
            ];
        }

        $registerData = [
            'header' => [],
            'rows' => [],
        ];

        $company = $this->companyService->current($companyId);

        $registerData['header']['company_name']  = $company ? $company->name : '';
        $registerData['header']['cheque_number'] = $payment->cheque_number;
        $registerData['header']['payment_date']     = $payment->payment_date ? format_date($payment->payment_date) : '';


        foreach ($paymentVoucher as $key => $pv) {
            $registerData['header']['file_no'] = $pv->file_number;
            $registerData['rows'][] = [
                'sr_no'           => $key + 1,
                'name'            => $pv->account->name ?? '',
                'bank_name'       => $pv->account->bankDetail->bank_name ?? '',
                'bank_account_no' => $pv->account->bankDetail->bank_account_number ?? '',
                'bank_ifsc_code'  => $pv->account->bankDetail->bank_ifsc ?? '',
                'amount'          => $pv->paid_amount ?? 0,
            ];
        }
        return [
            'html' => view('company.pages.payment-online-rtgs._payment_register_print', ['final_data' => $registerData])->render(),
            'error' => ''
        ];
    }

    public function fetchVouchersByChequeNo(string $chequeNo, int $bankId, string $date, int $companyId, int $financialYearId): array
    {
        $data = [];
        $payment = Payment::where('cheque_number', $chequeNo)->where('payment_date', $date)->where('bank_id', $bankId)->where('company_id', $companyId)->where('financial_year_id', $financialYearId)->first();
        if (!$payment) {
            return $data;
        }

        // dd($payment->id);

        $rtgsData = PaymentVoucher::with([
            'voucher:id,voucher_date,voucher_serial',
            'account:id,name,city,email,mobile_number',
            'account.bankDetail:id,account_id,bank_name,bank_branch_name,bank_account_number,bank_ifsc',
            // 'payment:id,cheque_number,payment_date',
        ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            // ->where('is_pass_to_rtgs', 1)
            // ->where('bank_id', $bankId)
            ->where('payment_id', $payment->id)
            ->get(['id as payment_voucher_id', 'voucher_id', 'account_id', 'paid_amount', 'bank_id', 'payment_id', 'file_number']);


        $data = [];
        foreach ($rtgsData as $index => $item) {
            $data[] = [
                'sr_no'              => $index + 1,
                'voucher_serial'     => $item->voucher->voucher_serial ?? null,
                'file_no'            => $item->file_number ?? null,
                'payee_name'         => $item->account->name ?? null,
                'city'               => $item->account->city ?? null,
                'paid_amount'        => $item->paid_amount ?? 0,
                'file_number'       =>  $item->file_number ?? null,
                'cheque_no'          => $item->payment->cheque_number ?? null,
                'payment_date'       => $item->voucher->voucher_date ?? null,
                'bank_account_number' => $item->account?->bankDetail?->bank_account_number ?? null,
                'bank_name'          => $item->account?->bankDetail?->bank_name ?? null,
                'bank_ifsc'          => $item->account?->bankDetail?->bank_ifsc ?? null,
                'bank_branch_name'   => $item->account?->bankDetail?->bank_branch_name ?? null,
                'email'              => $item->account->email ?? null,
                'mobile_number'      => $item->account->mobile_number ?? null,
                'payment_voucher_id' => $item->payment_voucher_id,
                'voucher_id'         => $item->voucher_id,
            ];
        }
        return $data;
    }

    /*--------------------------------------------------------------
    | PAYMENT REGISTER LIST
    | Only fetches PaymentVouchers that have a payment_id (cheque/RTGS paid)
    --------------------------------------------------------------*/
    public function getPaymentRegisterList(int $companyId, int $financialYearId, array $filters = [], int $page = 1, int $size = 100): array
    {
        $offset = ($page - 1) * $size;

        $query = DB::table('payment_vouchers as pv')
            ->join('payments as p', 'p.id', '=', 'pv.payment_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'pv.account_id')
            ->leftJoin('vouchers as v', 'v.id', '=', 'pv.voucher_id')
            ->leftJoin('users as u', 'u.id', '=', 'v.created_by')
            ->where('pv.company_id', $companyId)
            ->where('pv.financial_year_id', $financialYearId)
            ->whereNotNull('pv.payment_id');

        if (!empty($filters['with_deleted'])) {
            $query->withTrashed(); // include soft-deleted payments
        } else {
            $query->whereNull('p.deleted_at');
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('p.payment_date', [
                Carbon::parse($filters['start_date'])->format('Y-m-d'),
                Carbon::parse($filters['end_date'])->format('Y-m-d'),
            ]);
        }

        if (!empty($filters['account_id'])) {
            $query->where('pv.account_id', $filters['account_id']);
        }

        if (!empty($filters['file_number'])) {
            $query->where('pv.file_number', 'like', '%' . $filters['file_number'] . '%');
        }

        if (!empty($filters['voucher_no'])) {
            $query->where('v.id', $filters['voucher_no']);
        }

        if (!empty($filters['cheque_number'])) {
            $query->where('p.cheque_number', 'like', '%' . $filters['cheque_number'] . '%');
        }

        $total = $query->count();

        $rows = (clone $query)
            ->orderByDesc('p.payment_date')
            ->orderByDesc('pv.id')
            ->offset($offset)
            ->limit($size)
            ->get([
                'pv.id as payment_voucher_id',
                'pv.voucher_id',
                'pv.account_id',
                'pv.paid_amount',
                'pv.file_number',
                'pv.payment_id',
                'p.payment_date',
                'v.voucher_serial as voucher_no',
                'p.cheque_number',
                'p.deleted_at as payment_deleted_at',
                'a.name as party_name',
                'a.city as party_city',
                'v.narration',
                'u.name as creator_name',
            ]);
        $permissions = userPermissions([
            'payment_register.view',
            'payment_register.update',
            'payment_register.print',
            'payment_register.delete',
        ], true);
        return [
            'data'      => $rows,
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
            'permissions' => $permissions,
        ];
    }

    public function printPaymentAdvice(array $paymentVoucherIds, int $companyId, int $financialYearId): array
    {
        if (empty($paymentVoucherIds)) {
            return [];
        }

        $paymentVouchers = PaymentVoucher::with([
            'voucher:id,voucher_serial,voucher_date,narration',
            'voucher.details:id,voucher_id,account_id,debit,credit,is_party_account,line_no',
            'voucher.details.account:id,name',
            'account:id,name,city,email,mobile_number',
            'account.taxDetail:account_id,pan,gst_number',
            'account.bankDetail:account_id,bank_name,bank_account_number,bank_ifsc',
            'bank:id,name',
            'bank.bankDetail:account_id,bank_name,bank_account_number',
            'payment:id,cheque_number,payment_date',
        ])
            ->whereIn('id', $paymentVoucherIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        if ($paymentVouchers->isEmpty()) {
            return [];
        }

        $company = $this->companyService->current($companyId);

        $data['companyData'] = [
            'company_name'  => $company->name ?? '',
            'companyDetail' => [
                'address1' => $company->address_one ?? '',
                'address2' => $company->address_two ?? '',
                'email'    => $company->email ?? '',
            ],

        ];


        $data['voucher'] = [];

        // Batch-load all ReferenceAllocations grouped by voucher_id
        $voucherIds  = $paymentVouchers->pluck('voucher_id')->filter()->values();

        $allocations = ReferenceAllocation::with('reference:id,direction,reference_number,reference_date')
            ->whereIn('voucher_id', $voucherIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get()
            ->groupBy('voucher_id');


        // Batch-load all PurchaseInvoices with details and sundries
        $purchaseInvoiceIds = $allocations->flatten()
            ->where('source_type', 'purchase_invoice')
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();


        $purchaseInvoices = PurchaseInvoice::with(['details.item', 'details.destination', 'billSundries', 'account'])
            ->whereIn('id', $purchaseInvoiceIds)
            ->get()
            ->keyBy('id');

        // Batch-load all DebitNotes (Purchase Returns) with details and sundries
        $purchaseReturnIds = $allocations->flatten()
            ->where('source_type', SourceType::PURCHASE_RETURN)
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        $purchaseReturns = DebitNote::with(['details.item', 'details.destination', 'billSundries', 'account'])
            ->whereIn('id', $purchaseReturnIds)
            ->get()
            ->keyBy('id');

        foreach ($paymentVouchers as $pv) {
            $paymentVoucherId = $pv->id;

            $payingBankName    = $pv->bank?->bankDetail?->bank_name ?? ($pv->bank?->name ?? '');
            $drawnOn           = $payingBankName;

            $data['voucher'][$paymentVoucherId]['accountDetail'] = [
                'payment_voucher_id' => $pv->id,
                'voucher_number'    => $pv->voucher?->voucher_serial,
                'account_name'      => $pv->account->name ?? '',
                'pan'               => $pv->account?->taxDetail?->pan ?? '',
                'city'              => $pv->account->city ?? '',
                'contact'           => $pv->account->mobile_number ?? '',
                'gst_no'            => $pv->account?->taxDetail?->gst_number ?? '',
                'email'             => $pv->account->email ?? '',
                'payment_date'      => $pv->payment?->payment_date ?? $pv->voucher?->voucher_date ?? '',
                'drawn_bank_name'   => $drawnOn,
                'cheque_no'         => $pv->payment?->cheque_number ?? '',
                'bank'              => $pv->account?->bankDetail?->bank_name ?? '',
                'bank_acc_no'       => $pv->account?->bankDetail?->bank_account_number ?? '',
                'bank_IFSCcode'     => $pv->account?->bankDetail?->bank_ifsc ?? '',
                'narration'         => $pv->voucher?->narration ?? '',
                'cheque_amount'     => $pv->paid_amount
            ];

            $pvAllocations = $allocations->get($pv->voucher_id, collect());
            $rows          = [];

            foreach ($pvAllocations as $allocation) {

                // Original reference tells us if this allocation is debit (advance) or credit (bill)
                $isDebitRef = $allocation->reference?->direction === 'debit';
                $allocationAmount = $isDebitRef ? -$allocation->amount : $allocation->amount;

                if ($allocation->source_type === SourceType::PURCHASE && $allocation->source_id) {

                    $invoice = $purchaseInvoices->get($allocation->source_id);
                    if (!$invoice) continue;

                    $details  = $invoice->details;
                    $sundries = $invoice->billSundries;

                    // ---------- Sundries Calculation ----------
                    $sundryAmount = [
                        'cd'      => 0,
                        'cd_per'  => 0,
                        'tds'     => 0,
                        'gst'     => 0,
                        'penalty' => 0,
                        'rebate'  => 0,
                        'premium' => 0,
                        'freight' => 0,
                    ];

                    $sundryMap = [
                        '1001' => 'cd',
                        '1005' => 'freight',
                        '1009' => 'tds',
                        '1007' => 'penalty',
                        '1008' => 'rebate',
                        '1012' => 'premium',
                    ];

                    $gstCodes = ['1002', '1003', '1004'];

                    foreach ($sundries as $sundry) {
                        $code = $sundry->code;

                        if (in_array($code, $gstCodes)) {
                            $sundryAmount['gst'] += $sundry->value;
                        } elseif (isset($sundryMap[$code])) {
                            $sundryAmount[$sundryMap[$code]] = $sundry->value;
                            if ($code === '1001') {
                                $sundryAmount['cd_per'] = $sundry->rate_percent;
                            }
                        }
                    }

                    // ---------- Create Rows ----------
                    $isFirstRow = true;

                    foreach ($details as $d) {

                        if ($isFirstRow) {
                            $rows[] = [
                                'date'        => $invoice->show_date,
                                'bill_no'     => $invoice->reference_number ?? '',
                                'file_no'     => $invoice->file_number ?? '',
                                'p_qty'       => format_number($d->party_quantity, 3),
                                'qty'         => format_number($d->quantity, 3),
                                'rate'        => $d->rate,
                                'amount'      => $allocationAmount,
                                'cd'          => $sundryAmount['cd'],
                                'cd_per'      => $sundryAmount['cd_per'],
                                'tds'         => $sundryAmount['tds'],
                                'gst'         => $sundryAmount['gst'],
                                'penalty'     => $sundryAmount['penalty'],
                                'rebate'      => $sundryAmount['rebate'],
                                'premium'     => $sundryAmount['premium'],
                                'freight'     => $sundryAmount['freight'],
                                'type'        => 'invoice',
                                'particular'  => $invoice->account->name ?? '',
                                'city'        => $invoice->account->city ?? '',
                                'destination' => $d->destination?->name ?? '',
                                'product'     => $d->item?->name ?? '',
                                'balance'     => $allocation->reference?->pending_amount ?? 0,
                            ];
                            $isFirstRow = false;
                        } else {
                            $rows[] = [
                                'date'        => '',
                                'bill_no'     => '',
                                'file_no'     => '',
                                'p_qty'       => format_number($d->party_quantity, 3),
                                'qty'         => format_number($d->quantity, 3),
                                'rate'        => $d->rate,
                                'amount'      => 0,
                                'cd'          => '',
                                'cd_per'      => '',
                                'tds'         => '',
                                'gst'         => '',
                                'penalty'     => '',
                                'rebate'      => '',
                                'premium'     => '',
                                'freight'     => '',
                                'type'        => 'invoice',
                                'particular'  => '',
                                'city'        => '',
                                'destination' => $d->destination?->name ?? '',
                                'product'     => $d->item?->name ?? '',
                                'balance'     => '',
                            ];
                        }
                    }
                } elseif ($allocation->source_type === SourceType::PURCHASE_RETURN && $allocation->source_id) {

                    $debitNote = $purchaseReturns->get($allocation->source_id);
                    if (!$debitNote) continue;

                    $details  = $debitNote->details;
                    $sundries = $debitNote->billSundries;

                    // ---------- Sundries Calculation ----------
                    $sundryAmount = [
                        'cd'      => 0,
                        'cd_per'  => 0,
                        'tds'     => 0,
                        'gst'     => 0,
                        'penalty' => 0,
                        'rebate'  => 0,
                        'premium' => 0,
                        'freight' => 0,
                    ];

                    $sundryMap = [
                        '1001' => 'cd',
                        '1005' => 'freight',
                        '1009' => 'tds',
                        '1007' => 'penalty',
                        '1008' => 'rebate',
                        '1012' => 'premium',
                    ];

                    $gstCodes = ['1002', '1003', '1004'];

                    foreach ($sundries as $sundry) {
                        $code = $sundry->code;

                        if (in_array($code, $gstCodes)) {
                            $sundryAmount['gst'] += $sundry->value;
                        } elseif (isset($sundryMap[$code])) {
                            $sundryAmount[$sundryMap[$code]] = $sundry->value;
                            if ($code === '1001') {
                                $sundryAmount['cd_per'] = $sundry->rate_percent;
                            }
                        }
                    }

                    // ---------- Create Rows ----------
                    $isFirstRow = true;

                    foreach ($details as $d) {

                        if ($isFirstRow) {
                            $rows[] = [
                                'date'        => $debitNote->debit_note_date,
                                'bill_no'     => $debitNote->reference_number ?? '',
                                'file_no'     => '',
                                'p_qty'       => format_number(0, 3),
                                'qty'         => format_number($d->quantity, 3),
                                'rate'        => $d->rate,
                                'amount'      => $allocationAmount,
                                'cd'          => $sundryAmount['cd'],
                                'cd_per'      => $sundryAmount['cd_per'],
                                'tds'         => $sundryAmount['tds'],
                                'gst'         => $sundryAmount['gst'],
                                'penalty'     => $sundryAmount['penalty'],
                                'rebate'      => $sundryAmount['rebate'],
                                'premium'     => $sundryAmount['premium'],
                                'freight'     => $sundryAmount['freight'],
                                'type'        => 'purchase_return',
                                'particular'  => $debitNote->account->name ?? '',
                                'city'        => $debitNote->account->city ?? '',
                                'destination' => $d->destination?->name ?? '',
                                'product'     => $d->item?->name ?? '',
                                'balance'     => $allocation->reference?->pending_amount ?? 0,
                            ];
                            $isFirstRow = false;
                        } else {
                            $rows[] = [
                                'date'        => '',
                                'bill_no'     => '',
                                'file_no'     => '',
                                'p_qty'       => format_number(0, 3),
                                'qty'         => format_number($d->quantity, 3),
                                'rate'        => $d->rate,
                                'amount'      => 0,
                                'cd'          => '',
                                'cd_per'      => '',
                                'tds'         => '',
                                'gst'         => '',
                                'penalty'     => '',
                                'rebate'      => '',
                                'premium'     => '',
                                'freight'     => '',
                                'type'        => 'purchase_return',
                                'particular'  => '',
                                'city'        => '',
                                'destination' => $d->destination?->name ?? '',
                                'product'     => $d->item?->name ?? '',
                                'balance'     => '',
                            ];
                        }
                    }
                } elseif ($allocation->allocation_type === ReferenceAllocation::ADVANCE_ADJUSTMENT) {
                    $rows[] = [
                        'date'    => $allocation->reference?->reference_date ?? '',
                        'bill_no' => $allocation->reference_number ?? 'ADVANCE',
                        'amount'  => $allocationAmount ?? 0,
                        'particular' => $allocation->account->name ?? '',
                        'city' => $allocation->account->city ?? '',
                        'p_qty'   => '0.000',
                        'qty'     => '0.000',
                        'rate'    => '0.00',
                        'freight' => '0.00',
                        'cd'      => '0.00',
                        'gst'     => '0.00',
                        'tds'     => '0.00',
                        'premium' => '0.00',
                        'rebate'  => '0.00',
                        'penalty' => '0.00',
                        'type'    => 'advance',
                    ];
                } elseif ($allocation->allocation_type === ReferenceAllocation::OnAccount) {
                    $rows[] = [
                        'date'    => $allocation->reference?->reference_date ?? '',
                        'bill_no' => $allocation->reference_number ?? 'ON ACCOUNT',
                        'amount'  => $allocationAmount ?? 0,
                        'p_qty'   => '0.000',
                        'particular' => $allocation->account?->name ?? '',
                        'city' => $allocation->account?->city ?? '',
                        'qty'     => '0.000',
                        'rate'    => '0.00',
                        'freight' => '0.00',
                        'cd'      => '0.00',
                        'gst'     => '0.00',
                        'tds'     => '0.00',
                        'premium' => '0.00',
                        'rebate'  => '0.00',
                        'penalty' => '0.00',
                        'type'    => 'on_account',
                    ];
                } else {
                    $originalRef = Reference::where('id', $allocation->reference_id)->first();
                    $rows[] = [
                        'date'    => $allocation->reference?->reference_date ?? '',
                        'bill_no' => $allocation->reference_number ?? '',
                        'amount'  => $allocationAmount ?? 0,
                        'p_qty'   => '0.000',
                        'qty'     => '0.000',
                        'particular' => $allocation->account?->name ?? '',
                        'city' => $allocation->account?->city ?? '',
                        'rate'    => '0.00',
                        'freight' => '0.00',
                        'cd'      => '0.00',
                        'gst'     => '0.00',
                        'tds'     => '0.00',
                        'premium' => '0.00',
                        'rebate'  => '0.00',
                        'penalty' => '0.00',
                        'type'    => 'invoice',
                    ];
                }
            }

            // fetching only payment reference form Ref Table
            $references = Reference::with('account')->where('voucher_id', $pv->voucher_id)
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->where('source_type', SourceType::PAYMENT)
                ->where('reference_type', Reference::Advance)
                ->get()
                ->groupBy('voucher_id');

            foreach ($references as $voucherId =>  $advanceRef) {
                foreach ($advanceRef as $adv) {
                    $rows[] = [
                        'date'    => $adv->reference_date ?? '',
                        'bill_no' => $adv->reference_number ?? 'ON ACCOUNT',
                        'amount'  => $adv->amount ?? 0,
                        'p_qty'   => '0.000',
                        'qty'     => '0.000',
                        'rate'    => '0.00',
                        'particular' => $adv->account?->name ?? '',
                        'city' => $adv->account?->city ?? '',
                        'freight' => '0.00',
                        'cd'      => '0.00',
                        'gst'     => '0.00',
                        'tds'     => '0.00',
                        'premium' => '0.00',
                        'rebate'  => '0.00',
                        'penalty' => '0.00',
                        'type'    => 'on_account_original_ref',
                    ];
                }
            }

            $data['voucher'][$paymentVoucherId]['rows'] = $rows;

            // Build particulars: non-party, non-bank voucher transaction rows

            $count = $pv->voucher->details->count();

            if ($count == 3) {
                foreach ($pv->voucher->details ?? [] as $txn) {
                    if ($txn->line_no == 2) {
                        $data['voucher'][$paymentVoucherId]['particular'] = [
                            'particular_name' => $txn->account->name ?? '',
                            'amount'          => $txn->credit,
                            'direction'       => 'Cr',
                        ];
                    }
                }
            }
        }
        return $data;
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
            $paymentVoucher = \App\Models\PaymentVoucher::where('voucher_id', $voucher->id)->first();

            $newValues = [
                'voucher_no'   => $voucher->voucher_id ?? null,
                'voucher_date' => $data['voucher_date'] ?? null,
                'narration'    => $data['narration'] ?? null,
                'cheque_no'    => $data['cheque']['cheque_no'] ?? null,
                // 'approved_by'  => $paymentVoucher && $paymentVoucher->is_approved ? (current_user()?->name ?? 'System') : 'Pending',
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
        $finalAmount = collect($newValues['details'] ?? [])->sum(function ($item) {
            return (float)($item['debit_amount'] ?? 0);
        });

        $auditData = [
            'company_id'        => $voucher->company_id,
            'financial_year_id' => $voucher->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::PAYMENT,
            'record_type'       => AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => PaymentVoucher::class,
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

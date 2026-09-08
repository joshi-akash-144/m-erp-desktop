<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\PaymentVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\TransportPaymentRelease;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransportReferencePaymentService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function store(array $data, int $companyId, int $financialYearId): TransportPaymentRelease
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {

            $bankId      = (int) $data['bank_id'];
            $bankAccount = \App\Models\Account::find($bankId);
            $isCashAccount = $bankAccount && $bankAccount->code == '12000';
            
            $paymentDate = $data['payment_date'];
            $refIds      = $data['ref_ids'];
            $uuid        = $data['uuid'] ?? null;

            // ── Idempotency guard ────────────────────────────────────────────
            // If a release with this UUID already exists, return it immediately
            // to prevent double-submission on network retry or button re-click.
            if ($uuid) {
                $existing = TransportPaymentRelease::where('uuid', $uuid)
                    ->where('company_id', $companyId)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            // Lock and load all selected references
            $references = Reference::with('account:id,name')
                ->whereIn('id', $refIds)
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->where('is_closed', 0)
                ->where('pending_amount', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($references->isEmpty()) {
                throw new \Exception('No valid open references found for the selected items.');
            }

            // Group references by account_id and sort alphabetically by account name
            $grouped = $references->groupBy('account_id')->sortBy(function ($refs) {
                return strtolower(trim($refs->first()->account->name ?? ''));
            });

            $grandTotal      = 0;
            $paymentVoucherIds = [];

            foreach ($grouped as $accountId => $refs) {
                $accountTotal = 0;
                foreach ($refs as $ref) {
                    $payAmt = (float) ($data['pay_amounts'][$ref->id] ?? 0);
                    if ($ref->direction === 'debit') {
                        $accountTotal -= $payAmt;
                    } else {
                        $accountTotal += $payAmt;
                    }
                }

                if ($accountTotal < 0) {
                    $partyName = $refs->first()->account->name ?? 'Unknown Party';
                    throw new \Exception("Net payment amount for {$partyName} is negative. Cannot process as a Payment Voucher.");
                }

                // Instead of skipping when $accountTotal == 0, we allow it to create a settlement voucher.
                // If it is 0, it means it's a mutual adjustment (e.g., advance vs bill), so no actual bank transfer is needed.
                $isPaid = $accountTotal == 0 || $isCashAccount;
                $isPassToRtgs = $accountTotal > 0 && !$isCashAccount;
                $isApproved = $accountTotal > 0 || $isCashAccount;
                $isVoucherOnly = $isCashAccount;

                $grandTotal += $accountTotal;

                // Build voucher lines: DR party account, CR bank
                $voucherLines = [
                    [
                        'account_id'         => $accountId,
                        'against_account_id' => $bankId,
                        'debit'              => $accountTotal,
                        'credit'             => 0,
                        'is_party_account'   => true,
                        'line_no'            => 1,
                    ]
                ];

                $creditAmount = $accountTotal;
                $ledgerId = $data['ledger_id'] ?? null;
                $ledgerAmount = (float)($data['ledger_amount'] ?? 0);

                if ($ledgerId && $ledgerAmount > 0) {
                    $creditAmount = $accountTotal - $ledgerAmount;

                    $voucherLines[] = [
                        'account_id'         => $ledgerId,
                        'against_account_id' => $accountId,
                        'debit'              => 0,
                        'credit'             => $ledgerAmount,
                        'is_party_account'   => false,
                        'line_no'            => 2,
                    ];

                    $grandTotal -= $ledgerAmount;
                }

                $voucherLines[] = [
                    'account_id'         => $bankId,
                    'against_account_id' => $accountId,
                    'debit'              => 0,
                    'credit'             => $creditAmount,
                    'is_party_account'   => false,
                    'line_no'            => 3,
                ];


                $serialInfo = $this->voucherService->getNextVoucherNumber(
                    VoucherType::PAYMENT,
                    $companyId,
                    $financialYearId
                );

                $voucherMaster = [
                    'uuid'              => (string) Str::uuid(),
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'voucher_date'      => $paymentDate,
                    'voucher_type_id'   => VoucherType::PAYMENT,
                    'source_type'       => SourceType::PAYMENT,
                    'source_id'         => null,
                    'reference_number'  => '',
                    'voucher_serial'    => $serialInfo->serial,
                    'voucher_number'    => $serialInfo->voucher_number,
                    'narration'         => $data['narration'],
                    'created_by'        => current_user_id(),
                ];

                $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

                $paymentVoucher = PaymentVoucher::create([
                    'voucher_id'        => $voucher->id,
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'bank_id'           => $bankId,
                    'account_id'        => $accountId,
                    'paid_amount'       => $creditAmount,
                    'is_approved'       => $isApproved,
                    'approved_by'       => $isApproved ? current_user_id() : null,
                    'approved_at'       => $isApproved ? Carbon::now() : null,
                    'is_pass_to_rtgs'   => $isPassToRtgs,
                    'is_paid'           => $isPaid,
                    'entry_from'        => PaymentVoucher::ENTRY_FROM_PAYMENT_VOUCHER,
                    'is_transport_payment' => true,
                    'is_voucher_only'   => $isVoucherOnly,
                ]);

                $voucher->update(['source_id' => $paymentVoucher->id]);

                // Settle each reference
                foreach ($refs as $ref) {
                    $settleAmount = (float) ($data['pay_amounts'][$ref->id] ?? 0);

                    if ($settleAmount <= 0) continue;

                    if (round($settleAmount, 2) > round($ref->pending_amount, 2)) {
                        throw new \Exception("Settlement amount ({$settleAmount}) cannot be greater than the pending amount ({$ref->pending_amount}) for reference {$ref->reference_number}.");
                    }

                    $ref->settled_amount += $settleAmount;
                    $ref->pending_amount  -= $settleAmount;

                    if ($ref->pending_amount <= 0) {
                        $ref->pending_amount = 0;
                        $ref->is_closed = true;
                        $ref->closed_at = Carbon::now();
                    }
                    $ref->save();

                    ReferenceAllocation::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_id'        => $voucher->id,
                        'reference_number'  => $ref->reference_number,
                        'reference_id'      => $ref->id,
                        'account_id'        => $ref->account_id,
                        'amount'            => $settleAmount,
                        'allocation_type'   => ReferenceAllocation::AGAINST_REF,
                        'source_type'       => SourceType::PAYMENT,
                        'source_id'         => $paymentVoucher->id,
                    ]);
                }

                $paymentVoucherIds[] = $paymentVoucher->id;

                $freshVoucher = $voucher->fresh(['details.account']);
                $this->logPaymentAudit($freshVoucher, \App\Models\AuditTrail::ACTION_CREATE);
            }

            // Create a single TransportPaymentRelease for this batch
            $release = TransportPaymentRelease::create([
                'uuid'              => $uuid,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'payment_date'      => $paymentDate,
                'bank_id'           => $bankId,
                'total_amount'      => $grandTotal,
                'created_by'        => current_user_id(),
            ]);

            // Link all payment vouchers to the release
            PaymentVoucher::whereIn('id', $paymentVoucherIds)->update([
                'transport_payment_release_id' => $release->id,
            ]);

            return $release;
        });
    }

    public function getRegisterList(int $companyId, int $financialYearId, array $filters = [], int $page = 1, int $size = 50): array
    {
        $releaseQuery = TransportPaymentRelease::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if (!empty($filters['from_date'])) {
            $releaseQuery->whereDate('payment_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $releaseQuery->whereDate('payment_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['bank_id'])) {
            $releaseQuery->where('bank_id', $filters['bank_id']);
        }

        $total    = $releaseQuery->count();
        $lastPage = (int) ceil($total / max($size, 1));
        $offset   = ($page - 1) * $size;

        $releases = (clone $releaseQuery)
            ->with(['bank:id,name,code', 'creator:id,name'])
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($size)
            ->get();

        $releaseIds = $releases->pluck('id')->toArray();

        // Live (non-deleted) PVs — used for paid / pending / adjusted counts
        $pvByRelease = PaymentVoucher::with([
            'account:id,name,city',
            'payment',
        ])
            ->whereIn('transport_payment_release_id', $releaseIds)
            ->orderBy('id')
            ->get()
            ->groupBy('transport_payment_release_id');

        // Soft-deleted PVs — counted separately for the "Deleted" badge
        $deletedCountByRelease = PaymentVoucher::onlyTrashed()
            ->whereIn('transport_payment_release_id', $releaseIds)
            ->get(['id', 'transport_payment_release_id'])
            ->groupBy('transport_payment_release_id')
            ->map(fn($g) => $g->count());

        $data = [];
        $srNo = $offset + 1;

        foreach ($releases as $release) {
            $vouchers      = $pvByRelease->get($release->id, collect());
            $deletedCount  = $deletedCountByRelease->get($release->id, 0);

            $paidCount     = 0;
            $adjustedCount = 0;

            $isCashAccount = $release->bank?->code == '12000';

            foreach ($vouchers as $pv) {
                if ((float)$pv->paid_amount == 0) {
                    $adjustedCount++;
                } elseif (!empty($pv->payment?->cheque_number) || $pv->is_voucher_only || $pv->is_paid || $isCashAccount) {
                    $paidCount++;
                }
            }

            $liveCount    = $vouchers->count();
            $pendingCount = $liveCount - $paidCount - $adjustedCount;


            $data[] = [
                'id'             => $release->id,
                'sr_no'          => $srNo++,
                'payment_date'   => $release->payment_date ? date('d-m-Y', strtotime($release->payment_date)) : '',
                'bank_name'      => $release->bank?->name ?? '',
                'total_amount'   => (float) $release->total_amount,
                'vouchers_count' => $liveCount + $deletedCount,
                'paid_count'     => $paidCount,
                'pending_count'  => $pendingCount,
                'adjusted_count' => $adjustedCount,
                'deleted_count'  => $deletedCount,
                'created_by'     => $release->creator?->name ?? '',
            ];
        }

        return ['data' => $data, 'total' => $total, 'last_page' => $lastPage ?: 1];
    }

    public function getRegisterDetail(int $releaseId, int $companyId): array
    {
        $release = TransportPaymentRelease::with('bank')->findOrFail($releaseId);

        // Live payment vouchers
        $paymentVouchers = PaymentVoucher::with([
            'voucher:id,voucher_number,voucher_serial,voucher_date',
            'account:id,name,city',
            'payment',
        ])
            ->where('transport_payment_release_id', $releaseId)
            ->where('company_id', $companyId)
            ->get();

        // Soft-deleted payment vouchers (voucher was deleted)
        $deletedPaymentVouchers = PaymentVoucher::onlyTrashed()
            ->with(['account:id,name,city'])
            ->where('transport_payment_release_id', $releaseId)
            ->where('company_id', $companyId)
            ->get();

        $voucherIds = $paymentVouchers->pluck('voucher_id')->filter()->toArray();

        $allocations = ReferenceAllocation::with('reference:id,reference_number,reference_date,amount')
            ->whereIn('voucher_id', $voucherIds)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get()
            ->groupBy('voucher_id');

        $vouchers = $paymentVouchers->map(function ($pv) use ($allocations) {
            $refs = $allocations->get($pv->voucher_id, collect())->map(fn($a) => [
                'reference_number' => $a->reference->reference_number ?? '',
                'reference_date'   => $a->reference->reference_date
                    ? date('d-m-Y', strtotime($a->reference->reference_date))
                    : '',
                'amount'           => number_format((float) $a->amount, 2, '.', ''),
            ])->values()->toArray();

            return [
                'voucher_id'     => $pv->voucher_id ?? '',
                'voucher_number' => $pv->voucher->voucher_number ?? '',
                'voucher_serial' => $pv->voucher->voucher_serial ?? '',
                'party_name'     => $pv->account->name ?? '',
                'party_city'     => $pv->account->city ?? '',
                'paid_amount'    => number_format((float) $pv->paid_amount, 2, '.', ''),
                'cheque_number'  => $pv->payment?->cheque_number ?? '',
                'references'     => $refs,
                'is_deleted'     => false,
            ];
        })->values()->toArray();

        // Append deleted voucher rows
        foreach ($deletedPaymentVouchers as $pv) {
            $vouchers[] = [
                'voucher_number' => '',
                'voucher_serial' => '',
                'party_name'     => $pv->account->name ?? '',
                'party_city'     => $pv->account->city ?? '',
                'paid_amount'    => number_format((float) $pv->paid_amount, 2, '.', ''),
                'cheque_number'  => '',
                'references'     => [],
                'is_deleted'     => true,
            ];
        }

        return [
            'id'           => $release->id,
            'payment_date' => $release->payment_date ? date('d-m-Y', strtotime($release->payment_date)) : '',
            'bank_name'    => $release->bank->name ?? '',
            'total_amount' => number_format((float) $release->total_amount, 2, '.', ''),
            'vouchers'     => $vouchers,
        ];
    }

    public function getRegisterPrintData(int $releaseId, int $companyId): array
    {
        $release = TransportPaymentRelease::with('bank', 'creator')->findOrFail($releaseId);

        $paymentVouchers = PaymentVoucher::with([
            'voucher:id,voucher_number,voucher_serial,voucher_date',
            'account:id,name,city',
            'payment',
        ])
            ->where('transport_payment_release_id', $releaseId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get();

        $voucherIds = $paymentVouchers->pluck('voucher_id')->filter()->toArray();

        $allocations = ReferenceAllocation::with('reference:id,reference_number,reference_date,amount,pending_amount,settled_amount')
            ->whereIn('voucher_id', $voucherIds)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get()
            ->groupBy('voucher_id');

        $parties = [];
        $grandTotal = 0;
        $srNo = 1;

        foreach ($paymentVouchers as $pv) {
            $refs = $allocations->get($pv->voucher_id, collect())->map(function ($a) use (&$srNo) {
                return [
                    'sr_no'            => $srNo++,
                    'reference_number' => $a->reference->reference_number ?? '',
                    'reference_date'   => $a->reference->reference_date
                        ? date('d-m-Y', strtotime($a->reference->reference_date))
                        : '',
                    'amount'           => (float) $a->amount,
                ];
            })->values()->toArray();

            $partyTotal = (float) $pv->paid_amount;
            $grandTotal += $partyTotal;

            $isCashAccount = $release->bank?->code == '12000';

            $parties[] = [
                'party_name'     => $pv->account->name ?? '',
                'party_city'     => $pv->account->city ?? '',
                'voucher_number' => $pv->voucher->voucher_number ?? '',
                'voucher_serial' => $pv->voucher->voucher_serial ?? '',
                'cheque_number'  => $pv->payment?->cheque_number ?? '',
                'payment_status' => (!empty($pv->payment?->cheque_number) || $pv->is_voucher_only || $pv->is_paid || $isCashAccount) ? 'Paid' : 'Pending',
                'references'     => $refs,
                'party_total'    => $partyTotal,
            ];
        }

        return [
            'release_id'   => $release->id,
            'payment_date' => $release->payment_date ? date('d/m/Y', strtotime($release->payment_date)) : '',
            'bank_name'    => $release->bank->name ?? '',
            'created_by'   => $release->creator->name ?? '',
            'grand_total'  => $grandTotal,
            'parties'      => $parties,
        ];
    }

    private function buildPaymentAuditValues($voucher)
    {
        $details = [];
        foreach ($voucher->details as $row) {
            $details[] = [
                'account_id'    => $row->account_id,
                'account_name'  => $row->account?->name ?? 'Unknown',
                'dr_cr'         => $row->debit > 0 ? 'DR' : 'CR',
                'debit_amount'  => number_format((float)($row->debit ?? 0), 2, '.', ''),
                'credit_amount' => number_format((float)($row->credit ?? 0), 2, '.', ''),
            ];
        }

        $paymentVoucher = \App\Models\PaymentVoucher::where('voucher_id', $voucher->id)->first();

        return [
            'voucher_no'       => $voucher->id,
            'voucher_date'     => $voucher->voucher_date,
            'narration'        => $voucher->narration,
            'cheque_no'        => null,
            'approved_by'      => $paymentVoucher && $paymentVoucher->is_approved ? (current_user()?->name ?? 'System') : 'Pending',
            'details'          => $details,
        ];
    }

    private function logPaymentAudit($voucher, $action, $oldValues = [])
    {
        if (!\isAuditLog()) return;

        $newValues = [];
        if ($action == \App\Models\AuditTrail::ACTION_CREATE || $action == \App\Models\AuditTrail::ACTION_UPDATE) {
            $newValues = $this->buildPaymentAuditValues($voucher);
        }

        if ($action == \App\Models\AuditTrail::ACTION_UPDATE) {
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
                return;
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
            'module'            => \App\Enums\SourceType::PAYMENT,
            'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => \App\Models\PaymentVoucher::class,
            'source_id'         => $voucher->source_id ?? null,
            'voucher_id'        => $voucher->id,
            'reference_number'  => $voucher->reference_number,
            'org_amount'        => $origionalAmount,
            'final_amount'      => $finalAmount,
            'version'           => 0,
            'http_method'       => request()->method(),
            'user_id'           => current_user_id(),
            'user_name'         => current_user()?->name ?? 'System',
            'ip_address'        => (request()->header('CF-Connecting-IP') ?? request()->header('True-Client-IP') ?? request()->header('X-Real-IP') ?? trim(explode(',', request()->header('X-Forwarded-For', ''))[0]) ?: request()->ip()),
            'user_agent'        => request()->userAgent(),
            'request_url'       => request()->fullUrl(),
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ];

        app(\App\Services\AuditService::class)->log($auditData);
    }
    public function getVoucherPrintData(int $voucherId, int $companyId): array
    {
        $paymentVoucher = PaymentVoucher::with([
            'voucher:id,voucher_number,voucher_serial,voucher_date,narration',
            'voucher.details.account',
            'account:id,name,city',
            'payment',
            'bank'
        ])
            ->where('voucher_id', $voucherId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $allocations = ReferenceAllocation::with('reference:id,reference_number,reference_date,amount')
            ->where('voucher_id', $voucherId)
            ->where('allocation_type', ReferenceAllocation::AGAINST_REF)
            ->get();

        $items = $allocations->map(function ($a) {
            return [
                'ref_no' => $a->reference->reference_number ?? '',
                'ref_date' => $a->reference->reference_date ? date('d-m-Y', strtotime($a->reference->reference_date)) : '',
                'particular' => '',
                'amount' => (float) $a->amount,
            ];
        })->sortBy('ref_no', SORT_NATURAL)->values()->toArray();

        $voucherParticular = null;
        if ($paymentVoucher->voucher && $paymentVoucher->voucher->details) {
            $ledgerDetail = $paymentVoucher->voucher->details->firstWhere('line_no', 2);
            if ($ledgerDetail && $ledgerDetail->credit > 0) {
                $voucherParticular = [
                    'particular_name' => $ledgerDetail->account->name ?? '',
                    'amount' => number_format((float) $ledgerDetail->credit, 2, '.', '')
                ];
            }
        }

        return [
            'voucher' => clone $paymentVoucher,
            'items' => $items,
            'bank_name' => $paymentVoucher->bank?->name ?? 'Bank',
            'payment_date' => $paymentVoucher->voucher?->voucher_date ? date('d-m-Y', strtotime($paymentVoucher->voucher->voucher_date)) : '',
            'total_amount' => (float) $paymentVoucher->paid_amount,
            'narration' => $paymentVoucher->voucher?->narration ?? '',
            'voucherParticular' => $voucherParticular,
        ];
    }
}

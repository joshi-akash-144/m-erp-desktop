<?php

namespace App\Services;

use App\Enums\RefType;
use App\Enums\SourceType;
use App\Models\Account;
use App\Models\PaymentVoucher;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceSundry;
use App\Models\DebitNote;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\SalesInvoice;
use App\Models\UniqueToken;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use App\Repositories\ReferencesRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReferenceService
{
    protected VoucherService $voucherService;
    protected ReferencesRepository $referenceRepo;
    protected MasterDataService $masterDataService;
    protected AccountBalanceService $accountBalanceService;

    public function __construct(VoucherService $voucherService, ReferencesRepository $referenceRepo, MasterDataService $masterDataService, AccountBalanceService $accountBalanceService)
    {
        $this->voucherService = $voucherService;
        $this->referenceRepo = $referenceRepo;
        $this->masterDataService = $masterDataService;
        $this->accountBalanceService = $accountBalanceService;
    }

    public function  createReference($data, $companyId, $financialYearId)
    {
        $this->referenceRepo->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'reference_number' => $data['reference_number'],
            'reference_date' => $data['reference_date'],
            // 'parent_voucher_id' => 1,
            'reference_type' => 'new_ref',
            'file_number' => $data['file_number'],
            'direction' => $data['direction'],
            'amount' => $data['amount'],
            'account_id' => $data['account_id'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'voucher_id' => $data['voucher_id'],
            'pending_amount' => $data['pending_amount'],
            'created_by' => current_user_id(),

        ]);
    }

    public function updateReference($voucherId, $referenceData): void
    {

        $reference = $this->referenceRepo->getRefBySourceTypeAndSourceId($voucherId);

        if ($reference) {
            $reference->account_id          = $referenceData['account_id'];
            $reference->reference_date      = $referenceData['reference_date'];
            $reference->file_number         = $referenceData['file_number'];
            $reference->reference_number    = $referenceData['reference_number'];
            $reference->amount              = $referenceData['amount'];
            $reference->pending_amount      = $referenceData['amount'] - $reference->settled_amount;
            $reference->is_closed           = $reference->pending_amount == 0;
            $reference->updated_by          = current_user_id();
            $reference->save();
        }
    }

    public function holdBills(array $ids): void
    {
        Reference::whereIn('id', $ids)->update(['is_hold' => 1]);
    }

    public function getPendingReferenceByAccountId($accountId)
    {
        return  $this->referenceRepo->getPendingReferenceByAccountId($accountId);
    }

    public function getPayable($filter = [])
    {
        $companyId = $filter['company_id'];

        $allAccountIds = $this->masterDataService->getCreditors($companyId)->pluck('id')->toArray();

        // Single bulk query instead of N queries per account
        [$crAccountIds, $drAccountIds] = $this->partitionAccountsByBalance(
            $companyId, $filter['financial_year_id'], $allAccountIds
        );

        $filterBy   = $filter['filter_by'] ?? null;
        $onAdvance  = $filter['on_advance'] ?? false;

        $q = Reference::with('account:id,name,city')
            ->where('company_id', $companyId)
            ->when($filter['account_id'] ?? null, fn($q) => $q->where('account_id', $filter['account_id']));

        match (true) {
            $filterBy === 'all' && !$onAdvance =>
                $q->whereIn('account_id', $allAccountIds)->where('reference_type', 'new_ref'),

            $filterBy === 'all' && $onAdvance =>
                $q->whereIn('account_id', $allAccountIds)->whereIn('reference_type', ['advance', 'new_ref']),

            $filterBy === 'cr' && !$onAdvance =>
                $q->whereIn('account_id', $crAccountIds)->where('reference_type', 'new_ref'),

            $filterBy === 'cr' && $onAdvance =>
                $q->whereIn('account_id', $crAccountIds)->where('reference_type', 'advance'),

            $filterBy === 'dr' =>
                $q->whereIn('account_id', $drAccountIds)->where('reference_type', 'new_ref'),

            $filterBy === 'partial' =>
                $q->whereIn('account_id', $crAccountIds)
                  ->where('settled_amount', '>', 0)
                  ->where('pending_amount', '>', 0),

            default => null,
        };

        $q->when(isset($filter['file_number']),fn($q) => $q->where('file_number', $filter['file_number']));
        $q->when(!empty($filter['reference_ids']), fn($q) => $q->whereIn('id', $filter['reference_ids']));

        $q->where('is_closed', 0)->where('is_hold', 0)
          ->orderBy('file_number', 'asc')
          ->orderBy('reference_date', 'asc')
          ->orderBy('reference_number', 'asc');

        return $this->preparePayableData($q->get(), $filter);
    }

    private function partitionAccountsByBalance(int $companyId, int $financialYearId, array $accountIds): array
    {
        if (empty($accountIds)) {
            return [[], []];
        }

        $balances = $this->accountBalanceService->getClosingBalance($companyId, $financialYearId, $accountIds);

        $cr = [];
        $dr = [];
        foreach ($balances as $accountId => $bal) {
            if ($bal['closing'] < 0) {
                $cr[] = $accountId;
            } elseif ($bal['closing'] > 0) {
                $dr[] = $accountId;
            }
        }

        return [$cr, $dr];
    }

    private function preparePayableData($references, $filter)
    {
        $purchaseInvoiceIds = $references->where('source_type', Reference::PurchaseInvoice)->pluck('source_id')->toArray();
        $creditNoteIds = $references->where('source_type', Reference::CreditNote)->pluck('source_id')->toArray();
        $debitNoteIds = $references->where('source_type', Reference::DebitNote)->pluck('source_id')->toArray();
        $purchaseReturnIds = $references->where('source_type', Reference::PURCHASE_RETURN)->pluck('source_id')->toArray();


        if (count($purchaseInvoiceIds) > 0) {
            $purchaseInvoices = $this->preparePurchaseInvoiceData($purchaseInvoiceIds);
        }

        if (count($purchaseReturnIds) > 0) {
            $purchaseReturns = $this->preparePurchaseReturnData($purchaseReturnIds);
        }

        $data = [];
        foreach ($references as $reference) {
            $voucherData = null;

            if ($reference->source_type == Reference::PurchaseInvoice) {
                $voucherData = $purchaseInvoices[$reference->source_id] ?? null;
            } elseif ($reference->source_type == Reference::PURCHASE_RETURN) {
                $voucherData = $purchaseReturns[$reference->source_id] ?? null;
            }

            $data[] = [
                'id'                    => $reference->id,
                'account_name'          => $reference->account->name,
                'account_city'          => $reference->account->city ?? '',
                'reference_number'      => $reference->reference_number,
                'reference_date'        => $reference->reference_date,
                'file_number'           => $reference->file_number,
                'amount'                => $reference->amount,
                'pending_amount'        => $reference->pending_amount,
                'settled_amount'        => $reference->settled_amount,
                'reference_type'        => $reference->reference_type,
                'direction'             => $reference->direction,
                'account_id'            => $reference->account_id,
                'source_type'           => $reference->source_type,
                'source_id'             => $reference->source_id,
                'voucher_id'            => $reference->voucher_id,
                'items'                 => $voucherData['items'] ?? [],
                'show_date'             => $voucherData['show_date'] ?? $reference->reference_date,
                'cd'                    => $voucherData['cd'] ?? 0.00,
                'cd_percent'            => $voucherData['cd_percent'] ?? 0.00,
                'rebate'                => $voucherData['rebate'] ?? 0,
                'sgst'                  => $voucherData['sgst'] ?? 0,
                'cgst'                  => $voucherData['cgst'] ?? 0,
                'igst'                  => $voucherData['igst'] ?? 0,
                'freight'               => $voucherData['freight'] ?? 0,
                'labour'                => $voucherData['labour'] ?? 0,
                'penalty'               => $voucherData['penalty'] ?? 0,
                'tds'                   => $voucherData['tds'] ?? 0,
                'round_additive'        => $voucherData['round_additive'] ?? 0,
                'round_deductive'       => $voucherData['round_deductive'] ?? 0,
                'other'                 => $voucherData['other'] ?? 0,
                'rebate_from_analysis'  => $voucherData['rebate_from_analysis'] ?? 0,
                'is_partial_paid'       => ($reference->settled_amount > 0 && $reference->pending_amount > 0) ? true : false,
                'total_quantity'        => $voucherData['total_quantity'] ?? 0,
                'taxable_amount'        => $voucherData['taxable_amount'] ?? 0,
            ];
        }

        usort($data, function ($a, $b) {
            return strcmp($a['account_name'], $b['account_name']);
        });

        // advance entries at top
        usort($data, function ($a, $b) {
            $aIsAdvance = $a['reference_type'] === 'advance' ? 0 : 1;
            $bIsAdvance = $b['reference_type'] === 'advance' ? 0 : 1;
            return $aIsAdvance - $bIsAdvance;
        });

        return $data;
    }

    private function preparePurchaseInvoiceData($purchaseInvoiceIds)
    {
        $purchaseInvoices = PurchaseInvoice::with('details', 'billSundries')
            ->whereIn('id', $purchaseInvoiceIds)
            ->get()
            ->keyBy('id');

        // Map sundry code → field name
        $codeMap = [
            '1001' => 'cd',
            '1002' => 'sgst',
            '1003' => 'cgst',
            '1004' => 'igst',
            '1005' => 'freight',
            '1006' => 'labour',
            '1007' => 'penalty',
            '1008' => 'rebate',
            '1009' => 'tds',
            '1010' => 'round_additive',
            '1011' => 'round_deductive',
        ];

        $data = [];

        foreach ($purchaseInvoices as $invoice) {

            $data[$invoice->id] = [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'rebate_from_analysis' => $invoice->rebate_from_analysis ?? 0, // Init safe default
                'total_quantity' => $invoice->total_quantity,
                'taxable_amount' => $invoice->taxable_amount,
                'show_date' => $invoice->show_date,
                'items' => $invoice->details->map(function ($detail) {
                    return [
                        'item_name' => $detail->item->name ?? '',
                        'destination_name' => $detail->destination->name ?? '',
                        'quantity'  => $detail->quantity,
                        'rate'      => $detail->rate,
                        'amount'    => $detail->amount,
                    ];
                })->toArray(),
                'other' => 0, // Init safe default
            ];

            // Loop bill sundry only once
            foreach ($invoice->billSundries as $sundry) {
                if ($sundry->affect_net_total == 0) continue; // skip if net total not affect
                if ($sundry->code == '1001') {
                    $data[$invoice->id]['cd'] = abs($sundry->amount) ?? 0;
                    $data[$invoice->id]['cd_percent'] = $sundry->rate_percent;
                }

                if (isset($codeMap[$sundry->code]) && $sundry->code != '1001') {
                    $field = $codeMap[$sundry->code];
                    $data[$invoice->id][$field] = $sundry->amount;
                } else if ($sundry->code != '1001') {
                    $data[$invoice->id]['other'] += $sundry->amount;
                }
            }
        }
        return $data;
    }

    private function preparePurchaseReturnData($purchaseReturnIds)
    {
        $debitNotes = DebitNote::with('details.item', 'details.destination', 'billSundries')
            ->whereIn('id', $purchaseReturnIds)
            ->get()
            ->keyBy('id');

        // Map sundry code → field name
        $codeMap = [
            '1001' => 'cd',
            '1002' => 'sgst',
            '1003' => 'cgst',
            '1004' => 'igst',
            '1005' => 'freight',
            '1006' => 'labour',
            '1007' => 'penalty',
            '1008' => 'rebate',
            '1009' => 'tds',
            '1010' => 'round_additive',
            '1011' => 'round_deductive',
        ];

        $data = [];

        foreach ($debitNotes as $debitNote) {

            $data[$debitNote->id] = [
                'invoice_number' => $debitNote->debit_note_number,
                'invoice_date' => $debitNote->debit_note_date,
                'rebate_from_analysis' => 0, // Init safe default
                'total_quantity' => $debitNote->total_quantity,
                'taxable_amount' => $debitNote->taxable_amount,
                'show_date' => $debitNote->debit_note_date,
                'items' => $debitNote->details->map(function ($detail) {
                    return [
                        'item_name' => $detail->item->name ?? '',
                        'destination_name' => $detail->destination->name ?? '',
                        'quantity'  => $detail->quantity,
                        'rate'      => $detail->rate,
                        'amount'    => $detail->amount,
                    ];
                })->toArray(),
                'other' => 0, // Init safe default
            ];

            // Loop bill sundry only once
            foreach ($debitNote->billSundries as $sundry) {
                if ($sundry->affect_net_total == 0) continue; // skip if net total not affect
                if ($sundry->code == '1001') {
                    $data[$debitNote->id]['cd'] = abs($sundry->amount) ?? 0;
                    $data[$debitNote->id]['cd_percent'] = $sundry->rate_percent;
                }

                if (isset($codeMap[$sundry->code]) && $sundry->code != '1001') {
                    $field = $codeMap[$sundry->code];
                    $data[$debitNote->id][$field] = $sundry->amount;
                } else if ($sundry->code != '1001') {
                    $data[$debitNote->id]['other'] += $sundry->amount;
                }
            }
        }
        return $data;
    }

    public function settlementByPayable($companyId, $financialYearId, $payment)
    {
        // find purchase bill id from reference array
        $purchaseUpdates = [];

        foreach ($payment['references'] as $reference) {
            if ($reference['source_type'] == Reference::PurchaseInvoice) {
                $purchaseUpdates[$reference['source_id']] = [
                    'cd_percent' => $reference['cd_percent'],
                    'show_date' => $reference['show_date'],
                    'cd_amount' => $reference['cd_amount'],
                ];
            }
        }
        DB::beginTransaction();
        try {
            $token = $payment['unique_request_id'];
            $existingToken = UniqueToken::where('unique_request_id', $token)->first();

            if ($existingToken) {
                // Payment already processed
                DB::commit();
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => 'Payment has already been processed. Please refresh the page to make a new payment.',
                ];
            } else {
                UniqueToken::create([
                    'unique_request_id' => $token,
                ]);
            }

            // Update purchase bill
            if (count($purchaseUpdates) > 0) {
                [$purchaseData, $auditJobsData] = $this->updatePurchaseBill($purchaseUpdates);

                $this->updatePurchaseVoucherEntry($purchaseData);

                if (\isAuditLog()) {
                    foreach ($auditJobsData as $jobData) {
                        $bill = PurchaseInvoice::find($jobData['bill_id']);
                        $newBillState = app(\App\Services\PurchaseInvoiceService::class)->buildAuditValues($bill);

                        $oldValues = $jobData['old_values'];
                        $newValues = $newBillState;

                        // Check if changes exist
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
                            continue;
                        }

                        $orgAmount   = (float) collect($oldValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();
                        $finalAmount = (float) collect($newValues)->filter(fn($v, $k) => str_starts_with((string) $k, 'items.') && str_ends_with((string) $k, '.amount'))->sum();

                        $auditData = [
                            'company_id'        => $bill->company_id,
                            'financial_year_id' => $bill->financial_year_id,
                            'action'            => \App\Models\AuditTrail::ACTION_UPDATE,
                            'module'            => SourceType::PURCHASE,
                            'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
                            'model_name'        => PurchaseInvoice::class,
                            'source_id'         => $bill->id,
                            'voucher_id'        => $bill->voucher_id,
                            'reference_number'  => $bill->reference_number,
                            'org_amount'        => $orgAmount,
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
                        \App\Jobs\LogAuditJob::dispatch($auditData);
                    }
                }
            }

            // Create payment Voucher
            $this->createPaymentVoucher($payment, $companyId, $financialYearId);

            DB::commit();

            return [
                'status' => true,
                'message' => 'Settlement completed successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function createPaymentVoucher($payment, $companyId, $financialYearId)
    {
        $accountId = $payment['account_id'];
        $ledgerAccountId = $payment['ledger_id'] ?? null;
        $ledgerTransactionType = $payment['ledger_transaction_type'];
        $ledgerAmount = $payment['ledger_amount'];
        $paymentDate = !empty($payment['payment_date'])
            ? $payment['payment_date']
            : Carbon::now();
        $bankId = $payment['bank_id'];
        $reference = $payment['references'];

        // Fetch original references to know each one's true debit/credit direction
        // (e.g. advance references are 'debit' and must reduce the payable total,
        // while purchase invoice references are 'credit' and add to it).
        $referenceIds = collect($reference)->pluck('reference_id')->filter()->unique()->values()->toArray();
        $originalReferences = Reference::whereIn('id', $referenceIds)->get()->keyBy('id');

        $accountIdWiseTotal = [];
        $accountWiseRef = [];
        foreach ($reference as $ref) {
            if (!isset($accountIdWiseTotal[$ref['account_id']])) {
                $accountIdWiseTotal[$ref['account_id']] = 0;
            }

            $originalRef = $originalReferences->get($ref['reference_id']);
            $isDebit = $originalRef && $originalRef->direction === 'debit';

            $accountIdWiseTotal[$ref['account_id']] += $isDebit ? -$ref['payment_amount'] : $ref['payment_amount'];
            $accountWiseRef[$ref['account_id']][] = $ref;
        }

        $auditAccountIds = array_filter(array_unique(array_merge(
            array_keys($accountIdWiseTotal),
            [$bankId, $ledgerAccountId]
        )));
        $accounts = \App\Models\Account::whereIn('id', $auditAccountIds)->pluck('name', 'id');

        $totalBankAmount = 0;
        $allAllocations = [];
        $now = Carbon::now();

        foreach ($accountIdWiseTotal as $aId => $totalAmount) {

            $voucherNumber = $this->voucherService->getNextVoucherNumber(
                companyId: $companyId,
                financialYearId: $financialYearId,
                voucherTypeId: VoucherType::PAYMENT
            );


            $voucher = Voucher::create([
                'uuid'             => uuid(),
                'company_id'       => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_date'     => $paymentDate,
                'voucher_type_id'  => VoucherType::PAYMENT,
                'source_type'      => SourceType::PAYMENT,
                'voucher_serial'   => $voucherNumber->serial,
                'voucher_number'   => $voucherNumber->voucher_number,
                'narration'        => "",
                'created_by'       => current_user_id(),
            ]);

            // Account Entry witch amount to paid
            $debitAmount = $totalAmount;
            $creditAmount = $totalAmount;
            $voucherLines = [];

            // account entry debit 
            $voucherLines[] = [
                'account_id'    => $aId,
                'debit' => $debitAmount,
                'credit' => 0,
                'against_account_id' => $bankId,
                'is_party_account' => true,
                'line_no' => 1,
            ];

            // Additional Deduction Entry
            if ($accountId && $ledgerAccountId) {
                $creditAmount = $totalAmount - $ledgerAmount;
                $voucherLines[] = [
                    'account_id'    => $ledgerAccountId,
                    'debit' => 0,
                    'credit' => $ledgerAmount,
                    'against_account_id' => $bankId,
                    'is_party_account' => false,
                    'line_no' => 2,
                ];
            }

            // Bank Entry 
            $voucherLines[] = [
                'account_id'    => $bankId,
                'debit' => 0,
                'credit' => $creditAmount,
                'against_account_id' => $aId,
                'is_party_account' => false,
                'line_no' => ($accountId && $ledgerAccountId) ? 3 : 2,
            ];
            $voucher->details()->createMany($voucherLines);

            foreach ($accountWiseRef[$aId] as $ref) {
                $allAllocations[] = [
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'reference_number'  => $originalReferences->get($ref['reference_id'])?->reference_number,
                    'voucher_id'        => $voucher->id,
                    'reference_id'      => $ref['reference_id'],
                    'account_id'        => $ref['account_id'],
                    'amount'            => $ref['payment_amount'],
                    'allocation_type'   => ReferenceAllocation::AGAINST_REF,
                    'source_type'       => $ref['source_type'],
                    'source_id'         => $ref['source_id'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $reference = $originalReferences->get($ref['reference_id']);
                
                if ($reference) {
                    $reference->settled_amount += $ref['payment_amount'];
                    $reference->pending_amount = $reference->amount - $reference->settled_amount;
                    $reference->is_closed = $reference->pending_amount == 0;
                    $reference->closed_at = $reference->is_closed ? $now : null;
                    $reference->updated_by = current_user_id();
                    $reference->updated_at = $now;
                    $reference->save();
                }
            }
            $paymentVoucher = PaymentVoucher::create([
                'voucher_id' => $voucher->id,
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'account_id' => $aId, //main account Id
                'payment_date' => $paymentDate,
                'bank_id' => $bankId,
                'paid_amount' => $creditAmount,
                'is_paid' => $creditAmount == 0,
                'is_approved' => $creditAmount == 0,
                'file_number' => $payment['file_number'] ?? null,
                'entry_from'        => PaymentVoucher::ENTRY_FROM_PAYMENT_PAYABLE,
                'is_pass_to_rtgs'   => true,
            ]);
            //  Update Voucher Table 
            Voucher::where('id', $voucher->id)->update([
                'source_id' => $paymentVoucher->id,
            ]);

            if (\isAuditLog()) {
                $newValues = [
                    'voucher_no'   => $voucher->voucher_serial,
                    'voucher_date' => $paymentDate,
                    'narration'    => "",
                    // 'approved_by'  => $paymentVoucher->is_approved ? (current_user()?->name ?? 'System') : 'Pending',
                ];

                $details = [];
                foreach ($voucherLines as $row) {
                    $details[] = [
                        'account_id'    => $row['account_id'] ?? null,
                        'account_name'  => $accounts[$row['account_id'] ?? 0] ?? 'Unknown',
                        'dr_cr'         => $row['debit'] > 0 ? 'DR' : 'CR',
                        'debit_amount'  => number_format((float)($row['debit'] ?? 0), 2, '.', ''),
                        'credit_amount' => number_format((float)($row['credit'] ?? 0), 2, '.', ''),
                    ];
                }
                $newValues['details'] = $details;

                $finalAmount = collect($newValues['details'])->sum(function ($item) {
                    return (float)($item['debit_amount'] ?? 0);
                });

                $auditData = [
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'action'            => \App\Models\AuditTrail::ACTION_CREATE,
                    'module'            => SourceType::PAYMENT,
                    'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
                    'model_name'        => PaymentVoucher::class,
                    'source_id'         => $paymentVoucher->id,
                    'voucher_id'        => $voucher->id,
                    'reference_number'  => $voucher->reference_number,
                    'org_amount'        => 0,
                    'final_amount'      => $finalAmount,
                    'version'           => 0,
                    'http_method'       => request()->method(),
                    'user_id'           => current_user_id(),
                    'user_name'         => current_user()?->name ?? 'System',
                    'ip_address'        => (request()->header('CF-Connecting-IP') ?? request()->header('True-Client-IP') ?? request()->header('X-Real-IP') ?? trim(explode(',', request()->header('X-Forwarded-For', ''))[0]) ?: request()->ip()),
                    'user_agent'        => request()->userAgent(),
                    'request_url'       => request()->fullUrl(),
                    'old_values'        => [],
                    'new_values'        => $newValues,
                ];

                // \App\Jobs\LogAuditJob::dispatch($auditData);
                app(\App\Services\AuditService::class)->log($auditData);    
            }

            $totalBankAmount += $creditAmount;
        }

        
        if (!empty($allAllocations)) {
            ReferenceAllocation::insert($allAllocations);
        }

        $sessionKey = 'rtgs_payable_amt_' . $companyId;
        // session()->put($sessionKey, session()->get($sessionKey, 0) + $totalBankAmount);
        session()->put($sessionKey, $totalBankAmount);
    }

    function updatePurchaseBill(array $purchaseUpdates)
    {
        $purchaseBills = PurchaseInvoice::with('billSundries')
            ->whereIn('id', array_keys($purchaseUpdates))
            ->get();

        $billIds = $purchaseBills->pluck('id')->toArray();
        $references = Reference::where('source_type', Reference::PurchaseInvoice)
            ->whereIn('source_id', $billIds)
            ->get()
            ->keyBy('source_id');

        $voucherUpdateData = [];
        $auditJobsData = [];

        foreach ($purchaseBills as $bill) {
            $oldCdAmount = 0;
            $cdId = null;
            $cdSundry = null;

            // 1. Find existing CD (negative value) from already eager-loaded billSundries
            foreach ($bill->billSundries as $sundry) {
                if ($sundry->code == 1001) {
                    $oldCdAmount = (float)$sundry->value;  // value is always negative
                    $cdId = $sundry->account_id;
                    $cdSundry = $sundry;
                    break;
                }
            }

            // 2. Always convert new CD to negative
            $inputCd = $purchaseUpdates[$bill->id]['cd_amount'];
            $newCdAmount = $inputCd;         // value always negative
            $newCdAbs    = abs($newCdAmount);      // amount always positive

            // 3. net total difference
            // old=-50  new=-80 => diff = -80 - (-50) = -30
            $diff = $newCdAmount - $oldCdAmount;

            $oldBillState = [];
            if (\isAuditLog()) {
                $oldBillState = app(\App\Services\PurchaseInvoiceService::class)->buildAuditValues($bill);
            }

            $bill->update([
                'net_amount' => $bill->net_amount - $diff, // diff is negative
                'grand_total' => $bill->grand_total - $diff,
                'show_date' => $purchaseUpdates[$bill->id]['show_date'],
                'updated_by' => current_user_id(),
            ]);

            $ratePercent = $purchaseUpdates[$bill->id]['cd_percent'];
            
            if ($cdSundry) {
                $cdSundry->update([
                    'value' => $newCdAbs,     // always Positive
                    'amount' => -$newCdAmount,   // always Negative
                    'rate_percent' => $ratePercent,
                    'updated_by' => current_user_id(),
                ]);
            } else {
                $this->updateCdSundry(
                    $bill->id,
                    $newCdAbs,
                    $newCdAmount,
                    $ratePercent,
                );
            }

            $voucherUpdateData[$bill->voucher_id] = [
                'bill_id' => $bill->id,
                'account_id' => $bill->account_id,
                'net_amount' => $bill->net_amount,
                'voucher_id' => $bill->voucher_id,
                'cd_amount' => $newCdAmount,
                'cd_id'     => $cdId,
                'diff' => $diff,
            ];

            // Reference table update (using pre-fetched references)
            $reference = $references->get($bill->id);

            if ($reference) {
                $reference->amount = $bill->net_amount; // net_amount changes
                $reference->pending_amount = $bill->net_amount - $reference->settled_amount;
                $reference->is_closed = $reference->pending_amount == 0;
                $reference->closed_at = $reference->pending_amount == 0 ? Carbon::now() : null;
                $reference->updated_by = current_user_id();
                $reference->save();
            }

            if (\isAuditLog()) {
                $auditJobsData[] = [
                    'bill_id' => $bill->id,
                    'old_values' => $oldBillState,
                ];
            }
        }

        return [$voucherUpdateData, $auditJobsData];
    }

    function updateCdSundry($billId, $cdValue, $cdAmount, $ratePercent)
    {

        $sundry = PurchaseInvoiceSundry::where('purchase_invoice_id', $billId)
            ->where('code', 1001)
            ->first();

        if ($sundry) {
            $sundry->update([
                'value' => $cdValue,     // always Positive
                'amount' => -$cdAmount,   // always Negative
                'rate_percent' => $ratePercent,
                'updated_by' => current_user_id(),
            ]);
        }
    }

    function updatePurchaseVoucherEntry(array $voucherUpdateData)
    {
        $voucherIds = array_column($voucherUpdateData, 'voucher_id');
        $allVoucherEntries = VoucherTransaction::whereIn('voucher_id', $voucherIds)->get()->groupBy('voucher_id');

        foreach ($voucherUpdateData as $voucherId => $purchase) {

            $voucherEntries = $allVoucherEntries->get($purchase['voucher_id'], collect());

            $cdUpdated = false;
            $mainAccountUpdated = false;

            foreach ($voucherEntries as $entry) {

                // Main party/vendor account update
                if ($entry->account_id == $purchase['account_id'] && $entry->credit != 0) {
                    $entry->update([
                        'credit' => $entry->credit - $purchase['diff']
                    ]);
                    $mainAccountUpdated = true;
                }

                // Cash Discount account update
                if ($entry->account_id == $purchase['cd_id']) {
                    $entry->update([
                        'credit' => abs($purchase['cd_amount'])
                    ]);
                    $cdUpdated = true;
                }
            }

            // If party account not found → create (rare case)
            if (!$mainAccountUpdated) {
                VoucherTransaction::create([
                    'voucher_id' => $purchase['voucher_id'],
                    'account_id' => $purchase['account_id'],
                    'debit'      => 0,
                    'credit'     => $purchase['diff'],
                ]);
            }

            // If CD account does not exist → create CD entry
            if (!$cdUpdated) {
                VoucherTransaction::create([
                    'voucher_id' => $purchase['voucher_id'],
                    'account_id' => $purchase['cd_id'],
                    'debit'      => 0,
                    'credit'     => abs($purchase['cd_amount']),
                ]);
            }
        }
    }

    // 🔹 Prepare Data For Receivables
    public function getReceivables($filter = [])
    {
        $companyId = $filter['company_id'];
        $accountId = $filter['account_id'];

        $q = Reference::with('account:id,name,city')->where('company_id', $companyId)->where('account_id', $accountId)->where('is_closed', 0)->where('is_hold', 0);


        $q->orderBy('reference_date', 'asc')->orderBy('reference_number', 'asc');

        $references = $q->get();


        return $this->prepareReceivableData($references, $filter);
    }

    private function prepareReceivableData($references, $filter)
    {
        $salesInvoiceIds = $references->where('source_type', Reference::SalesInvoice)->pluck('source_id')->toArray();

        $salesInvoices = [];
        if (count($salesInvoiceIds) > 0) {
            $salesInvoices = $this->prepareSalesInvoiceReceivables($salesInvoiceIds);
        }

        $data = [];
        foreach ($references as $reference) {
            $voucherData = null;

            if ($reference->source_type == Reference::SalesInvoice) {
                $voucherData = $salesInvoices[$reference->source_id] ?? null;
            }

            $data[] = [
                'id'               => $reference->id,
                'account_name'     => $reference->account->name,
                'account_city'     => $reference->account->city ?? '',
                'reference_number' => $reference->reference_number,
                'reference_date'   => $reference->reference_date,
                'file_number'      => $reference->file_number,
                'amount'           => $reference->amount,
                'pending_amount'   => $reference->pending_amount,
                'settled_amount'   => $reference->settled_amount,
                'reference_type'   => $reference->reference_type,
                'direction'        => $reference->direction,
                'account_id'       => $reference->account_id,
                'source_type'      => $reference->source_type,
                'source_id'        => $reference->source_id,
                'voucher_id'       => $reference->voucher_id,
                'grn_number'       => $voucherData['grn_number'] ?? '',
                'items'            => $voucherData['items'] ?? [],
                'total_quantity'   => $voucherData['total_quantity'] ?? 0,
                'taxable_amount'   => $voucherData['taxable_amount'] ?? 0,
                'is_partial_paid'  => ($reference->settled_amount > 0 && $reference->pending_amount > 0),

            ];
        }
        return $data;
    }

    private function prepareSalesInvoiceReceivables($salesInvoiceIds)
    {
        $salesInvoices = SalesInvoice::with(['details.item', 'details.condition', 'details.destination'])
            ->whereIn('id', $salesInvoiceIds)
            ->get()
            ->keyBy('id');

        $data = [];

        foreach ($salesInvoices as $invoice) {
            $data[$invoice->id] = [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date'   => $invoice->invoice_date,
                'total_quantity' => $invoice->total_quantity,
                'taxable_amount' => $invoice->taxable_amount,
                'grn_number'     => $invoice->grn_number,
                'items'          => $invoice->details->map(function ($detail) {
                    return [
                        'item_name'        => $detail->item->name ?? '',          // Product
                        'quantity'         => $detail->quantity,                  // Qty
                        'rate'             => $detail->rate,                      // Rate
                        'amount'           => $detail->amount,
                        'particular_name'  => $detail->condition->name ?? '',
                        'destination_name' => $detail->destination->name ?? '',
                    ];
                })->toArray(),
            ];
        }
        return $data;
    }

    public function settlementByReceivable($companyId, $financialYearId, $receivable)
    {
        DB::beginTransaction();
        try {
            $token = $receivable['unique_request_id'];
            $existingToken = UniqueToken::where('unique_request_id', $token)->first();

            if ($existingToken) {
                // Payment already processed
                DB::commit();
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => 'Receipt has already been processed. Please refresh the page to make a new receipt.',
                ];
            } else {
                UniqueToken::create([
                    'unique_request_id' => $token,
                ]);
            }

            // Create Receipt Voucher
            $this->createReceiptVoucher($receivable, $companyId, $financialYearId);

            DB::commit();

            return [
                'status' => true,
                'message' => 'Settlement completed successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Settlement failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    function createReceiptVoucher($receivable, $companyId, $financialYearId)
    {
        $accountId = $receivable['account_id'];
        $receiptDate = $receivable['voucher_date'];
        $bankId = $receivable['bank_id'];
        $bankAmount = floatval($receivable['bank_amount'] ?? 0);

        $references = $receivable['references'] ?? [];

        // Fetch original references to know each one's true debit/credit direction
        // (e.g. credit note / sales return references are 'credit' and must reduce
        // the receivable total, while sales invoice references are 'debit' and add to it).
        $referenceIds = collect($references)->pluck('reference_id')->filter()->unique()->values()->toArray();
        $originalReferences = Reference::whereIn('id', $referenceIds)->get()->keyBy('id');

        // Sum the total allocated to bills (which will be the Credit to the Customer)
        $totalAmount = 0;
        foreach ($references as $ref) {
            $originalRef = $originalReferences->get($ref['reference_id']);
            $isCredit = $originalRef && $originalRef->direction === 'credit';
            $totalAmount += $isCredit ? -floatval($ref['payment_amount']) : floatval($ref['payment_amount']);
        }

        // Create exactly ONE voucher
        $voucherNumber = $this->voucherService->getNextVoucherNumber(
            companyId: $companyId,
            financialYearId: $financialYearId,
            voucherTypeId: VoucherType::RECEIPT
        );

        $voucher = Voucher::create([
            'uuid'             => uuid(),
            'company_id'       => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_date'     => $receiptDate,
            'voucher_type_id'  => VoucherType::RECEIPT,
            'voucher_serial'   => $voucherNumber->serial,
            'voucher_number'   => $voucherNumber->voucher_number,
            'narration'        => $receivable['remarks'] ?? "",
            'created_by'       => current_user_id(),
        ]);

        $voucherLines = [];
        $lineNo = 1;

        // 1. Credit Customer Account (Base reference total)
        $voucherLines[] = [
            'account_id'    => $accountId,
            'debit' => 0,
            'credit' => $totalAmount,
            'against_account_id' => $bankId,
            'is_party_account' => true,
            'line_no' => $lineNo++,
        ];

        // 2. Debit Bank
        if ($bankId && $bankAmount > 0) {
            $voucherLines[] = [
                'account_id'    => $bankId,
                'debit' => $bankAmount,
                'credit' => 0,
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        // 3. Debit TDS
        if (!empty($receivable['tds_id']) && floatval($receivable['tds_amount']) > 0) {
            $voucherLines[] = [
                'account_id'    => $receivable['tds_id'],
                'debit' => floatval($receivable['tds_amount']),
                'credit' => 0,
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        // 4. Debit Rebate
        if (!empty($receivable['rebate_id']) && floatval($receivable['rebate_amount']) > 0) {
            $voucherLines[] = [
                'account_id'    => $receivable['rebate_id'],
                'debit' => floatval($receivable['rebate_amount']),
                'credit' => 0,
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        // 5. Debit Penalty
        if (!empty($receivable['penalty_id']) && floatval($receivable['penalty_amount']) > 0) {
            $voucherLines[] = [
                'account_id'    => $receivable['penalty_id'],
                'debit' => floatval($receivable['penalty_amount']),
                'credit' => 0,
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        // 6. Credit Premium
        if (!empty($receivable['premium_id']) && floatval($receivable['premium_amount']) > 0) {
            $voucherLines[] = [
                'account_id'    => $receivable['premium_id'],
                'debit' => 0,
                'credit' => floatval($receivable['premium_amount']),
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        // 7. Other Amount (Dr or Cr based on type)
        if (!empty($receivable['other_id']) && floatval($receivable['other_amount']) > 0) {
            $amt = floatval($receivable['other_amount']);
            $isCr = ($receivable['other_type'] === 'cr');
            $voucherLines[] = [
                'account_id'    => $receivable['other_id'],
                'debit' => $isCr ? 0 : $amt,
                'credit' => $isCr ? $amt : 0,
                'against_account_id' => $accountId,
                'is_party_account' => false,
                'line_no' => $lineNo++,
            ];
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($voucherLines as $line) {
            $totalDebit += $line['debit'];
            $totalCredit += $line['credit'];
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception('Debit and Credit total do not match. Debit: ' . $totalDebit . ', Credit: ' . $totalCredit);
        }

        $voucher->details()->createMany($voucherLines);

        $receipt = ReceiptVoucher::create([
            'voucher_id'        => $voucher->id,
            'company_id'        => $companyId,
            'financial_year_id' => $financialYearId,
            'entry_from'        => ReceiptVoucher::ENTRY_FROM_RECEIPT_RECEIVABLE,
            'received_amount'   => $totalCredit,
            'mode'              => 'rtgs',
            'is_received'       => true,
            'is_approved'       => true,
        ]);

        // Reference Allocation
        foreach ($references as $ref) {
            ReferenceAllocation::create([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_id' => $voucher->id,
                'reference_id' => $ref['reference_id'],
                'account_id' => $accountId,
                'amount' => $ref['payment_amount'],
                'allocation_type' => ReferenceAllocation::AGAINST_REF,
                'source_type' => $ref['source_type'],
                'source_id' => $ref['source_id'],
            ]);

            $referenceRecord = Reference::where('id', $ref['reference_id'])->first();
            if ($referenceRecord) {
                $referenceRecord->settled_amount += $ref['payment_amount'];
                $referenceRecord->pending_amount -= $ref['payment_amount'];
                $isClosed = round($referenceRecord->amount, 2) == round($referenceRecord->settled_amount, 2);
                $referenceRecord->is_closed = $isClosed;
                $referenceRecord->closed_at = $isClosed ? Carbon::now() : null;
                $referenceRecord->save();
            }
        }

        if (\isAuditLog()) {
            $newValues = [
                'voucher_no'   => $voucher->id,
                'voucher_date' => $receiptDate,
                'narration'    => $receivable['remarks'] ?? "",
            ];

            $accountIds = collect($voucherLines)->pluck('account_id')->filter()->unique()->toArray();
            $accounts   = \App\Models\Account::whereIn('id', $accountIds)->pluck('name', 'id');

            $details = [];
            foreach ($voucherLines as $row) {
                $details[] = [
                    'account_id'    => $row['account_id'] ?? null,
                    'account_name'  => $accounts[$row['account_id'] ?? 0] ?? 'Unknown',
                    'dr_cr'         => $row['debit'] > 0 ? 'DR' : 'CR',
                    'debit_amount'  => number_format((float)($row['debit'] ?? 0), 2, '.', ''),
                    'credit_amount' => number_format((float)($row['credit'] ?? 0), 2, '.', ''),
                ];
            }
            $newValues['details'] = $details;

            $finalAmount = collect($newValues['details'])->sum(function ($item) {
                return (float)($item['debit_amount'] ?? 0);
            });

            $auditData = [
                'company_id'        => $voucher->company_id,
                'financial_year_id' => $voucher->financial_year_id,
                'action'            => \App\Models\AuditTrail::ACTION_CREATE,
                'module'            => SourceType::RECEIPT,
                'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
                'model_name'        => ReceiptVoucher::class,
                'source_id'         => $receipt->id,
                'voucher_id'        => $voucher->id,
                'reference_number'  => $voucher->reference_number,
                'org_amount'        => 0,
                'final_amount'      => $finalAmount,
                'version'           => 0,
                'http_method'       => request()->method(),
                'old_values'        => [],
                'new_values'        => $newValues,
            ];

            app(\App\Services\AuditService::class)->log($auditData);
        }
    }

    public function getPendingRef($filter)
    {
        $companyId = $filter['company_id'];
        $accountId = $filter['account_id'];

        $data = Reference::where('company_id', $companyId)->where('is_closed', 0)->where('is_hold', 0)
            ->where('account_id', $accountId)->get();

        if (!$data) {
            return [];
        }
        $ref = [];
        foreach ($data as $key => $item) {
            $ref[] = [
                'reference_id' => $item->id,
                'reference_number' => $item->reference_number,
                'reference_date' => $item->reference_date,
                'voucher_id' => $item->voucher_id,
                'source_type' => $item->source_type,
                'source_id' => $item->source_id,
                'pending_amount' => $item->pending_amount,
                'amount' => $item->amount,
                'reference_type' => $item->reference_type,
                'direction' => $item->direction,
                'file_number' => $item->file_number
            ];
        }
        return $ref;
    }
}

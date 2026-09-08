<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ChequeMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\Godown;
use App\Models\GodownMapping;
use App\Models\GodownModule;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\ItemMapping;
use App\Models\OldRefMapping;
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class PaymentOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:payment {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payment Data Insert';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->setOldData();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function setOldData()
    {
        $companyId =  $this->argument('company_id');
        $financialYearId = $this->argument('financial_year_id');

        $ccId = [
            1 => '39',
            2 => '40',
            3 => '41',
            4 => '42',
            5 => '43',
            6 => '44',
            7 => '45',
            8 => '46',
            9 => '47',
        ];

        $type = [3 => 'account', 1 => 'supplier', 2 => 'customer'];

        // Pre-fetch all mappings before the loop to avoid N+1 queries
        // $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        // $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        // $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->get();
        $chequeMapping    = ChequeMapping::where('company_id', $companyId)->pluck('new_payment_id', 'old_payment_id');
        // $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        // $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        // $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');

        // current company's creditor and debtor accounts and its child groups
        $accounts = Account::where('company_id', $companyId)->orderBy('id')->pluck('account_group_id', 'id');


        $group = AccountGroup::where('company_id', $companyId)->pluck('is_party_group', 'id');


        $isPartyGroupIds = [];

        foreach ($accounts as $accountId => $groupId) {
            $isPartyGroupIds[$accountId] = $group[$groupId] ?? 0;
        }





        $ledgerData = DB::connection('old_db')
            ->table('ledger_details')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('voucher_type', VoucherType::PAYMENT)
            ->orderBy('drcr_status', 'desc')
            ->get()
            ->groupBy('voucher_id');

        $payments = DB::connection('old_db')
            ->table('payments')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->get()->keyBy('id');

        $paymentVoucherDetail = DB::connection('old_db')
            ->table('payment_voucher_detail')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->get()
            ->groupBy('payment_voucher_id')->toArray();

        $voucherWisePaymentDetails = [];
        foreach ($paymentVoucherDetail as $paymentVoucherId => $details) {
            foreach ($details as $detail) {
                $voucherWisePaymentDetails[$detail->payment_voucher_id] = $detail->payment_id;
            }
        }

        $bankGroupIds = AccountGroup::where('company_id', $companyId)
            ->where('code', 130)
            ->pluck('id')
            ->toArray();

        $bankIds = Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $bankGroupIds)
            ->pluck('id')
            ->toArray();

        $refs = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')->where('voucher_type', 2)->get()->groupBy('voucher_number');

        $purchaseOrders = PurchaseOrder::where('company_id', $companyId)->pluck('id', 'order_serial');
        $purchaseInvoice = PurchaseInvoice::where('company_id', $companyId)->pluck('id', 'invoice_serial');

        $paymentVoucher = Voucher::where('company_id', $companyId)->where('voucher_type_id', VoucherType::PAYMENT)->pluck('id', 'voucher_serial');
        $journalVoucher = Voucher::where('company_id', $companyId)->where('voucher_type_id', VoucherType::JOURNAL)->pluck('id', 'voucher_serial');

        $oldRefMapper =  OldRefMapping::where('company_id', $companyId)->pluck('new_ref_id', 'old_ref_id');

        $oldJournalVoucher = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')->where('voucher_type', VoucherType::JOURNAL)->pluck('voucher_number', 'id');

        $oldPaymentVoucher = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')->where('voucher_type', VoucherType::PAYMENT)->pluck('voucher_number', 'id');

        $oldPurchaseBills = DB::connection('old_db')
            ->table('purchase_bills')->where('cc_id', $ccId[$companyId])->pluck('vchr_no', 'id');

        


        DB::connection('old_db')
            ->table('payment_vouchers')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($payment_vouchers) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $ledgerData,
                $isPartyGroupIds,
                $payments,
                $voucherWisePaymentDetails,
                $paymentVoucherDetail,
                $bankGroupIds,
                $bankIds,
                $refs,
                $purchaseOrders,
                $purchaseInvoice,
                $paymentVoucher,
                $journalVoucher,
                $oldRefMapper,
                $oldJournalVoucher,
                $oldPaymentVoucher,
                $oldPurchaseBills,
                $chequeMapping


            ) {
                foreach ($payment_vouchers as $pv) {
                    $paymentRecord = $payments[$voucherWisePaymentDetails[$pv->voucher_no]] ?? null;
                    $firstPaymentDetail = $paymentVoucherDetail[$pv->voucher_no][0] ?? null;
                    // if($paymentRecord == null){
                    //     dd($pv->voucher_no, $firstPaymentDetail);
                    // }

                    $bankId = null;
                    foreach ($accountMaster as $key => $am) {
                        if(!$paymentRecord) continue;
                        if ($am->old_account_id == $paymentRecord->bank_id && $am->account_type == $type[3]) {
                            $bankId = $am->new_account_id;
                            break;
                        }
                    }
                    
                    // if()
                    // $payment = Payment::create([
                    //     'company_id' => $companyId,
                    //     'financial_year_id' => $financialYearId,
                    //     'bank_id' => $bankId,
                    //     'payment_date' => $firstPaymentDetail->payment_date,
                    //     'mode' => 'rtgs',
                    //     'amount' => $paymentRecord->amount,
                    //     'cheque_number' => $paymentRecord->cheque_no,
                    //     'cheque_date' => $firstPaymentDetail->payment_date,
                    //     'cheque_time' => $paymentRecord->cheque_time,
                    //     'utr_number' => null,
                    //     'cheque_name' => null,
                    //     'ac_pay' => 'N',
                    //     'rtgs' => 'N',
                    // ]);



                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::PAYMENT,
                        'voucher_serial'    => $pv->voucher_no,
                        'voucher_number'    => $pv->voucher_no,
                        'voucher_date'      => $pv->vch_date,
                        'reference_number'  => null,
                        'source_type'       => SourceType::PAYMENT,
                        'narration'         => $pv->secondary_narration,
                        'secondary_narration' => $pv->narration,
                    ]);

                    $voucherLine = [];
                    foreach ($ledgerData[$pv->voucher_no]  as $line => $ldData) {
                        $ldAccountId = null;
                        $oppositeAccount = null;
                        foreach ($accountMaster as $key => $am) {
                            if ($am->old_account_id == $ldData->account_id && $am->account_type == $type[$ldData->account_type]) {
                                $ldAccountId = $am->new_account_id;
                                break;
                            }
                        }
                        foreach ($accountMaster as $key => $am) {
                            if ($am->old_account_id == $ldData->reference_account_id && $am->account_type == $type[$ldData->reference_account_type]) {
                                $oppositeAccount = $am->new_account_id;
                                break;
                            }
                        }
                        $debit = 0;
                        $credit = 0;

                        if ($ldData->drcr_status == 1) {
                            $credit = $ldData->amount;
                        }
                        if ($ldData->drcr_status == 2) {
                            $debit = $ldData->amount;
                        }

                        $voucherLine[] = [
                            'voucher_id' => $voucher->id,
                            'account_id' => $ldAccountId,
                            'debit'  => $debit,
                            'credit' => $credit,
                            'narration' => null,
                            'is_party_account' => $isPartyGroupIds[$ldAccountId] ?? false,
                            'line_no' => $line + 1,
                            'against_account_id' => $oppositeAccount
                        ];
                    }
                    VoucherTransaction::insert($voucherLine);



                    $accountId = null;
                    $paidAmount = 0;

                    foreach ($voucherLine as $line) {
                        if ($line['debit'] > 0) {
                            $accountId = $line['account_id'];
                        }
                        if ($line['credit'] > 0 && in_array($line['account_id'], $bankIds)) {
                            $paidAmount = $line['credit'];
                            if(!$bankId){
                                 $bankId = $line['account_id'];
                            }
                        }
                    }
                    // dd($firstPaymentDetail);

                    $paymentVoucher = PaymentVoucher::create([
                        'voucher_id' => $voucher->id,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'paid_amount' => $paidAmount,
                        'bank_id' => $bankId,
                        'is_paid' => $firstPaymentDetail->is_payment == 2 ? true : false,
                        'is_approved' => true,
                        'approved_by'  => current_user_id(),
                        'approved_at' => now(),
                        'is_hold' => false,
                        'is_pass_to_rtgs' => true,
                        'account_id' => $accountId,
                        'file_number' => $firstPaymentDetail->file_no,
                        'entry_from' => $firstPaymentDetail->payment_type == 1 ? PaymentVoucher::ENTRY_FROM_PAYMENT_VOUCHER : PaymentVoucher::ENTRY_FROM_PAYMENT_PAYABLE,
                        'payment_id' => isset($firstPaymentDetail->payment_id) && $firstPaymentDetail->payment_id ? $chequeMapping[$firstPaymentDetail->payment_id] : null,
                    ]);

                    $voucher->update([
                        'source_id' => $paymentVoucher->id
                    ]);

                    // New Ref 

                    $references = $refs[$pv->voucher_no] ?? null;
                    

                    if ($references == null) {
                        continue;
                    }
                    // if($pv->voucher_no == 978){
                    //  dd($references);
                    // }

                    foreach ($references as $oldRef) {
                        if ($oldRef->voucher_id == null && !$oldRef->is_old_bill) {
                            //  new ref create
                            $purchaseOrderId = null;
                            if($oldRef->purchase_order){
                                $purchaseOrderId = $purchaseOrders[$oldRef->purchase_order];
                            }

                             $method = [
                                    '1' => Reference::NewReference,
                                    '2' => Reference::Advance,
                                ];

                            $sourceType = [
                                1 =>  SourceType::JOURNAL,
                                2 =>  SourceType::PAYMENT,
                                3 =>  SourceType::RECEIPT,
                                4 =>  SourceType::PURCHASE,
                                5 =>  SourceType::SALES,
                                9 =>  SourceType::PURCHASE_RETURN,
                                10 => SourceType::SALES_RETURN,
                                29 => SourceType::DEBIT_NOTE,
                                30 => SourceType::CREDIT_NOTE,
                            ];

                            Reference::create([
                                'company_id' => $companyId,
                                'financial_year_id' => $financialYearId,
                                'account_id' => $accountId, // Map this from $oldRef if needed
                                'reference_number' => $oldRef->ref_no,
                                'reference_date' => $oldRef->ref_date,
                                'file_number' => $oldRef->file_no,
                                'reference_type' => $method[$oldRef->method], // Map this from $oldRef if needed
                                'amount' => $oldRef->pay_amount,
                                'purchase_order_id' => $purchaseOrderId,
                                'settled_amount' => 0,
                                'pending_amount' => $oldRef->pay_amount,
                                'is_hold' => $oldRef->is_hold,
                                'is_closed' => false,
                                'closed_at' => null,
                                'source_type'   => $sourceType[$oldRef->voucher_type],
                                'voucher_id' => $voucher->id, // Link to the newly created voucher
                                'source_id' => $paymentVoucher->id, // Set this if you have a source ID in the old reference data
                                'direction' => $oldRef->payment_mode == 2 ? 'debit' : 'credit', // Map this from $oldRef if needed
                            ]);
                        } else {
                            $isOldYearRef = false;
                            if ($oldRef->is_old_bill) {
                                $isOldYearRef = true;
                            }

                            if ($isOldYearRef) {
                                // continue;
                                // dd($isOldYearRef);
                                $vId = $oldRef->voucher_id;

                                $id = $oldRefMapper[$vId];

                                $reference = Reference::find($id);

                                $allocationType = [
                                    '1' => ReferenceAllocation::AGAINST_REF,
                                    '2' => ReferenceAllocation::ADVANCE_ADJUSTMENT,
                                ];


                                ReferenceAllocation::create([
                                    'company_id' => $companyId,
                                    'financial_year_id' => $financialYearId,
                                    'voucher_id' => $voucher->id,
                                    'reference_id' => $reference->id,
                                    'amount' => $oldRef->pay_amount,
                                    'allocation_type' => $allocationType[$oldRef->method],
                                    'reference_number' => $oldRef->ref_no,
                                    'account_id' => $reference->account_id,
                                    'source_type' => $reference->source_type,
                                    'source_id' => $reference->source_id,

                                ]);

                                $reference->settled_amount +=  $oldRef->pay_amount;
                                $reference->pending_amount  = $reference->amount - $reference->settled_amount;
                                $reference->is_closed = $reference->pending_amount == 0;
                                $reference->closed_at = $reference->is_closed ? Carbon::now() : null;
                                $reference->save();
                            } else {
                                $voucherId = null;
                                if ($oldRef->bill_type == 4) {
                                    
                                    $getOldPurchaseVoucher = $oldPurchaseBills[$oldRef->voucher_id];

                                    
                                    $purchaseInvoiceId = $purchaseInvoice[$getOldPurchaseVoucher];
                                    
                                    $voucherId = Voucher::where('company_id', $companyId)
                                        ->where('voucher_type_id', 4)
                                        ->where('source_id', $purchaseInvoiceId)
                                        ->value('id');
                                    // dd($voucherId, $purchaseInvoiceId, $oldRef->voucher_number);
                                }
                                if ($oldRef->voucher_type == 2 && $oldRef->ref_no =='ONAC') {
                                    $getOldPV = $oldPaymentVoucher[$oldRef->voucher_id];
                                    $voucherId = Voucher::where('company_id', $companyId)->where('voucher_type_id', 2)->where('voucher_serial', $getOldPV)->value('id');
                                }
                                if($oldRef->bill_type == 1){
                                    $getOldJV = $oldJournalVoucher[$oldRef->voucher_id];
                                    $voucherId = Voucher::where('company_id', $companyId)->where('voucher_type_id', 1)->where('voucher_serial', $getOldJV)->value('id');
                                }



                                $reference = Reference::where('voucher_id', $voucherId)->where('company_id', $companyId)->first();

                                // if($reference->id == 172){
                                //    dd($oldRef);
                                // }


                                // if (!$reference) {
                                //     // continue;
                                //     dd([
                                //         'id' => $voucherId,
                                //         'oldRef' => $oldRef,
                                //     ]);
                                // }

                                $allocationType = [
                                    '1' => ReferenceAllocation::AGAINST_REF,
                                    '2' => ReferenceAllocation::ADVANCE_ADJUSTMENT,
                                ];


                                ReferenceAllocation::create([
                                    'company_id' => $companyId,
                                    'financial_year_id' => $financialYearId,
                                    'voucher_id' => $voucher->id,
                                    'reference_id' => $reference->id,
                                    'amount' => $oldRef->pay_amount,
                                    'allocation_type' => $allocationType[$oldRef->method],
                                    'reference_number' => $oldRef->ref_no,
                                    'account_id' => $reference->account_id,
                                    'source_type' => $reference->source_type,
                                    'source_id' => $reference->source_id,

                                ]);

                                $reference->settled_amount +=  $oldRef->pay_amount;
                                $reference->pending_amount  = $reference->amount - $reference->settled_amount;
                                $reference->is_closed = $reference->pending_amount == 0;
                                $reference->closed_at = $reference->is_closed ? Carbon::now() : null;
                                $reference->save();
                            }
                        }
                    }
                }
            });
    }
}

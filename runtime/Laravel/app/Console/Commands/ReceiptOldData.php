<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\Godown;
use App\Models\GodownMapping;
use App\Models\GodownModule;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\ItemMapping;
use App\Models\OldRefMapping;
use App\Models\PurchaseOrder;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\SalesInvoice;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class ReceiptOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:receipt {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receipt Data Insert';

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
            ->where('voucher_type', VoucherType::RECEIPT)
            ->orderBy('drcr_status', 'asc')
            ->get()
            ->groupBy('voucher_id');


        $refs =  DB::connection('old_db')
            ->table('receivable_payment_sale_details')
            ->where('cc_id', $ccId[$companyId])->whereNull('deleted_at')->get()->groupBy('receipt_voucher_no');

        $crNtref = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])->whereNull('deleted_at')->get()->groupBy('voucher_number');

        $newSalesBills = SalesInvoice::where('company_id', $companyId)->orderBy('id')->pluck('voucher_id', 'invoice_serial');

        $oldRefMapper =  OldRefMapping::where('company_id', $companyId)->pluck('new_ref_id', 'old_ref_id');

        DB::connection('old_db')
            ->table('receipt_vouchers')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('id', '!=', 3922)
            ->orderBy('id')
            ->chunk(200, function ($receipt_vouchers) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $ledgerData,
                $isPartyGroupIds,
                $refs,
                $newSalesBills,
                $oldRefMapper,
                $crNtref
            ) {
                foreach ($receipt_vouchers as $rv) {
                    
                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::RECEIPT,
                        'voucher_serial'    => $rv->receipt_voucher_no,
                        'voucher_number'    => $rv->receipt_voucher_no,
                        'voucher_date'      => $rv->receipt_voucher_date,
                        'reference_number'  => null,
                        'source_type'       => SourceType::RECEIPT,
                        'narration'         => $rv->narration,
                    ]);

                    $voucherLine = [];
                    // $lastOppositeAccount = null;
                    $totalCredit = 0;
                    foreach ($ledgerData[$rv->receipt_voucher_no]  as $line => $ldData) {
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
                                // $lastOppositeAccount =$am->new_account_id;
                                break;
                            }
                        }
                        if(!$ldAccountId){
                            dd($ldData, $rv);
                        }
                        $debit = 0;
                        $credit = 0;

                        if ($ldData->drcr_status == 1) {
                            $credit = $ldData->amount;
                            $totalCredit += $ldData->amount;
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

                    // dd($rv);

                    $references = $refs[$rv->receipt_voucher_no] ?? null;
                    
                    if ($references == null) {
                        continue;
                        }
                        // dd($references);

                    // foreach ($voucherLine as $key => $vln) {
                    //     if($vln['credit'] > 0){
                            ReceiptVoucher::create([
                                'voucher_id' => $voucher->id,
                                'company_id'        => $companyId,
                                'financial_year_id' => $financialYearId,
                                'received_amount' => $totalCredit,
                                // 'bank_id'         => $lastOppositeAccount, 
                                'entry_from'        => ReceiptVoucher::ENTRY_FROM_RECEIPT_RECEIVABLE, 
                                'is_received'    => true,
                                'is_approved' => true,
                                'is_hold'   => false,
                                // 'account_id' => $vln['account_id'],
                                'mode' => 'rtgs'
                            ]);
                        // }
                        if(!empty($references) && count($references) > 0){
                            foreach ($references as $key => $ref) {
                                $isOldBill = false;
                                if ($ref->old_reference_id) {
                                    $isOldBill = true;
                                }
        
                                if ($isOldBill) {
                                    $refId = $oldRefMapper[$ref->old_reference_id];
                                    
                                    $reference = Reference::find($refId);
        
                                    $allocationType = [
                                        '1' => ReferenceAllocation::AGAINST_REF,
                                        '2' => ReferenceAllocation::ADVANCE_ADJUSTMENT,
                                    ];
        
        
                                    ReferenceAllocation::create([
                                        'company_id' => $companyId,
                                        'financial_year_id' => $financialYearId,
                                        'voucher_id' => $voucher->id,
                                        'reference_id' => $reference->id,
                                        'amount' => $ref->receive_amount,
                                        'allocation_type' => $allocationType[1],
                                        'reference_number' => $ref->sale_bill_no,
                                        'account_id' => $reference->account_id,
                                        'source_type' => $reference->source_type,
                                        'source_id' => $reference->source_id,
        
                                    ]);
        
                                    $reference->settled_amount +=  $ref->receive_amount;
                                    $reference->pending_amount  = $reference->amount - $reference->settled_amount;
                                    $reference->is_closed = $reference->pending_amount == 0;
                                    $reference->closed_at = $reference->is_closed ? Carbon::now() : null;
                                    $reference->save();
        
                                    // $reference
                                } else {
                                    
                                    // dd($newSalesBills);
                                    // dd($ref);
                                    $voucherId = $newSalesBills[$ref->sale_bill_no];
                                    $reference = Reference::where('voucher_id', $voucherId)->first();
                                    
                                    $allocationType = [
                                        '1' => ReferenceAllocation::AGAINST_REF,
                                        '2' => ReferenceAllocation::ADVANCE_ADJUSTMENT,
                                    ];
        
        
                                    ReferenceAllocation::create([
                                        'company_id' => $companyId,
                                        'financial_year_id' => $financialYearId,
                                        'voucher_id' => $voucher->id,
                                        'reference_id' => $reference->id,
                                        'amount' => $ref->receive_amount,
                                        'allocation_type' => $allocationType[1],
                                        'reference_number' => $ref->sale_bill_no,
                                        'account_id' => $reference->account_id,
                                        'source_type' => $reference->source_type,
                                        'source_id' => $reference->source_id,
        
                                    ]);
        
                                    $reference->settled_amount +=  $ref->receive_amount;
                                    $reference->pending_amount  = $reference->amount - $reference->settled_amount;
                                    $reference->is_closed = $reference->pending_amount == 0;
                                    $reference->closed_at = $reference->is_closed ? Carbon::now() : null;
                                    $reference->save();
                                   
                                }
                            }
                        }
                    }
                    

                    // if (isset($crNtref[$rv->receipt_voucher_no]) && count($crNtref[$rv->receipt_voucher_no]) > 0) {
                    //             $arr = [
                    //                 '55630' => 1,
                    //                 '55631' => 2,
                    //                 '55632' => 3
                    //             ];

                    //             $allocationType = [
                    //                 '1' => ReferenceAllocation::AGAINST_REF,
                    //                 '2' => ReferenceAllocation::ADVANCE_ADJUSTMENT,
                    //             ];


                    //             foreach ($crNtref[$rv->receipt_voucher_no] as $key => $crntV) {
                    //                 $voucherId = Voucher::where('company_id', $companyId)->where('voucher_type_id', 10)->where('voucher_serial', $crntV->voucher_number)->value('id');

                    //                 $reference = Reference::where('voucher_id', $voucherId)->first();

                    //                 ReferenceAllocation::create([
                    //                     'company_id' => $companyId,
                    //                     'financial_year_id' => $financialYearId,
                    //                     'voucher_id' => $voucher->id,
                    //                     'reference_id' => $reference->id,
                    //                     'amount' => $reference->amount,
                    //                     'allocation_type' => $allocationType[1],
                    //                     'reference_number' => $reference->reference_number,
                    //                     'account_id' => $reference->account_id,
                    //                     'source_type' => $reference->source_type,
                    //                     'source_id' => $reference->source_id,

                    //                 ]);

                    //                 $reference->settled_amount = $reference->amount;
                    //                 $reference->pending_amount = 0;
                    //                 $reference->is_closed = true;
                    //                 $reference->closed_at = Carbon::now();
                    //                 $reference->save();
                    //             }
                    //         }

                }
            );
    }
}

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
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\PurchaseOrder;
use App\Models\Reference;
use App\Models\ReferenceItem;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class OldRefOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:old-ref {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Old Reference Data Insert';

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
        $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        // $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->get();
        // $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        // $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        // $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');

        // current company's creditor and debtor accounts and its child groups
        // $accounts = Account::where('company_id', $companyId)->orderBy('id')->pluck('account_group_id', 'id');


        // $group = AccountGroup::where('company_id', $companyId)->pluck('is_party_group', 'id');




        $isPartyGroupIds = [];

        // foreach ($accounts as $accountId => $groupId) {
        //     $isPartyGroupIds[$accountId] = $group[$groupId] ?? 0;
        // }

        $ovd = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])
            ->where('is_old_bill', true)
            ->whereNull('deleted_at')->get()->groupBy('voucher_id');


        DB::connection('old_db')
            ->table('old_references')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($old_references) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $isPartyGroupIds,
                $itemMaster,
                $destinationMaster,
                $ovd
            ) {
                foreach ($old_references as $or) {

                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $or->account_id && $am->account_type == $type[$or->account_type]) {
                            $accountId = $am->new_account_id;
                            break;
                        }
                    }

                    $method = [
                        1 => Reference::NewReference,
                        2 => Reference::Advance,
                    ];

                    $voucherType = [
                        1 => 'credit',
                        2 => 'debit',
                        3 => 'payment',
                        4 => 'credit',
                        5 => 'debit',
                        10 => 'credit',
                    ];
                    $payAmount = $or->pay_amount;

                     if($or->voucher_type == 5){
                        $payAmount = $or->net_total;
                    }
                    
                    // if(isset($ovd[$or->id]) && $ovd[$or->id]){
                        
                    //     $payAmount = 0;
                    //     dd($ovd[$or->id]);
                    //     foreach ($ovd[$or->id] as $key => $ovdDetail) {
                    //         if($or->voucher_type == 5){
                    //             $payAmount += $ovdDetail->net_total;
                    //         }else{
                    //             $payAmount += $ovdDetail->pay_amount;
                    //         }
                    //     }
                    // }
                    // if($or->id == 4807){
                    //     dd(2222);
                    // }


                    $ref = Reference::create([
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'account_id' => $accountId,
                        'reference_number' => $or->ref_no,
                        'reference_date' => $or->ref_date,
                        'file_number' => $or->file_no,
                        'reference_type' => $method[$or->method],
                        'amount' => $or->net_total,
                        'settled_amount' => 0,
                        'pending_amount' => $payAmount,
                        'is_hold' => $or->is_hold,
                        'is_closed' => false,
                        'closed_at' => null,
                        'voucher_id' => null,
                        'source_id' => null,
                        'direction' => $voucherType[$or->voucher_type] ?? null,
                    ]);

                    ReferenceItem::create([
                        'reference_id' => $ref->id,
                        'destination_id' => $destinationMaster[$or->destination_id] ?? null,
                        'item_id' => $itemMaster[$or->product_id] ?? null,
                        'quantity' => $or->gross_qty,
                        'rate' => $or->rate,
                        'rebate' => $or->rebate,
                        'cd_percentage' => $or->cd_percentage,
                        'cd_amount' => $or->cd,
                        'net_total' => $or->net_total,
                        'total_amount' => $or->total_amount,
                     ]);

                     OldRefMapping::create([
                        'company_id' => $companyId,
                        'old_ref_id' => $or->id,
                        'new_ref_id' => $ref->id,
                        'name' => $or->ref_no,
                     ]);
                
                }
            });
    }
}

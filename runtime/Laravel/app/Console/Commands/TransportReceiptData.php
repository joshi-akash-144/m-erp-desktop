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
use App\Models\JournalVoucher;
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\PurchaseOrder;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\TransportJournalMapping;
use App\Models\VehicleMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class TransportReceiptData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:t-receipt {company_id} {financial_year_id}';

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



        $type = [3 => 'account', 1 => 'supplier', 2 => 'customer'];

        // Pre-fetch all mappings before the loop to avoid N+1 queries
        // $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        // $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        // $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->pluck('new_account_id', 'old_account_id');
        // $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        // $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        // $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');

        // current company's creditor and debtor accounts and its child groups
        // $accounts = Account::where('company_id', $companyId)->orderBy('id')->pluck('account_group_id', 'id');


        // $group = AccountGroup::where('company_id', $companyId)->pluck('is_party_group', 'id');


        // $isPartyGroupIds = [];

        // foreach ($accounts as $accountId => $groupId) {
        //     $isPartyGroupIds[$accountId] = $group[$groupId] ?? 0;
        // }






        
        $bankGroupIds = AccountGroup::where('company_id', $companyId)
            ->where('code', 130)
            ->pluck('id')
            ->toArray();

        $bankIds = Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $bankGroupIds)
            ->pluck('id')
            ->toArray();

        DB::connection('old_db')
            ->table('transport_vouchers')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')
            ->where('voucher_type', 3)
            ->orderBy('id')
            ->chunk(200, function ($journals) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
               
                $bankIds
            ) {
                
                $allDetails = DB::connection('old_db')
                    ->table('transport_voucher_details')
                    ->where('voucher_type',3)
                    ->whereIn('transport_voucher_id', $journals->pluck('id'))
                    ->orderBy('payment_mode', 'asc')
                    ->get()
                    ->groupBy('transport_voucher_id');


        
                // $driverSalary = DB::connection('old_db')->table('driver_salary_details')->where('cc_id', 47)->get()->groupBy('voucher_no');

                foreach ($journals as $pv) {
                    $detialData = $allDetails->get($pv->id, collect());
                    
                    // $newExpId = $accountMaster[$pv->expense_account_type] ?? null;

                    $serial = $pv->voucher_number;
                    //  if($serial == 3894){
                    //     dd( $pv);
                    // }

                    if (Voucher::where('company_id', $companyId)
                        ->where('financial_year_id', $financialYearId)
                        ->where('voucher_type_id', VoucherType::RECEIPT)
                        ->where('voucher_serial', $serial)
                        ->exists()
                    ) {
                        $serial = Voucher::where('company_id', $companyId)
                            ->where('financial_year_id', $financialYearId)
                            ->where('voucher_type_id', VoucherType::RECEIPT)
                            ->max('voucher_serial') + 1;
                    }

                    
                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::RECEIPT,
                        'voucher_serial'    => $serial,
                        'voucher_number'    => $serial,
                        'voucher_date'      => $pv->voucher_date,
                        'reference_number'  => null,
                        'source_type'       => SourceType::RECEIPT,
                        'narration'         => $pv->narration
                    ]);

                    $detialData = $allDetails->get($pv->id, collect());

                    $voucherLine = [];
                  

                    //   if($serial == 3894){
                    //     dd( $pv);
                    // }

                    $crId = null;
                    $amount  = 0;
                    $isEntryPost = false;
                    $drAgianstId = null;
                    $crAgianstId = null;
                    $firstDebitId = null;
                    foreach($detialData as $line => $ldData){
                        if($ldData->payment_mode==1 && !$crAgianstId){
                            $crAgianstId = $accountMaster[$ldData->account_id] ?? null;
                            if(!$crAgianstId){
                                dd($ldData, $ldData->account_id);
                            }
                        }
                        if($ldData->payment_mode==2){
                            $mappedId = $accountMaster[$ldData->account_id] ?? null;
                            if(!$mappedId){
                                dd($ldData);
                            }
                            // Prefer bank account as the against for the party credit line
                            if(!$drAgianstId && in_array($mappedId, $bankIds)){
                                $drAgianstId = $mappedId;
                            }
                            if(!$firstDebitId){
                                $firstDebitId = $mappedId;
                            }
                        }
                    }
                    // Fallback to first debit if no bank account found among debit lines
                    if(!$drAgianstId){
                        $drAgianstId = $firstDebitId;
                    }

                    foreach ($detialData as $line => $ldData) {


                      $ldAccountId = $accountMaster[$ldData->account_id] ?? null;

                        if($ldData->payment_mode == 2){
                            
                            $voucherLine[] = [
                                'voucher_id' => $voucher->id,
                                'account_id' => $ldAccountId,
                                'debit'  => $ldData->amount,
                                'credit' => 0,
                                'narration' => null,
                                'is_party_account' =>  true,
                                'line_no' => $line + 1,
                                'against_account_id' => $crAgianstId
                            ];
                        }else{
                            
                            $voucherLine[] = [
                                'voucher_id' => $voucher->id,
                                'account_id' => $ldAccountId,
                                'debit'  => 0,
                                'credit' => $ldData->amount,
                                'narration' => null,
                                'is_party_account' =>  true,
                                'line_no' => $line + 1,
                                'against_account_id' => $drAgianstId
                            ];
                        }

                        
                            
                            
                        
                    }
                   
                    VoucherTransaction::insert($voucherLine);

                    $paidAmount = 0;
                    $bakId = null;
                    $drAccountId = null;

                    if(count($voucherLine) > 2){
                        foreach($voucherLine as $vc){
                            if($vc['credit'] > 0 & in_array($vc['account_id'], $bankIds)){
                                $paidAmount = $vc['credit'];
                                $bakId = $vc['account_id'];
                            }else{
                                $drAccountId = $vc['account_id'];
                            }
                            
                        }
                    }else{
                        foreach($voucherLine as $vc){
                            if($vc['credit'] > 0){
                                $paidAmount = $vc['credit'];
                                $bakId = $vc['account_id'];
                            }
                            else{
                                $drAccountId = $vc['account_id'];
                            }
                        }
                    }

                    $journal = ReceiptVoucher::create([
                          'voucher_id' => $voucher->id,
                           'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                            'received_amount' => $paidAmount,
                            'entry_from' => ReceiptVoucher::ENTRY_FROM_RECEIPT_VOUCHER,
                            
                    ]);

                    $voucher->update([
                        'source_id' => $journal->id
                    ]);
                }
            });
    }
}

<?php

namespace App\Console\Commands;

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
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class ChequeOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cheque {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cheque Data Insert';

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

        // $payments = DB::connection('old_db')
        //     ->table('payments')
        //     ->where('cc_id', $ccId[$companyId])->get();




        // $godownDetail = DB::connection('old_db')
        //     ->table('godowns')
        //     ->where('cc_id', $ccId[$companyId])
        //     ->get()
        //     ->groupBy('grn_number');
        
        
        // $nGrn = Grn::with('details')->where('company_id', $companyId)->pluck('id', 'grn_serial');
        

        DB::connection('old_db')
            ->table('payments')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($pm) use (
                $companyId,
                $financialYearId,
               
                $accountMaster,
               
                $type,
               
            ) {
                foreach ($pm as $p) {
                    $bankId = null;
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $p->bank_id && $am->account_type == $type[3]) {
                            $bankId = $am->new_account_id;
                            break;
                        }
                    }
                   $payment = Payment::create([
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'bank_id' => $bankId,
                        'payment_date' => $p->payment_date,
                        'mode' => 'rtgs',
                        'amount' => $p->amount == null ? 0 : $p->amount,
                        'cheque_number' => $p->cheque_no,
                        'cheque_date' => $p->payment_date,
                        'cheque_time' => $p->cheque_time,
                        'utr_number' => null,
                        'cheque_name' => null,
                        'ac_pay' => 'N',
                        'rtgs' => 'N',
                   ]);

                   ChequeMapping::create([
                        'company_id' => $companyId,
                        'new_payment_id' => $payment->id,
                        'old_payment_id' => $p->id,
                   ]);


                }
            });
    }
}

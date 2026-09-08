<?php

namespace App\Console\Commands;

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
use App\Models\PurchaseOrder;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class GodownOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:godown {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Godown Data Insert';

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
        $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->get();
        $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');


        // $godownDetail = DB::connection('old_db')
        //     ->table('godowns')
        //     ->where('cc_id', $ccId[$companyId])
        //     ->get()
        //     ->groupBy('grn_number');
        
        
        $nGrn = Grn::with('details')->where('company_id', $companyId)->pluck('id', 'grn_serial');
        

        DB::connection('old_db')
            ->table('godowns')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->chunk(200, function ($grns) use (
                $companyId,
                $financialYearId,
                $itemMaster,
                $conditionMaster,
                $destinationMaster,
                $accountMaster,
                $brokerMaster,
                $type,
                $nGrn,
                $godownUnit,
                $transporter
            ) {
                foreach ($grns as $od) {
                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if($am->old_account_id == $od->supplier_id && $am->account_type == $type[$od->account_type]){
                            $accountId = $am->new_account_id;
                        }
                    }


                    GodownModule::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'godown_id'         => $od->destination_id ?  ($destinationMaster[$od->destination_id] ?? null) : null,
                        'godown_unit_id'    => $od->godown_no ?  ($godownUnit[$od->godown_no] ?? null) : null,
                        'party_destination_id' => $od->party_destination_id ?  ($destinationMaster[$od->party_destination_id] ?? null) : null,
                        'grn_id'                => $nGrn[$od->grn_number] ?? null,
                        'delivery_challan_id' => null,
                        'dairy_po' => null,
                        'transporter_id' => $od->transporter_id ? $transporter[$od->transporter_id] : null,
                        'lr_number' => $od->lr_number ?? null,
                        'in_date' => $od->date_in,
                        'out_date' => $od->date_out,
                        'in_time' => $od->time_in,
                        'out_time' => $od->time_out,
                        'in_out_status' => $od->inout_status == 1 ? 'in' : 'out',
                        'challan_weight' => $od->challan_weight,
                        'challan_bags' => $od->challan_bags,
                        'is_manual' => $od->is_manual,
                        'is_crossing' => $od->is_crossing,
                        'is_cycle' => $od->is_cycle == 1 ?  'close' : 'open',
                    ]);
                }
            });
    }
}

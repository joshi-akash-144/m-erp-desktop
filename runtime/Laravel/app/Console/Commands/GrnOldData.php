<?php

namespace App\Console\Commands;

use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\ItemMapping;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class GrnOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:grn {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $grnDetails = DB::connection('old_db')
            ->table('grn_transactions')
            ->where('cc_id', $ccId[$companyId])
            ->get()
            ->groupBy('grn_id');
        
        $grnReceipt = DB::connection('old_db')->table('grn_receipts')
            ->where('cc_id', $ccId[$companyId])
            ->get()->keyBy('grn_id')->toArray();

        $purchaseOrder = PurchaseOrder::with('details')->where('company_id', $companyId)->get()->keyBy('order_serial');
        

        DB::connection('old_db')
            ->table('grns')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->whereNull('deleted_at')
            ->chunk(200, function ($grns) use (
                $companyId,
                $financialYearId,
                $itemMaster,
                $conditionMaster,
                $destinationMaster,
                $accountMaster,
                $brokerMaster,
                $type,
                $grnDetails,
                $grnReceipt,
                $purchaseOrder
            ) {
                foreach ($grns as $od) {
                    if($od->cc_id == 41) continue;
                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if($am->old_account_id == $od->supplier_id && $am->account_type == $type[$od->account_type]){
                            $accountId = $am->new_account_id;
                        }
                    }

                    $grn = Grn::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'grn_serial'        => $od->grn_number,
                        'grn_number'        => $od->grn_number,
                        'account_id'        => $accountId,
                        'broker_id'         => $brokerMaster[$od->broker_id] ?? null,
                        'contract_number'   => $od->contract_number,
                        'grn_date'          => $od->grn_date,
                        'grn_in_date'       => $od->date_in,
                        'party_type'      => $type[$od->account_type],
                        'grn_out_date' => $od->date_out,
                        'gst_type' => $od->grn_type == 'OGS' ? Grn::TAX_INTERSTATE : Grn::TAX_LOCAL,
                        'reference_number' => $od->other_ref_no,
                        'vehicle_number' => $od->vehical_no,
                        'gross_weight' => $grnReceipt[$od->grn_number]->gross_weight ?? 0,
                        'tare_weight' => $grnReceipt[$od->grn_number]->tare_weight ?? 0,
                        'net_weight' => $grnReceipt[$od->grn_number]->net_weight ?? 0,
                        'bag_type' => $grnReceipt[$od->grn_number]->bag_type ?? 'gunny',
                        'bag_count' => $grnReceipt[$od->grn_number]->dairybag ?? 0,
                        'net_weight_wt_bag' => $grnReceipt[$od->grn_number]->net_without_bags ?? 0,
                        'total_quantity' => 0,
                        'is_skip_serial_generation' => $od->roll_over_at ? true : false,
                        'remarks' => $od->grn_comment,
                        'grn_status' => $od->is_billing == 'Completed' ? Grn::STATUS_BILLED : Grn::STATUS_OPEN,
                    ]);

                   $qty  = 0;

                    foreach ($grnDetails[$od->grn_number] as $key => $gd) {
                        $purchaseOrderId = null;
                        $purchaseOrderSerial = null;
                        $purchaseOrderItemId = null;

                        if($od->account_type == 1){
                            $purchaseOrderId = $gd->order_no ? $purchaseOrder[$gd->order_no]->id : null;
                            $purchaseOrderSerial = $gd->order_no;
                            $purchaseOrderItemId = $purchaseOrder[$gd->order_no][0]->order_no ?? null;
                        }

                        GrnItem::create([
                            'grn_id' => $grn->id,
                            'item_id' =>        $itemMaster[$gd->product_id],
                            'condition_id'          => $gd->condition_id ? ($conditionMaster[$gd->condition_id] ?? null) : null,
                            'destination_id'        => $gd->destination_id ?  ($destinationMaster[$gd->destination_id] ?? null) : null,
                            'purchase_order_id'     =>  $purchaseOrderId,
                            'purchase_order_serial' => $purchaseOrderSerial,
                            'purchase_order_item_id' => $purchaseOrderItemId,
                            'quantity'              => $gd->qty ?? 0,
                            'party_quantity'        => $gd->p_qty ?? 0,
                            'bag_count'             => $gd->bags ?? 0,
                            'rate'                  => $gd->rate ?? 0,
                            'inclusive_rate'        => $gd->incltax_rate ?? 0,
                            'taxable_amount'        => 0,
                            'amount'                => $gd->amount ?? 0,
                            'net_amount'            => $gd->amount ?? 0,
                            'grand_total'           => $gd->amount ?? 0
                        ]);
                        $qty += $gd->qty;
                    } 

                    $grn->update([
                        'total_quantity' => $qty
                    ]);
                }
            });
    }
}

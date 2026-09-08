<?php

namespace App\Console\Commands;

use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\ItemMapping;
use App\Models\PurchaseOrder as ModelsPurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class PurchaseOrderOldData extends Command
{

    

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purchase-order {company_id} {financial_year_id}';

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
            1 => '39', 2 => '40', 3 => '41', 4 => '42', 5 => '43',
            6 => '44', 7 => '45', 8 => '46', 9 => '47',
        ];

        $type = [3 => 'account', 1 => 'supplier', 2 => 'customer'];

        // Pre-fetch all mappings before the loop to avoid N+1 queries
        $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::whereIn('account_type', array_values($type))->pluck('new_account_id', 'old_account_id');
        $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');

        DB::connection('old_db')
            ->table('purchase_orders')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->chunk(200, function ($oldPurchaseOrders) use (
                $companyId, $financialYearId,
                $itemMaster, $conditionMaster, $destinationMaster, $accountMaster, $brokerMaster
            ) {
                foreach ($oldPurchaseOrders as $op) {
                    $purchaseOrder = ModelsPurchaseOrder::create([
                        'uuid'              => $op->unique_token ?? Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'order_serial'      => $op->po_number,
                        'order_number'      => $op->po_number,
                        'broker_id'         => $brokerMaster[$op->broker_id] ?? null,
                        'account_id'        => $accountMaster[$op->supplier_id] ?? null,
                        'destination_id'    => $destinationMaster[$op->destination_id] ?? null,
                        'contract_number'   => $op->contract_number,
                        'delivery_days'     => $op->delivery_days,
                        'is_skip_serial_generation' => $op->roll_over_at ? true : false,

                        'order_date'        => $op->po_date,
                        'due_date'          => $op->due_date,
                        'total_quantity'    => $op->qty,
                        'remarks'           => $op->po_remark,
                        'order_status'      => $op->status == 1 ? ModelsPurchaseOrder::STATUS_CLOSE : ModelsPurchaseOrder::STATUS_OPEN,
                    ]);

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'item_id'           => $itemMaster[$op->product_id],
                        'condition_id'      => $op->condition_id ? ($conditionMaster[$op->condition_id] ?? null) : null,
                        'ordered_qty'       => $op->qty,
                        'received_qty'      => $op->rec_qty,
                        'rate'              => $op->rate,
                        'inclusive_rate'    => $op->incltax_rate,
                        'is_closed'         => $op->status == 1,
                        'amount'            => $op->qty * $op->rate,
                        'taxable_amount'    => $op->qty * $op->rate
                    ]);
                }
            });
    }
}

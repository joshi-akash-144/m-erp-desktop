<?php

namespace App\Console\Commands;

use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\ItemMapping;
use App\Models\SalesOrder as ModelSaleOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class SalesOrderOldData extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sales-order {company_id} {financial_year_id}';

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
            ->table('sales_orders')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->chunk(200, function ($oldPurchaseOrders) use (
                $companyId, $financialYearId,
                $itemMaster, $conditionMaster, $destinationMaster, $accountMaster, $brokerMaster
            ) {
                foreach ($oldPurchaseOrders as $os) {
                    $purchaseOrder = ModelSaleOrder::create([
                        'uuid'              => $os->unique_token ?? Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'order_serial'      => $os->so_number,
                        'order_number'      => $os->so_number,
                        'account_id'        => $accountMaster[$os->vendor_id] ?? null,
                        'purchase_order_number' => $os->po_number,
                        
                        'order_status'      => $os->status == 1 ? ModelSaleOrder::STATUS_CLOSE : ModelSaleOrder::STATUS_OPEN,
                        'purchase_order_date' => $os->po_date,
                        
                        'delivery_date' => $os->delivery_date,
                        'delivery_days' => $os->delivery_days,
                        'is_skip_serial_generation' => $os->roll_over_at ? true : false,
                        // 'due_date' => ,

                        'remarks' => $os->so_remark,
                        

                        'total_quantity' => $os->qty,
                        'grand_total' => $os->amount,
                        'sub_total' => $os->amount,
                    ]);


                    SalesOrderItem::create([
                        'sales_order_id' => $purchaseOrder->id,
                        'item_id'           => $itemMaster[$os->product_id],
                        'condition_id'      => $os->condition_id ? ($conditionMaster[$os->condition_id] ?? null) : null,
                        'ordered_qty'       => $os->qty,
                        'destination_id'    => $os->destination_id ? ($destinationMaster[$os->destination_id] ?? null) : null,
                        'received_qty'      => $os->receiving_qty,
                        'rate'              => $os->rate,
                        'inclusive_rate'    => $os->incltax_rate,
                        'is_closed'         => $os->status == 1,
                        'amount'            => $os->qty * $os->rate,
                        'taxable_amount'    => $os->qty * $os->rate
                    ]);
                }
            });
    }
}

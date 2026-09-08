<?php

namespace App\Services;

use App\DTOs\GrnDTO;
use App\DTOs\DeliveryChallanDTO;
use App\Models\Grn;
use App\Models\DeliveryChallan;
use App\Models\Moisture;
use App\Repositories\GodownModuleRepository;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\GodownModule;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Str;

class GodownModuleService
{
    protected GodownModuleRepository $godownRepo;
    protected GrnService $grnService;
    protected VoucherService $voucherService;
    protected DeliveryChallanService $deliveryChallanService;
    public function __construct(GodownModuleRepository $godownRepo, GrnService $grnService, VoucherService $voucherService, DeliveryChallanService $deliveryChallanService)
    {
        $this->godownRepo = $godownRepo;
        $this->grnService = $grnService;
        $this->voucherService = $voucherService;
        $this->deliveryChallanService = $deliveryChallanService;
    }

    public function storeProductIn(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            // $uuid = $data['uuid'];
            // if (GodownModule::where('uuid', $uuid)->exists()) {
            //     throw ValidationException::withMessages([
            //         'uuid' => ['already store']
            //     ]);
            // }
            // first create GRN
            $grn = $this->createGrn($data, $companyId, $financialYearId);
            // then create GodownEntry
            $godownEntry = GodownModule::create([
                'uuid' => $data['uuid'],
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'grn_id' => $grn->id,
                'godown_id' => $data['godown_id'],
                'party_destination_id' => $data['party_destination_id'] ?? null,
                'godown_unit_id' => $data['godown_unit_id'],
                'delivery_challan_id' => null,
                'transporter_id' => $data['transporter_id'],
                'in_out_status' => GodownModule::PRODUCT_IN,
                'lr_number' => $data['lr_number'],
                'challan_date' => $data['challan_date'] ?? null,
                'in_date' => date('Y-m-d'),
                'out_date' => date('Y-m-d'),
                'in_time' => date('H:i:s'),
                'out_time' => date('H:i:s'),
                'challan_weight' => $data['challan_weight'] ?? 0,
                'challan_bags' => $data['challan_bags'] ?? 0,
                'is_manual' => $data['is_manual'],
                'is_crossing' => $data['is_crossing'],
                'is_cycle' => GodownModule::CYCLE_OPEN,
                'created_by' => current_user_id(),
            ]);
            
            $godownEntry->grn_serial = $grn->grn_serial ?? '';
            
            $this->moisture($data, $godownEntry, $companyId, $financialYearId);

            return $godownEntry; // Grn Number
        });
    }

    public function updateProductIn(GodownModule $godownModule, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($godownModule, $data, $companyId, $financialYearId) {
            // First update GRN if it exists
            if ($godownModule->grn_id) {
                // Prepare GRN payload similar to createGrn
                $grnData = [
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'uuid'             => $godownModule->grn->uuid ?? $data['uuid'],
                    'grn_date'         => $data['grn_in_date'],
                    'grn_in_date'      => $data['grn_in_date'] ?? null,
                    'grn_out_date'     => $data['grn_out_date'] ?? null,
                    'contract_number'  => null,
                    'broker_id'        => null,
                    'account_id'       => $data['party_id'] ?? null,
                    // Preserve original reference_number — do not trigger uniqueness error
                    'reference_number' => $godownModule->grn->reference_number ?? ($data['reference_number'] ?? null),
                    'vehicle_number'   => $data['vehicle_number'] ?? null,
                    'remarks'          => $data['remarks'] ?? null,
                    'gross_weight'     => $data['gross_weight'] ?? 0,
                    'tare_weight'      => $data['tare_weight'] ?? 0,
                    'net_weight'       => $data['net_weight'] ?? 0,
                    'bag_count'        => (int)($data['bag_count'] ?? 0),
                    'bag_type'         => $data['bag_type'] ?? null,
                    'entry_from'       => Grn::ENTRY_FROM_GODOWN,
                ];
                $qty = ($data['net_weight_wt_bag'] ?? 0) / 1000;
                $partyQty = ($data['challan_weight'] ?? 0) / 1000;
                $amount = $qty * ($data['rate'] ?? 0);

                $grnData['items'][] = [
                    'item_id'                   => $data['item_id'],
                    'quantity'                  => $qty,
                    'party_quantity'            => $partyQty,
                    'rate'                      => $data['rate'] ?? 0,
                    'inclusive_rate'            => $data['rate'] ?? 0,
                    'amount'                    => $amount,
                    'bag_count'                 => $data['bag_count'] ?? 0,
                    'purchase_order_id'         => null,
                    'purchase_order_item_id'    => null,
                    'net_amount'                => $amount,
                    'condition_id'              => null,
                    'destination_id'            => $data['godown_id'] ?? null,
                    'purchase_order_serial'     => null,
                ];
                
                $this->grnService->updateGrn($grnData, $companyId, $financialYearId, $godownModule->grn_id);
            }

            // Then update GodownEntry
            $godownModule->update([
                'godown_id' => $data['godown_id'],
                'godown_unit_id' => $data['godown_unit_id'],
                'transporter_id' => $data['transporter_id'] ?? $godownModule->transporter_id,
                // Preserve original lr_number — do not trigger uniqueness error
                'lr_number'  => $data['lr_number'] ?? $godownModule->lr_number,
                'challan_date' => $data['challan_date'] ?? $godownModule->challan_date,
                'in_date' => $godownModule->in_date,       // Never change date_in on update
                'out_date' => date('Y-m-d'),               // Always set current date when vehicle exits
                'in_time' => $godownModule->in_time,       // Never change time_in on update
                'out_time' => date('H:i:s'),               // Always set current time when vehicle exits
                'challan_weight' => $data['challan_weight'] ?? $godownModule->challan_weight,
                'challan_bags' => $data['challan_bags'] ?? $godownModule->challan_bags,
                'is_manual' => $godownModule->is_manual ? 1 : ($data['is_manual'] ?? $godownModule->is_manual),
                'is_crossing' => $data['is_crossing'] ?? $godownModule->is_crossing,
                'is_cycle' => GodownModule::CYCLE_CLOSE, // Always close the cycle on update when a vehicle is selected
                'updated_by' => current_user_id(),
            ]);

            $godownModule->load('grn');
            $godownModule->grn_serial = $godownModule->grn->grn_serial ?? '';

            $this->moisture($data, $godownModule, $companyId, $financialYearId);

            return $godownModule;
        });
    }

    public function storeProductOut($data, $companyId, $financialYearId){

      return DB::transaction(function () use ($data, $companyId, $financialYearId) {

            // first create Delivery Challan
            // $deliveryChallan = $this->createDeliveryChallan($data, $companyId, $financialYearId);
            $deliveryChallan = $this->createGrn($data, $companyId, $financialYearId);
            // then create GodownEntry
            $godownEntry = GodownModule::create([
                'uuid' => $data['uuid'],
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'grn_id' => $deliveryChallan->id,
                'godown_id' => $data['godown_id'] ?? null,
                'godown_unit_id' => $data['godown_unit_id'] ?? null,
                'delivery_challan_id' => null,
                'dairy_po' => $data['dairy_po'] ?? null,
                'transporter_id' => $data['transporter_id'],
                'in_out_status' => GodownModule::PRODUCT_OUT,
                'lr_number' => $data['lr_number'],
                'party_destination_id' => $data['party_destination_id'] ?? null,
                'in_date' => date('Y-m-d'),
                'out_date' => date('Y-m-d'),
                'in_time' => date('H:i:s'),
                'out_time' => date('H:i:s'),
                'challan_weight' => $data['challan_weight'] ?? 0,
                'challan_bags' => $data['challan_bags'] ?? 0,
                'is_manual' => $data['is_manual'],
                'is_crossing' => $data['is_crossing'],
                'is_cycle' => GodownModule::CYCLE_OPEN,
                'created_by' => current_user_id(),
            ]);
            
            // $godownEntry->dc_serial = $deliveryChallan->challan_serial ?? '';
            $godownEntry->grn_serial = $deliveryChallan->grn_serial ?? '';
            
            $this->moisture($data, $godownEntry, $companyId, $financialYearId);

            // dd($godownEntry);
            return $godownEntry; // Delivery Challan Number
        });
    }
    public function updateProductOut(GodownModule $godownModule, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($godownModule, $data, $companyId, $financialYearId) {
            // First update Delivery Challan if it exists
            // if ($godownModule->delivery_challan_id) {
            if ($godownModule->grn_id) {
                // Prepare DC payload
                $deliveryChallanData = [
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'uuid'             => $godownModule->grn->uuid ?? $data['uuid'],
                    'grn_date'         => $data['challan_date'] ?? $data['dc_in_date'] ?? date('Y-m-d'),
                    'grn_in_date'      => $data['dc_in_date'] ?? $data['date_in'] ?? null,
                    'grn_out_date'     => $data['date_out'] ?? null,
                    'date_in'          => $data['date_in'] ?? $data['dc_in_date'] ?? null,
                    'date_out'         => $data['date_out'] ?? null,
                    'contract_number'  => null,
                    'broker_id'        => null,
                    'account_id'       => $data['party_id'] ?? null,
                    // Preserve original reference_number
                    // 'reference_number' => $godownModule->deliveryChallan->reference_number ?? ($data['reference_number'] ?? null),
                    'reference_number' => $godownModule->deliveryChallan->reference_number ?? ($data['reference_number'] ?? null),
                    'vehicle_number'   => $data['vehicle_number'] ?? null,
                    'remarks'          => $data['remarks'] ?? null,
                    'gross_weight'     => $data['gross_weight'] ?? 0,
                    'tare_weight'      => $data['tare_weight'] ?? 0,
                    // 'net_weight'       => $this->deliveryChallanService->net_weight($data['gross_weight'] ?? 0, $data['tare_weight'] ?? 0),
                    'net_weight'       => $data['net_weight'] ?? 0,
                    'bag_count'        => (int)($data['bag_count'] ?? 0),
                    'bag_type'         => $data['bag_type'] ?? null,
                    'entry_from'       => Grn::ENTRY_FROM_GODOWN,
                ];
                $qty = ($data['net_weight_wt_bag'] ?? 0) / 1000;
                $partyQty = ($data['challan_weight'] ?? 0) / 1000;
                $amount = $qty * ($data['rate'] ?? 0);

                $deliveryChallanData['items'][] = [
                    'item_id'                   => $data['item_id'],
                    'destination_id'            => $data['godown_id'],
                    'quantity'                  => $qty,
                    'party_quantity'            => $partyQty,
                    'rate'                      => $data['rate'] ?? 0,
                    'inclusive_rate'            => $data['rate'] ?? 0,
                    'amount'                    => $amount,
                    'bag_count'                 => $data['bag_count'] ?? 0,
                    'sales_order_id'            => $data['so_no'] ?? null,
                    'sales_order_item_id'       => null,
                    'net_amount'                => $amount,
                    'condition_id'              => null,
                    'sales_order_serial'        => null,
                ];
                
                // $this->deliveryChallanService->updateDeliveryChallan($deliveryChallanData, $companyId, $financialYearId, $godownModule->delivery_challan_id);
                $this->grnService->updateGrn($deliveryChallanData, $companyId, $financialYearId, $godownModule->grn_id);
            }

            // Then update GodownEntry
            $godownModule->update([
                'godown_id' => $data['godown_id'],
                'godown_unit_id' => $data['godown_unit_id'],
                'dairy_po' => $data['dairy_po'] ?? null,
                'transporter_id' => $data['transporter_id'] ?? $godownModule->transporter_id,
                'lr_number'               => $data['lr_number'] ?? $godownModule->lr_number,
                'party_destination_id'    => $data['party_destination_id'] ?? $godownModule->party_destination_id,
                'in_date' => $godownModule->in_date,       // Never change date_in on update
                'out_date' => date('Y-m-d'),               // Always set current date when vehicle exits
                'in_time' => $godownModule->in_time,       // Never change time_in on update
                'out_time' => date('H:i:s'),               // Always set current time when vehicle exits
                'challan_weight' => $data['challan_weight'] ?? $godownModule->challan_weight,
                'challan_bags' => $data['challan_bags'] ?? $godownModule->challan_bags,
                'is_manual' => $godownModule->is_manual ? 1 : ($data['is_manual'] ?? $godownModule->is_manual),
                'is_crossing' => $data['is_crossing'] ?? $godownModule->is_crossing,
                'is_cycle' => GodownModule::CYCLE_CLOSE, // Always close the cycle on update when a vehicle is selected
                'updated_by' => current_user_id(),
            ]);

            // $godownModule->load('deliveryChallan');
            // $godownModule->dc_serial = $godownModule->deliveryChallan->challan_serial ?? '';
            $godownModule->load('grn');
            $godownModule->grn_serial = $godownModule->grn->grn_serial ?? '';

            $this->moisture($data, $godownModule, $companyId, $financialYearId);

            return $godownModule;
        });
    }

    private function createGrn(array $data, $companyId, $financialYearId)
    {
        $grnData = [
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'uuid'            => $data['uuid'],
            'grn_date'         => $data['dc_in_date'] ?? $data['grn_in_date'] ?? $data['date_in'] ?? null,
            'grn_in_date'      => $data['dc_in_date'] ?? $data['grn_in_date'] ?? $data['date_in'] ?? null,
            'grn_out_date'     => $data['date_out'] ?? $data['grn_out_date'] ?? null,
            'contract_number'  => null,
            'broker_id'        => null,
            'account_id'       => $data['party_id'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'vehicle_number'   => $data['vehicle_number'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'gross_weight'     => $data['gross_weight'] ?? 0,
            'tare_weight'      => $data['tare_weight'] ?? 0,
            'net_weight'       => $this->grnService->net_weight($data['gross_weight'], $data['tare_weight']),
            'bag_count'        => (int)($data['bag_count'] ?? 0),
            'bag_type'         => $data['bag_type'] ?? null,
            'entry_from'       => Grn::ENTRY_FROM_GODOWN,
            'created_by'       => current_user_id(),
        ];
        $qty = $data['net_weight_wt_bag'] / 1000; // convert kg to Ton
        $partyQty = $data['challan_weight'] / 1000; // convert kg to Ton
        $amount = $qty * $data['rate'];

        $grnData['items'][] = [
            'item_id'                   => $data['item_id'],
            'destination_id'            => $data['godown_id'] ?? null,
            'quantity'                  => $qty,
            'party_quantity'            => $partyQty,
            'rate'                      => $data['rate'],
            'inclusive_rate'            => $data['rate'] ?? 0,
            'amount'                    => $amount,
            'bag_count'                 => $data['bag_count'] ?? 0,
            'purchase_order_id'         => null,
            'purchase_order_item_id'    => null,
            'net_amount'                => $amount,
            'condition_id'              => null,
            'purchase_order_serial'     => null,
            'purchase_order_id'         => null,
            'purchase_order_item_id'    => null,
        ];
        return $this->grnService->createGrn($grnData, $companyId, $financialYearId);
    }


private function createDeliveryChallan($data, $companyId, $financialYearId){

    $deliveryChallanData = [
        'company_id' => $companyId,
        'financial_year_id' => $financialYearId,
        'uuid'            => $data['uuid'],
        'challan_date'         => $data['challan_date'] ?? $data['dc_in_date'] ?? date('Y-m-d'),
        'challan_in_date'      => $data['dc_in_date'] ?? null,
        'challan_out_date'     => $data['date_out'] ?? null,
        'contract_number'  => null,
        'broker_id'        => null,
        'account_id'       => $data['party_id'] ?? null,
        'reference_number' => $data['reference_number'] ?? null,
        'vehicle_number'   => $data['vehicle_number'] ?? null,
        'remarks'          => $data['remarks'] ?? null,
        'gross_weight'     => $data['gross_weight'] ?? 0,
        'tare_weight'      => $data['tare_weight'] ?? 0,
        'net_weight'       => $this->deliveryChallanService->net_weight($data['gross_weight'], $data['tare_weight']),
        'bag_count'        => (int)($data['bag_count'] ?? 0),
        'bag_type'         => $data['bag_type'] ?? null,
    ];
    $qty = $data['net_weight_wt_bag'] / 1000; // convert kg to Ton
    $partyQty = $data['challan_weight'] / 1000; // convert kg to Ton
    $amount = $qty * $data['rate'];

    $deliveryChallanData['items'][] = [
        'item_id'                   => $data['item_id'],
        'destination_id'            => $data['godown_id'],
        'quantity'                  => $qty,
        'party_quantity'            => $partyQty,
        'rate'                      => $data['rate'],
        'inclusive_rate'            => $data['rate'] ?? 0,
        'amount'                    => $amount,
        'bag_count'                 => $data['bag_count'] ?? 0,
        'sales_order_id'            => $data['so_no'] ?? null,
        'sales_order_item_id'       => null,
        'net_amount'                => $amount,
        'condition_id'              => null,
        'sales_order_serial'        => null,
    ];
    return $this->deliveryChallanService->createDeliveryChallan($deliveryChallanData, $companyId, $financialYearId);
}

    public function store(array $data, int $companyId, int $financialYearId, int $userId): GodownModule
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId, $userId) {
            $purchaseOrderId = $data['po_no'] ?? $data['purchase_order_id'] ?? null;
            if ($purchaseOrderId === '') {
                $purchaseOrderId = null;
            }
            $data['purchase_order_id'] = $purchaseOrderId;

            $saleOrderId = $data['so_no'] ?? $data['sale_order_id'] ?? null;
            if ($saleOrderId === '') {
                $saleOrderId = null;
            }
            $data['sale_order_id'] = $saleOrderId;

            $inOutStatus = $data['in_out_status'] ?? 'in';

            if ($inOutStatus === 'out') {
                // OUTBOUND (Delivery Challan) FLOW
                $deliveryChallan = DeliveryChallan::where('company_id', $companyId)
                    ->where('reference_number', $data['reference_number'] ?? null)
                    ->where('account_id', $data['account_id'] ?? null)
                    ->latest('id')
                    ->first();

                if ($deliveryChallan) {
                    // Delivery Challan found in DB — sync its data to the godown payload
                    $data['delivery_challan_id']     = $deliveryChallan->id;
                    $data['dc_serial'] = $deliveryChallan->challan_serial;
                    $data['dc_date']   = $deliveryChallan->challan_date;
                    $data['gst_type']   = $deliveryChallan->gst_type;

                    if (isset($data['remarks'])) {
                        $deliveryChallan->update(['remarks' => $data['remarks']]);
                    }
                } else {
                    // No matching Delivery Challan in DB — create it!
                    $lastDeliveryChallan = $this->voucherService->getLastDeliveryChallan($companyId, $financialYearId);
                    $serialInfo = $this->voucherService->getNextDeliveryChallanVoucherNumber($companyId, $financialYearId);
                    
                    $challanPayload = $this->prepareDeliveryChallanPayloadFromGodown($data, $userId);
                    
                    $challanPayload['challan_serial'] = $lastDeliveryChallan;
                    $challanPayload['challan_number'] = $serialInfo->challan_number;

                    $deliveryChallan = $this->deliveryChallanService->createDeliveryChallan($challanPayload, $companyId, $financialYearId, $userId);

                    $data['delivery_challan_id']     = $deliveryChallan->id;
                    $data['dc_serial'] = $deliveryChallan->challan_serial;
                    $data['dc_date']   = $deliveryChallan->challan_date;
                    $data['gst_type']   = $deliveryChallan->gst_type;
                }
            } else {
                // INBOUND (GRN) FLOW
                $grn = Grn::where('company_id', $companyId)
                    ->where('reference_number', $data['reference_number'] ?? null)
                    ->where('account_id', $data['account_id'] ?? null)
                    ->latest('id')
                    ->first();

                if ($grn) {
                    // GRN found in DB — sync its data to the godown payload
                    $data['grn_id']     = $grn->id;
                    $data['grn_serial'] = $grn->grn_serial;
                    $data['grn_date']   = $grn->grn_date;
                    $data['gst_type']   = $grn->gst_type;

                    if (isset($data['remarks'])) {
                        $grn->update(['remarks' => $data['remarks']]);
                    }
                } else {
                    // No matching GRN in DB — use VoucherService to fetch the next serial number
                    $lastGrn = $this->voucherService->getLastGrn($companyId, $financialYearId);
                    $serialInfo = $this->voucherService->getNextGrnVoucherNumber($companyId, $financialYearId);

                    $grnPayload = $this->prepareGrnPayloadFromGodown($data, $userId);
                    
                    // Assign the generated serial from getLastGrn() and formatted number
                    $grnPayload['grn_serial'] = $lastGrn;
                    $grnPayload['grn_number'] = $serialInfo->grn_number;

                    $grn = $this->grnService->createGrn($grnPayload, $companyId, $financialYearId);

                    $data['grn_id']     = $grn->id;
                    $data['grn_serial'] = $grn->grn_serial;
                    $data['grn_date']   = $grn->grn_date;
                    $data['gst_type']   = $grn->gst_type;
                }
            }

            // 2. Handle Entry (Update if godown_module_id exists, otherwise Create)
            $payload = $this->preparePayload($data, $companyId, $financialYearId, $userId);

//             if (!empty($data['godown_module_id'])) {
//                 $record = $this->godownRepo->find((int)$data['godown_module_id']);
//                 if ($record) {
//                     $payload['is_cycle'] = 'close';

//                     if (empty($payload['purchase_order_id']) && !empty($record->purchase_order_id)) {
//                         $payload['purchase_order_id'] = $record->purchase_order_id;
//                     }

//                     return ;
// $this->godownRepo->update($record, $payload);
//                 }
//             }

            // Default: Create new Godown Entry
            return ;
// $this->godownRepo->create($payload);
        });
    }

    protected function prepareDeliveryChallanPayloadFromGodown(array $data, int $userId): array
    {
        $salesOrderId = $data['sale_order_id'] ?? $data['purchase_order_id'] ?? null; // For outbound, sale_order_id or purchase_order_id holds the sales_order_id
        $salesOrderItemId = null;
        $salesOrderSerial = null;

        if ($salesOrderId) {
            $so = SalesOrder::where('id', $salesOrderId)->first();
            if ($so) {
                $salesOrderSerial = $so->order_serial;
                
                $soDetail = SalesOrderItem::where('sales_order_id', $salesOrderId)
                    ->where('item_id', $data['item_id'])
                    ->first();
                if ($soDetail) {
                    $salesOrderItemId = $soDetail->id;
                }
            }
        }

        // Wrap the single item from Godown into Delivery Challan items array
        $items = [[
            'item_id'                => $data['item_id'],
            'quantity'               => $data['total_quantity'] ?? $data['qty'] ?? 0,
            'party_quantity'         => $data['p_qty'] ?? 0,
            'rate'                   => $data['rate'] ?? 0,
            'inclusive_rate'         => $data['rate'] ?? 0,
            'amount'                 => $data['sub_total'] ?? 0,
            'bag_count'              => $data['bag_count'] ?? 0,
            'condition_id'           => $data['condition_id'] ?? null,
            'destination_id'         => $data['godown_id'] ?? null,
            'sales_order_id'         => $salesOrderId,
            'sales_order_item_id'    => $salesOrderItemId ?? $salesOrderId,
            'sales_order_serial'     => $salesOrderSerial,
        ]];

        return [
            'uuid'              => (string) Str::uuid(),
            'account_id'        => $data['account_id'],
            'broker_id'         => $data['broker_id'] ?? null,
            'challan_date'      => $data['date_out'] ?? $data['date_in'] ?? date('Y-m-d'),
            'challan_in_date'   => $data['date_in'] ?? null,
            'challan_out_date'  => $data['date_out'] ?? null,
            'vehicle_number'    => $data['vehicle_number'],
            'reference_number'  => $data['reference_number'] ?? null,
            'remarks'           => $data['remarks'] ?? null,
            'gross_weight'      => $data['gross_weight'] ?? 0,
            'tare_weight'       => $data['tare_weight'] ?? 0,
            'bag_type'          => $data['bag_type'] ?? null,
            'bag_count'         => $data['bag_count'] ?? 0,
            'created_by'        => $userId,
            'items'             => $items,
        ];
    }

    protected function prepareGrnPayloadFromGodown(array $data, int $userId): array
    {
        $purchaseOrderId = $data['purchase_order_id'] ?? null;
        $purchaseOrderItemId = null;
        $purchaseOrderSerial = null;

        if ($purchaseOrderId) {
            $po = PurchaseOrder::where('id', $purchaseOrderId)->first();
            if ($po) {
                $purchaseOrderSerial = $po->order_serial;
                
                $poDetail = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                    ->where('item_id', $data['item_id'])
                    ->first();
                if ($poDetail) {
                    $purchaseOrderItemId = $poDetail->id;
                }
            }
        }

        // Wrap the single item from Godown into GRN items array
        $items = [[
            'item_id'                => $data['item_id'],
            'quantity'               => $data['qty'] ?? 0,
            'party_quantity'         => $data['p_qty'] ?? 0,
            'rate'                   => $data['rate'] ?? 0,
            'bag_count'              => $data['bag_count'] ?? 0,
            'condition_id'           => $data['condition_id'] ?? null,
            'destination_id'         => $data['godown_id'] ?? null,
            'remarks'                => $data['remarks'] ?? null,
            'purchase_order_id'      => $purchaseOrderId,
            'purchase_order_item_id' => $purchaseOrderItemId ?? $purchaseOrderId,
            'purchase_order_serial'  => $purchaseOrderSerial,
        ]];

// dd($items);
        return [
            'uuid'             => (string) Str::uuid(),
            'account_id'       => $data['account_id'],
            'broker_id'        => $data['broker_id'] ?? null,
            'grn_date'         => $data['grn_date'] ?? $data['date_in'] ?? date('Y-m-d'),
            'grn_in_date'      => $data['date_in'] ?? null,
            'vehicle_number'   => $data['vehicle_number'],
            'reference_number' => $data['reference_number'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'gross_weight'     => $data['gross_weight'] ?? 0,
            'tare_weight'      => $data['tare_weight'] ?? 0,
            'bag_type'         => $data['bag_type'] ?? null,
            'bag_count'        => $data['bag_count'] ?? 0,
            'entry_from'       => Grn::ENTRY_FROM_GODOWN,
            'created_by'       => $userId,
            'items'            => $items,
        ];
    }

    protected function preparePayload(array $data, int $companyId, int $financialYearId, int $userId): array
    {
        // dd($data);
        $inOutStatus = $data['in_out_status'] ?? 'in' ?? 'out';

        return [
            "uuid"                    => $data["uuid"] ?? null,
            'company_id'              => $companyId,
            'financial_year_id'       => $financialYearId,
            'destination_id'          => $data['destination_id'] ?? null,
            'godown_unit_location_id' => $data['godown_unit_location_id'] ?? null,
            'party_destination_id'    => $data['party_destination_id'] ?? null,
            'grn_id'                  => $data['grn_id'] ?? null,
            'grn_serial'              => $data['grn_serial'] ?? null,
            'grn_date'                => $data['grn_date'] ?? null,
            'gst_type'                => $data['gst_type'] ?? null,
            'account_id'              => $data['account_id'] ?? null, 
            'broker_id'               => $data['broker_id'] ?? null,
            'item_id'                 => $data['item_id'] ?? null,
            'purchase_order_id'       => $inOutStatus === 'in' ? ($data['purchase_order_id'] ?? null) : null,
            'sale_order_id'           => $inOutStatus === 'out' ? ($data['sale_order_id'] ?? null) : null,
            'delivery_challan_id'     => $data['delivery_challan_id'] ?? null,
            'dc_serial'               => $data['dc_serial'] ?? null,
            'dc_date'                 => $data['dc_date'] ?? null,
            'reference_number'        => $data['reference_number'] ?? null,
            'vehicle_number'          => $data['vehicle_number'] ?? null,
            'transporter_id'          => $data['transporter_id'] ?? null,
            'lr_number'               => $data['lr_number'] ?? null,
            'challan_weight'          => $data['challan_weight'] ?? 0,
            'p_qty'                   => $data['p_qty'] ?? 0,
            'rate'                    => $data['rate'] ?? 0,
            'date_in'                 => $data['date_in'] ?? null,
            'date_out'                => $data['date_out'] ?? null,
            'time_in'                 => $data['time_in'] ?? null,
            'time_out'                => $data['time_out'] ?? null,
            'gross_weight'            => $data['gross_weight'] ?? 0,
            'tare_weight'             => $data['tare_weight'] ?? 0,
            'net_weight'              => abs($data['net_weight']) ?? 0,
            'bag_type'                => $data['bag_type'] ?? null,
            'bag_count'               => $data['bag_count'] ?? 0,
            'net_weight_wt_bag'       => abs($data['net_weight_wt_bag']) ?? 0,
            'total_quantity'          => $data['total_quantity'] ?? 0,
            'sub_total'               => $data['sub_total'] ?? 0,
            'in_out_status'           => $inOutStatus,
            'is_manual'               => $data['is_manual'] ?? false,
            'is_crossing'             => $data['is_crossing'] ?? false,
            'is_cycle'                => $data['is_cycle'] ?? 'open',
            'challan_bags'            => $data['challan_bags'] ?? 0,
            'created_by'              => $userId,
        ];
    }


    public function updateProductInSelf(GodownModule $godownModule, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($godownModule, $data, $companyId, $financialYearId) {

        if($godownModule->grn_id && $godownModule->grn){
            if ($godownModule->grn->grn_status === Grn::STATUS_BILLED) {
                throw new \Exception('This entry cannot be updated because the GRN is billed.');
            }
        }


            // First update GRN if it exists
            if ($godownModule->grn_id) {
                // Prepare GRN payload similar to createGrn
                $grnData = [
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'uuid'             => $godownModule->grn->uuid,
                    'grn_date'         => $godownModule->grn->grn_date,
                    'grn_in_date'      => $godownModule->grn->grn_in_date,
                    'grn_out_date'     => $godownModule->grn->grn_out_date,
                    'contract_number'  => $godownModule->grn->contract_number,
                    'broker_id'        => $godownModule->grn->broker_id,
                    'account_id'       => $data['party_id'],
                    // Preserve original reference_number — do not trigger uniqueness error
                    'reference_number' => $data['reference_number'],
                    'vehicle_number'   => $data['vehicle_number'],
                    'remarks'          => $data['remarks'],
                    'gross_weight'     => $godownModule->grn->gross_weight,
                    'tare_weight'      => $godownModule->grn->tare_weight,
                    'net_weight'       => $godownModule->grn->net_weight,
                    'net_weight_wt_bag'=> $godownModule->grn->net_weight_wt_bag,
                    'bag_count'        => (int)($godownModule->grn->bag_count),
                    'bag_type'         => $godownModule->grn->bag_type,
                    'entry_from'       => Grn::ENTRY_FROM_GODOWN,
                    'updated_by'       => current_user_id(),
                ];

                $qty = ($data['net_weight_wt_bag'] ?? 0) / 1000;
                $partyQty = ($data['challan_weight'] ?? 0) / 1000;
                $amount = $qty * ($data['rate'] ?? 0);

                $grnData['items'][] = [
                    'item_id'                   => $data['item_id'],
                    'quantity'                  => $qty,
                    'party_quantity'            => $partyQty,
                    'rate'                      => $data['rate'] ?? 0,
                    'inclusive_rate'            => $data['rate'] ?? 0,
                    'amount'                    => $amount,
                    'bag_count'                 => $data['bag_count'] ?? 0,
                    'purchase_order_id'         => $godownModule->grn->details->first()->purchase_order_id,
                    'purchase_order_item_id'    => $godownModule->grn->details->first()->purchase_order_item_id,
                    'net_amount'                => $amount,
                    'condition_id'              => $godownModule->grn->details->first()->condition_id,
                    'destination_id'            => $data['godown_id'],
                    'purchase_order_serial'     => $godownModule->grn->details->first()->purchase_order_serial,
                ];
                
                $this->grnService->updateGrn($grnData, $companyId, $financialYearId, $godownModule->grn_id);
            }

            // Then update GodownEntry
            $godownModule->update([
                'godown_id' => $data['godown_id'],
                'godown_unit_id' => $data['godown_unit_id'],
                // 'transporter_id' => $data['transporter_id'] ?? $godownModule->transporter_id,
                // Allow lr_number to be updated in self-correction mode
                // 'lr_number'  => $data['lr_number'] ?? $godownModule->lr_number,
                'challan_date' => Carbon::parse($data['challan_date'])->format('Y-m-d'),
                'in_date' => $godownModule->in_date,       // Never change date_in on update
                'out_date' => $godownModule->out_date,               // Always set current date when vehicle exits
                'in_time' => $godownModule->in_time,       // Never change time_in on update
                'out_time' => $godownModule->out_time,               // Always set current time when vehicle exits
                'challan_weight' => $data['challan_weight'],
                'challan_bags' => $data['challan_bags'],
                'is_manual' => $godownModule->is_manual ? 1 : ($data['is_manual'] ?? $godownModule->is_manual),
                // 'is_crossing' => $data['is_crossing'] ?? $godownModule->is_crossing,
                // 'is_cycle' => $data['is_cycle'] ?? $godownModule->is_cycle,
            ]);

            $godownModule->load('grn');
            $godownModule->grn_serial = $godownModule->grn->grn_serial ?? '';
// dd($godownModule->toArray());
            return $godownModule;
        });
    }

  public function updateProductOutSelf(GodownModule $godownModule, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($godownModule, $data, $companyId, $financialYearId) {
            if ($godownModule->grn_id && $godownModule->grn) {
                if ($godownModule->grn->grn_status === Grn::STATUS_CLOSE) {
                    throw new \Exception('This entry cannot be updated because the GRN is closed.');
                }
            }
            // First update Delivery Challan if it exists
            // if ($godownModule->delivery_challan_id) {
            if ($godownModule->grn_id) {
                // Prepare DC payload
                $deliveryChallanData = [
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'uuid'             => $godownModule->grn->uuid ?? $data['uuid'],
                    'grn_date'         => $data['challan_date'] ?? $data['dc_in_date'] ?? $data['date_in'] ?? date('Y-m-d'),
                    'grn_in_date'      => $data['dc_in_date'] ?? $data['date_in'] ?? null,
                    'grn_out_date'     => $data['date_out'] ?? null,
                    'date_in'          => $data['date_in'] ?? $data['dc_in_date'] ?? null,
                    'date_out'         => $data['date_out'] ?? null,
                    'contract_number'  => null,
                    'broker_id'        => null,
                    'account_id'       => $data['party_id'] ?? null,
                    // Preserve original reference_number
                    // 'reference_number' => $godownModule->deliveryChallan->reference_number ?? ($data['reference_number'] ?? null),
                    'reference_number' => $godownModule->deliveryChallan->reference_number ?? ($data['reference_number'] ?? null),
                    'vehicle_number'   => $data['vehicle_number'] ?? null,
                    'remarks'          => $data['remarks'] ?? null,
                    'gross_weight'     => $data['gross_weight'] ?? 0,
                    'tare_weight'      => $data['tare_weight'] ?? 0,
                    // 'net_weight'       => $this->deliveryChallanService->net_weight($data['gross_weight'] ?? 0, $data['tare_weight'] ?? 0),
                    'net_weight'       => $data['net_weight'] ?? 0,
                    'bag_count'        => (int)($data['bag_count'] ?? 0),
                    'bag_type'         => $data['bag_type'] ?? null,
                    'entry_from'       => Grn::ENTRY_FROM_GODOWN,
                ];
                $qty = ($data['net_weight_wt_bag'] ?? 0) / 1000;
                $partyQty = ($data['challan_weight'] ?? 0) / 1000;
                $amount = $qty * ($data['rate'] ?? 0);

                $deliveryChallanData['items'][] = [
                    'item_id'                   => $data['item_id'],
                    'destination_id'            => $data['godown_id'],
                    'quantity'                  => $qty,
                    'party_quantity'            => $partyQty,
                    'rate'                      => $data['rate'] ?? 0,
                    'inclusive_rate'            => $data['rate'] ?? 0,
                    'amount'                    => $amount,
                    'bag_count'                 => $data['bag_count'] ?? 0,
                    'sales_order_id'            => $data['so_no'] ?? null,
                    'sales_order_item_id'       => null,
                    'net_amount'                => $amount,
                    'condition_id'              => null,
                    'sales_order_serial'        => null,
                ];
                
                // $this->deliveryChallanService->updateDeliveryChallan($deliveryChallanData, $companyId, $financialYearId, $godownModule->delivery_challan_id);
                $this->grnService->updateGrn($deliveryChallanData, $companyId, $financialYearId, $godownModule->grn_id);
            }

            // Then update GodownEntry
            $godownModule->update([
                'godown_id' => $data['godown_id'],
                'godown_unit_id' => $data['godown_unit_id'],
                'dairy_po' => $data['dairy_po'] ?? null,
                'transporter_id' => $data['transporter_id'] ?? $godownModule->transporter_id,
                // Allow lr_number to be updated in self-correction mode
                'lr_number'  => $data['lr_number'] ?? $godownModule->lr_number,
                'party_destination_id'    => $data['party_destination_id'] ?? $godownModule->party_destination_id,
                'in_date' => $godownModule->in_date,       // Never change date_in on update
                'out_date' => $godownModule->out_date,               // Always set current date when vehicle exits
                'in_time' => $godownModule->in_time,       // Never change time_in on update
                'out_time' => $godownModule->out_time,               // Always set current time when vehicle exits
                'challan_weight' => $data['challan_weight'] ?? $godownModule->challan_weight,
                'challan_bags' => $data['challan_bags'] ?? $godownModule->challan_bags,
                'is_manual' => $godownModule->is_manual ? 1 : ($data['is_manual'] ?? $godownModule->is_manual),
                'is_crossing' => $data['is_crossing'] ?? $godownModule->is_crossing,
                'is_cycle' => $data['is_cycle'] ?? $godownModule->is_cycle,
            ]);

            // $godownModule->load('deliveryChallan');
            // $godownModule->dc_serial = $godownModule->deliveryChallan->challan_serial ?? '';
            $godownModule->load('grn');
            $godownModule->grn_serial = $godownModule->grn->grn_serial ?? '';
            return $godownModule;
        });
    }


    /**
     * Get next voucher number for Godown Module (synced with GRN Module)
     */
    public function getNextVoucherNumber(int $companyId, int $financialYearId): GrnDTO
    {
        return $this->grnService->getNextVoucherNumber($companyId, $financialYearId);
    }
    public function getNextDeliveryChallanVoucherNumber(int $companyId, int $financialYearId): DeliveryChallanDTO
    {
        return $this->deliveryChallanService->getNextVoucherNumber($companyId, $financialYearId);
    }
    public function getGrnBySerial(int $grnSerial, int $companyId, int $financialYearId)
    {
        return $this->godownRepo->getGrnBySerial($grnSerial, $companyId, $financialYearId);
    }

    public function getDeliveryChallanBySerial(int $grnSerial, int $companyId, int $financialYearId)
    {
        return $this->godownRepo->getDeliveryChallanBySerial($grnSerial, $companyId, $financialYearId);
    }
    
    public function getVehiclesForGodown(int $companyId, int $financialYearId, string $type = 'in')
    {
        return $this->godownRepo->getVehiclesForGodown($companyId, $financialYearId, $type);
    }

    public function getGrnByNumber(string $grnNumber, int $companyId, int $financialYearId)
    {
        return;
//  $this->godownRepo->getGrnByNumber($grnNumber, $companyId, $financialYearId);
    }

    /**
     * Delete a Godown Module record
     */
    public function delete($godown)
    {
        return $this->godownRepo->delete($godown);
    }

    /**
     * Get filtered Godown Module records
     */
    public function getGodowns(int $companyId, int $financialYearId, array $filters = [])
    {
        // return $this->godownRepo->getGodownData($companyId, $financialYearId, $filters);
        $permissions = userPermissions([
            'godown_module.delete',
            'self.update',

        ], true);
        $godownData = $this->godownRepo->getGodownData($companyId, $financialYearId, $filters);
        return compact('godownData', 'permissions');
    }

    //   public function getGodowns(int $companyId, int $financialYearId, array $filters = [])
    // {
    //     return $this->godownRepo->getGodownData($companyId, $financialYearId, $filters);
    // }

    // public function getTransporters(int $companyId, int $financialYearId, array $filters = []) : array
    // {
    //     return $this->godownRepo->getTransporterListData($companyId, $financialYearId, $filters);
    // }

    // public function getTransportersForExport(int $companyId, int $financialYearId, array $filters = []) : Collection
    // {
    //     return $this->godownRepo->getTransporterExportData($companyId, $financialYearId, $filters);
    // }

    public function getTransporters(int $companyId, int $financialYearId, array $filters = []) : array
    {
        return $this->godownRepo->getTransporterListData($companyId, $financialYearId, $filters);
    }

    public function getTransportersForExport(int $companyId, int $financialYearId, array $filters = []) : Collection
    {
        return $this->godownRepo->getTransporterExportData($companyId, $financialYearId, $filters);
    }

    /**
     * Check if LR Number is duplicated for the same account and transporter.
     */
    public function isDuplicateLrNumber(int $companyId, int $financialYearId, string $lrNumber, int $transporterId, ?int $id = null): bool
    {
        // Use strlen() instead of empty() because empty('0') === true in PHP,
        // which would skip the duplicate check when the user enters "0".
        // if (strlen(trim($lrNumber)) === 0) {
        //     return false;
        // }

        $query = GodownModule::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('lr_number', $lrNumber)
            ->where('transporter_id', $transporterId);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        return $query->exists();
    }

    private function moisture(array $data, GodownModule $godownModule, int $companyId, int $financialYearId)
    {
        if (isset($data['moisture']) && $data['moisture'] > 0) {
            $moistureRecord = Moisture::where('grn_id', $godownModule->grn_id)->first();
            
            if ($moistureRecord) {
                $moistureRecord->update([
                    'challan_weight' => $data['old_challan_weight'] ?? $moistureRecord->challan_weight,
                    'new_challan_weight' => $data['new_challan_weight'] ?? $moistureRecord->new_challan_weight,
                    'gross_weight' => $data['old_gross_weight'] ?? $moistureRecord->gross_weight,
                    'new_gross_weight' => $data['new_gross_weight'] ?? $moistureRecord->new_gross_weight,
                    'tare_weight' => $data['old_tare_weight'] ?? $moistureRecord->tare_weight,
                    'new_tare_weight' => $data['new_tare_weight'] ?? $moistureRecord->new_tare_weight,
                    'net_weight' => $data['old_net_weight'] ?? $moistureRecord->net_weight,
                    'new_net_weight' => $data['new_net_weight'] ?? $moistureRecord->new_net_weight,
                    'net_weight_wt_bag' => $data['old_net_weight_wt_bag'] ?? $moistureRecord->net_weight_wt_bag,
                    'new_net_weight_wt_bag' => $data['new_net_weight_wt_bag'] ?? $moistureRecord->new_net_weight_wt_bag,
                    'updated_by' => current_user_id(),
                ]);
            } else {
                Moisture::create([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'grn_id' => $godownModule->grn_id,
                    'godown_module_id' => $godownModule->id,
                    'challan_weight' => $data['old_challan_weight'] ?? 0,
                    'new_challan_weight' => $data['new_challan_weight'] ?? 0,
                    'gross_weight' => $data['old_gross_weight'] ?? 0,
                    'new_gross_weight' => $data['new_gross_weight'] ?? 0,
                    'tare_weight' => $data['old_tare_weight'] ?? 0,
                    'new_tare_weight' => $data['new_tare_weight'] ?? 0,
                    'net_weight' => $data['old_net_weight'] ?? 0,
                    'new_net_weight' => $data['new_net_weight'] ?? 0,
                    'net_weight_wt_bag' => $data['old_net_weight_wt_bag'] ?? 0,
                    'new_net_weight_wt_bag' => $data['new_net_weight_wt_bag'] ?? 0,
                    'created_by' => current_user_id(),
                ]);
            }
        }
    }

    /**
     * Check if LR Number is duplicated for the same account.
     */
    public function isDuplicateLrNo(int $companyId, int $financialYearId, string $lrNumber, int $transporterId, ?int $id = null): bool
    {
        // Use strlen() instead of empty() because empty('0') === true in PHP,
        // which would skip the duplicate check when the user enters "0".
        // However, 0 is allowed as an optional value on create, so we ignore it for duplicate checking.
        $lrNumberTrimmed = trim($lrNumber);
        if (strlen($lrNumberTrimmed) === 0 || $lrNumberTrimmed === '0') {
            return false;
        }

        $query = GodownModule::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('lr_number', $lrNumber)
            ->where('transporter_id', $transporterId)
            ->where('is_cycle', GodownModule::CYCLE_CLOSE);

        if ($id) {
            $query->where('id', '!=', $id);
        }

        return $query->exists();
    }

}
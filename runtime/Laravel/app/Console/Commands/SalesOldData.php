<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BillSundry;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\EInvoice;
use App\Models\EWayBill;
use App\Models\Grn;
use App\Models\ItemMapping;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseTypeMapping;
use App\Models\PurchaseInvoiceSundry;
use App\Models\Reference;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceSundry;
use App\Models\SalesOrder;
use App\Models\SaleTypeMapping;
use App\Models\StockVoucher;
use App\Models\StockVoucherTransaction;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class SalesOldData extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sales {company_id} {financial_year_id}';

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

        $purchaseType = SaleTypeMapping::where('company_id', $companyId)->pluck('new_sale_type_id', 'old_sale_type_id');

        $pBillItem = DB::connection('old_db')
            ->table('sales_bill_items')
            ->where('cc_id', $ccId[$companyId])
            ->get()
            ->groupBy('sbill_id')->toArray();


        $particular = DB::connection('old_db')
            ->table('sales_bill_particulars')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('account_id')
            ->get()
            ->groupBy('sales_voucher_number');

        $eInvoices = DB::connection('old_db')
            ->table('einvoice')
            ->where('cc_id', $ccId[$companyId])
            ->get()->keyBy('salesbill_id');
        

        $salesOrder = SalesOrder::with('details')->where('company_id', $companyId)->get()->keyBy('order_serial');

        $accounts = Account::pluck('gst_type', 'id');

        $sundries = BillSundry::where('company_id', $companyId)->get()->keyBy('code');

        $ledgerData = DB::connection('old_db')
            ->table('ledger_details')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('voucher_type', 5)
            ->orderBy('account_id')
            ->get()
            ->groupBy('voucher_id');

        DB::connection('old_db')
            ->table('sales_bills')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->chunk(200, function ($pbs) use (
                $companyId,
                $financialYearId,
                $itemMaster,
                $conditionMaster,
                $destinationMaster,
                $accountMaster,
                $brokerMaster,
                $type,
                $salesOrder,
                $pBillItem,
                $accounts,
                $purchaseType,
                $particular,
                $sundries,
                $ledgerData,
                $eInvoices

            ) {
                foreach ($pbs as $mainKey => $pb) {
                    $salesOrderId = null;
                    $salesOrderSerial = null;
                  

                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $pb->vendor_id && $am->account_type == $type[$pb->account_type]) {
                            $accountId = $am->new_account_id;
                            break;
                        }
                    }

                    $salesInv = SalesInvoice::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'invoice_serial'    => $pb->bill_no,
                        'invoice_number'    => $pb->bill_no,
                        'invoice_date'      => $pb->date,
                        'delivery_challan_number' => null,
                        'sales_order_id' => $salesOrderId,
                        'sales_order_serial' => $salesOrderSerial,
                        'grn_number'    => $pb->grn_no,
                        'reference_number' => $pb->bill_no,
                        'account_id' => $accountId,
                        'last_invoice_date' => $pb->last_inv_date,
                        'broker_id' => null,
                        
                        'vehicle_number' => $pb->vehical_no,
                        'sale_type_id'  => $purchaseType[$pb->inv_trans_type] ?? null, 
                        'delivery_date' => $pb->delivery_date,
                        'total_quantity' => $pb->total_qty,
                        'net_amount' => $pb->net_total,
                        'total_amount' => $pb->total_amount,
                        'taxable_amount' => $pb->total_amount,
                        
                        // 'ewaybill_number',
                        // 'invoice_status',
                        
                        // 'ewaybill_status',
                        'remarks' => $pb->sales_comment,
                        // 'payment_received_status',
                        'gst_type' => $accounts[$accountId],


                    ]);
                    
                    foreach ($pBillItem[$pb->bill_no] as $key => $pbi) {
                        
                        foreach ($salesOrder[$pbi->sales_order_number]->details as $sOrder) {
                            $salesOrderId = $salesOrder[$pbi->sales_order_number]->id;
                            $salesOrderSerial = $salesOrder[$pbi->sales_order_number]->order_serial;
                        }
                        
                        
                        SalesInvoiceItem::create([
                            'sales_invoice_id' => $salesInv->id,
                            'item_id' => $itemMaster[$pbi->product_id],
                            'quantity' => $pbi->qty,
                            'party_quantity' => $pbi->p_qty,
                            'rate' => $pbi->rate,
                            'inclusive_rate' => $pbi->incltax_rate,
                            'tax_amount' => 0,
                            'amount' => $pbi->qty * $pbi->rate,
                            'net_amount' => $pbi->amount,
                            'cgst_rate' => 0,
                            'sgst_rate' => 0,
                            'igst_rate' => 0,
                            'bag_count' => $pbi->bags == null ? 0 : $pbi->bags,
                            'cgst_amount' => 0,
                            'sgst_amount' => 0,
                            'igst_amount' => 0,
                            'taxable_amount' => 0,
                            'condition_id'          => $pbi->condition_id ? ($conditionMaster[$pbi->condition_id] ?? null) : null,
                            'destination_id'        => $pbi->destination_id ?  ($destinationMaster[$pbi->destination_id] ?? null) : null,
                        ]);
                    }

                    // dd($sundries->toArray());

                    // Particular

                    $globalAccount = [
                            1001 => 1001,
                            1002 => 1009,
                            1003 => 1002,
                            1004 => 1004,
                            1005 => 1003,
                            1006 => 1005,
                            1007 => 1006,
                            1009 => 1007,
                            1010 => 1008,
                            1012 => 1012
                    ];
                    $data = [];
                    // dd($particular[$pb->bill_no]);
                    foreach ($particular[$pb->bill_no] as $pkey => $particularRow) {
                        if ($particularRow->account_id == 1011) continue;
                        if ($particularRow->account_id == 1008) continue;
                        if ($particularRow->account_id == 998) continue;

                        $particularRow->account_id = $globalAccount[$particularRow->account_id];

                        if ($particularRow->account_id == 1012) {
                            if ($particularRow->particular_value < 0) {
                                $particularRow->account_id = 1011;
                            } else {
                                $particularRow->account_id = 1010;
                            }
                        }

                        $sundry = $sundries[$particularRow->account_id] ?? null;

                        if (!$sundry) {
                            continue;
                        }

                        $data[] = [
                            'sales_invoice_id'        => $salesInv->id,
                            'sundry_id'               => $sundry->id,
                            'code'                    => $sundry->code,
                            'name'                    => $sundry->name,
                            'bill_sundry_type'        => $sundry->bill_sundry_type,
                            'calculation_type'        => $sundry->calculation_type,
                            'apply_on'                => $sundry->apply_on,
                            'bill_sundry_modal_dr_id' => null,
                            'bill_sundry_modal_cr_id' => null,
                            'base_amount'             => 0,
                            'rate_percent'            => $particularRow->percentage ?? 0,
                            'value'                   => abs($particularRow->particular_value) ?? 0,
                            'amount'                  => $particularRow->particular_value ?? 0,
                            'sort_order'              => substr($sundry->code, -2),
                            'affect_net_total'        => true,
                        ];
                    }
                    // dd($data);
                    SalesInvoiceSundry::insert($data);

                    $voucher = Voucher::create([
                        'uuid'              => $salesInv->uuid,
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => 5,
                        'voucher_serial'    => $salesInv->invoice_serial,
                        'voucher_number'    => $salesInv->invoice_number,
                        'voucher_date'      => $salesInv->invoice_date,
                        'reference_number'  => $salesInv->reference_number,
                        'source_type'       => SourceType::SALES,
                        'source_id'         => $salesInv->id,
                        'narration'         => $salesInv->remarks,
                    ]);

                    $salesInv->update(['voucher_id' => $voucher->id, 'sales_order_id' => $salesOrderId,'sales_order_serial' => $salesOrderSerial]);


                    $voucherLine = [];
                    foreach($ledgerData[$pb->bill_no]  as $line => $ldData){
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

                        if($ldData->drcr_status == 1){
                            $credit = $ldData->amount;
                        }
                        if($ldData->drcr_status == 2){
                            $debit = $ldData->amount;
                        }

                        $voucherLine [] = [
                            'voucher_id' => $voucher->id,
                            'account_id' => $ldAccountId,
                            'debit'  => $debit,
                            'credit' => $credit,
                            'narration' => null,
                            'is_party_account' => $ldAccountId  == $salesInv->account_id,
                            'line_no' => $line+1,
                            'against_account_id' => $oppositeAccount
                        ];

                    }
                    
                    VoucherTransaction::insert($voucherLine);

                    Reference::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'account_id' => $salesInv->account_id,
                        'reference_number' => $salesInv->reference_number,
                        'reference_date' => $salesInv->invoice_date,
                        'file_number'=> null,
                        'reference_type' => Reference::NewReference,
                        'amount' => $salesInv->net_amount,
                        'settled_amount' => 0,
                        'pending_amount' => $salesInv->net_amount,
                        'voucher_id' => $voucher->id,
                        'source_type'       => SourceType::SALES,
                        'source_id'         => $salesInv->id,
                        'direction'          => 'debit',
                    ]);

                    $stock = StockVoucher::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_date'      => $salesInv->invoice_date,
                        'voucher_id'        => $voucher->id,
                        'voucher_type_id'   => VoucherType::SALE_INVOICE,
                        'voucher_number'    => $voucher->voucher_number,
                        'voucher_serial'    => $voucher->voucher_serial,
                        'reference_number'  => $salesInv->reference_number,
                    ]);

                    $itemDet = SalesInvoiceItem::where('sales_invoice_id', $salesInv->id)->get();

                    foreach ($itemDet as $key => $itd) {
                        StockVoucherTransaction::create([
                            'stock_voucher_id' => $stock->id,
                            'item_id' => $itd->item_id,
                            'in_qty' => 0,
                            'out_qty' => $itd->quantity,
                            'rate' => $itd->rate,
                            'amount' => $itd->amount,
                        ]);
                    }

                    $ewayInv = isset($eInvoices[$pb->bill_no]) && $eInvoices[$pb->bill_no] ?$eInvoices[$pb->bill_no] : null;
                    // dd([
                    //     'company_id' => $companyId,
                    //     'sales_invoice_id' => $salesInv->id,
                    //     'e_invoice_id',
                    //     'ewb_no' => $ewayInv->ewaybill_number,
                    //     'ewb_date' => $ewayInv->ewaybill_date,
                    //     'valid_upto' => $ewayInv->ewaybill_valid_till_date,
                    //     // 'trans_mode',
                    //     // 'transporter_name',
                    //     // 'transporter_id',
                    //     // 'trans_doc_no',
                    //     // 'trans_doc_date',
                    //     'vehicle_no' => $salesInv->vehicle_no,
                    //     // 'vehicle_type',
                    //     // 'status',
                    //     // 'cancel_reason_code',
                    //     // 'cancel_remark',
                    //     // 'cancelled_at',
                    //     'environment' => 'production',
                    //     // 'created_by',
                    // ]);
                    if(!$ewayInv){
                        continue;
                    }

                    EWayBill::create([
                       'company_id' => $companyId,
                        'sales_invoice_id' => $salesInv->id,
                        'ewb_no' => $ewayInv->ewaybill_number,
                        'ewb_date' => $ewayInv->ewaybill_date,
                        'valid_upto' => $ewayInv->ewaybill_valid_till_date,
                        // 'trans_mode',
                        'transporter_name' => 'SELF',
                        // 'transporter_id',
                        // 'trans_doc_no',
                        // 'trans_doc_date',
                        'vehicle_no' => $salesInv->vehicle_no,
                        'vehicle_type' => "R",
                        // 'status',
                        // 'cancel_reason_code',
                        // 'cancel_remark',
                        // 'cancelled_at',
                        'environment' => 'production',
                        // 'created_by',
                    ]);

                    EInvoice::create([
                          'company_id' => $companyId,
                            'sales_invoice_id' => $salesInv->id,
                            'irn' => $ewayInv->irn,
                            'ack_no' => $ewayInv->ack_no,
                            'ack_dt' => $ewayInv->ack_date,
                            'doc_type' => 'INV',
                            'doc_no' => $ewayInv->dock_no,
                            'doc_date' => $salesInv->invoice_date,
                            'signed_qr_code' => $ewayInv->signed_QR_code,
                            
                            
                            'environment' => 'production',
                            
                    ]);

                }
            });
    }

}

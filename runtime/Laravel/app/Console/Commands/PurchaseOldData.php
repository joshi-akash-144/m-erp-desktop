<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BillSundry;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\Grn;
use App\Models\ItemMapping;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseTypeMapping;
use App\Models\PurchaseInvoiceSundry;
use App\Models\Reference;
use App\Models\StockVoucher;
use App\Models\StockVoucherTransaction;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class PurchaseOldData extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purchase {company_id} {financial_year_id}';

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

        $purchaseType = PurchaseTypeMapping::where('company_id', $companyId)->pluck('new_purchase_type_id', 'old_purchase_type_id');

        $pBillItem = DB::connection('old_db')
            ->table('purchase_bill_items')
            ->where('cc_id', $ccId[$companyId])
            ->get()
            ->groupBy('pbill_id')->toArray();


        $particular = DB::connection('old_db')
            ->table('purchase_bill_particulars')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('account_id')
            ->get()
            ->groupBy('purchase_voucher_number');


        $grns = Grn::where('company_id', $companyId)->get(['id', 'grn_serial', 'grn_number'])->keyBy('grn_serial');

        $accounts = Account::pluck('gst_type', 'id');

        $sundries = BillSundry::where('company_id', $companyId)->get()->keyBy('code');

        $ledgerData = DB::connection('old_db')
            ->table('ledger_details')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('voucher_type', 4)
            ->orderBy('account_id')
            ->get()
            ->groupBy('voucher_id');

        DB::connection('old_db')
            ->table('purchase_bills')
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
                $grns,
                $pBillItem,
                $accounts,
                $purchaseType,
                $particular,
                $sundries,
                $ledgerData

            ) {
                foreach ($pbs as $key => $pb) {

                    $grn = $pb->grn_no ? $grns[$pb->grn_no] : null;

                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $pb->supplier_id && $am->account_type == $type[$pb->account_type]) {
                            $accountId = $am->new_account_id;
                            break;
                        }
                    }
                    if(!$accountId){
                        dd($pb);
                    }

                    $purchaseInv = PurchaseInvoice::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'invoice_serial'    => $pb->vchr_no,
                        'invoice_number'    => $pb->vchr_no,
                        'due_date'          => null,
                        'invoice_date'      => $pb->date,
                        'show_date'         => $pb->show_date,
                        'party_bill_date'   => $pb->bill_date,
                        'grn_id'            => $grn ? $grn->id : null,
                        'grn_number' => $grn ? $grn->grn_number : null,
                        'grn_serial' => $grn ? $grn->grn_number : null,
                        'file_number' => $pb->file_no,
                        'sales_invoice_serial' => $pb->sales_inv_no,
                        'account_id' => $accountId,
                        'gst_type' => $accounts[$accountId],
                        'reference_number' => $pb->other_ref_no,
                        'broker_id' => $brokerMaster[$pb->broker_id] ?? null,
                        'vehicle_number' => $pb->vehical_no,
                        'remarks' => $pb->pur_comment,
                        'net_amount' => $pb->net_total,
                        'purchase_type_id' => $purchaseType[$pb->inv_trans_type] ?? null,
                        'voucher_id' => null,
                        'grand_total' => $pb->net_total,
                        'tax_amount' => 0,
                        'taxable_amount' => $pb->total_amount,
                        'total_quantity' => $pb->total_qty,
                        'rebate_from_analysis' => $pb->is_rebate

                    ]);

                    foreach ($pBillItem[$pb->vchr_no] as $key => $pbi) {
                        PurchaseInvoiceItem::create([
                            'purchase_invoice_id' => $purchaseInv->id,
                            'item_id' => $itemMaster[$pbi->product_id],
                            'quantity' => $pbi->qty,
                            'party_quantity' => $pbi->p_qty,
                            'rate' => $pbi->rate,
                            'inclusive_rate' => $pbi->incltax_rate,
                            'tax_amount' => 0,
                            'amount' => $pbi->amount,
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
                            'purchase_order_serial' => $pbi->order_no
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
                    // dd($particular[$pb->vchr_no]);
                    foreach ($particular[$pb->vchr_no] as $pkey => $particularRow) {
                        $newAccountId = null;
                        foreach ($accountMaster as $acm) {
                        if ($acm->old_account_id == $particularRow->account_id && $acm->account_type == 'account') {
                            $newAccountId = $acm->new_account_id;
                            break;
                            }
                        }
                        if ($particularRow->account_id == 1011) continue;
                        if ($particularRow->account_id == 1008) continue;

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
                            'purchase_invoice_id' => $purchaseInv->id,
                            'sundry_id'               => $sundry->id,
                            'code'                    => $sundry->code,
                            'name'                    => $sundry->name,
                            'account_id'              => $newAccountId,
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
                    PurchaseInvoiceSundry::insert($data);

                    $voucher = Voucher::create([
                        'uuid'              => $purchaseInv->uuid,
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => 4,
                        'voucher_serial'    => $pb->vchr_no,
                        'voucher_number'    => $pb->vchr_no,
                        'voucher_date'      => $purchaseInv->invoice_date,
                        'reference_number'  => $purchaseInv->reference_number,
                        'source_type'       => SourceType::PURCHASE,
                        'source_id'         => $purchaseInv->id,
                        'narration'         => $purchaseInv->remarks,
                    ]);

                    $purchaseInv->update(['voucher_id' => $voucher->id]);

                    $voucherLine = [];
                    foreach($ledgerData[$pb->vchr_no]  as $line => $ldData){
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
                            'is_party_account' => $ldAccountId  == $purchaseInv->account_id,
                            'line_no' => $line+1,
                            'against_account_id' => $oppositeAccount
                        ];

                    }
                    
                    VoucherTransaction::insert($voucherLine);

                    Reference::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'account_id' => $purchaseInv->account_id,
                        'reference_number' => $purchaseInv->reference_number,
                        'reference_date' => $purchaseInv->invoice_date,
                        'file_number'=> $purchaseInv->file_number,
                        'reference_type' => Reference::NewReference,
                        'amount' => $purchaseInv->net_amount,
                        'settled_amount' => 0,
                        'pending_amount' => $purchaseInv->net_amount,
                        'voucher_id' => $voucher->id,
                        'source_type'       => SourceType::PURCHASE,
                        'source_id'         => $purchaseInv->id,
                        'direction'          => 'credit',
                    ]);

                    $stock = StockVoucher::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_date'      => $purchaseInv->invoice_date,
                        'voucher_id'        => $voucher->id,
                        'voucher_type_id'   => VoucherType::PURCHASE_INVOICE,
                        'voucher_number'    => $voucher->voucher_number,
                        'voucher_serial'    => $voucher->voucher_serial,
                        'reference_number'  => $purchaseInv->reference_number,
                    ]);

                    $itemDet = PurchaseInvoiceItem::where('purchase_invoice_id', $purchaseInv->id)->get();

                    foreach ($itemDet as $key => $itd) {
                        StockVoucherTransaction::create([
                            'stock_voucher_id' => $stock->id,
                            'item_id' => $itd->item_id,
                            'in_qty' => $itd->quantity,
                            'out_qty' => 0,
                            'rate' => $itd->rate,
                            'amount' => $itd->amount,
                        ]);
                    }
                }
            });
    }

}

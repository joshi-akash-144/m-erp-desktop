<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BillSundry;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CreditNoteSundry;
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
use App\Models\SaleType;
use App\Models\SaleTypeMapping;
use App\Models\StockVoucher;
use App\Models\StockVoucherTransaction;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class CreditNoteOldData extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:credit-note {company_id} {financial_year_id}';

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

        $creditNoteItems = DB::connection('old_db')
            ->table('credit_note_items')
            ->where('cc_id', $ccId[$companyId])
            ->get()
            ->groupBy('crnt_vch_no')->toArray();

        $creditNoteDetails = DB::connection('old_db')
            ->table('credit_note_details')
            ->where('cc_id', $ccId[$companyId])
            ->get()
            ->groupBy('crnt_vch_no')->toArray();


        // $particular = DB::connection('old_db')
        //     ->table('sales_bill_particulars')
        //     ->where('cc_id', $ccId[$companyId])
        //     ->whereNull('deleted_at')
        //     ->orderBy('account_id')
        //     ->get()
        //     ->groupBy('sales_voucher_number');

        // $eInvoices = DB::connection('old_db')
        //     ->table('einvoice')
        //     ->where('cc_id', $ccId[$companyId])
        //     ->get()->keyBy('salesbill_id');
        

        $salesVoucherNumber = SalesInvoice::where('company_id', $companyId)->pluck('id', 'invoice_serial');

        $accounts = Account::pluck('gst_type', 'id');

        $sundries = BillSundry::where('company_id', $companyId)->get()->keyBy('code');

        $ledgerData = DB::connection('old_db')
            ->table('ledger_details')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('voucher_type', 10)
            ->orderBy('account_id')
            ->get()
            ->groupBy('voucher_id');

        DB::connection('old_db')
            ->table('credit_notes')
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
                
                $creditNoteItems,
                $accounts,
                $purchaseType,
                
                $sundries,
                $ledgerData,
                $salesVoucherNumber,
                $creditNoteDetails

            ) {
                foreach ($pbs as $mainKey => $pb) {
                    
                    
                    $salesOrderSerial = null;
                  

                    $accountId = null;
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $pb->account_id && $am->account_type == $type[$pb->account_type]) {
                            $accountId = $am->new_account_id;
                            break;
                        }
                    }
              

                    $credit_note = CreditNote::create([
                         'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'credit_note_serial' => $pb->voucher_no,
                        'credit_note_number' => $pb->voucher_no,
                        'credit_note_date'  => $pb->date,
                        'sales_invoice_id'  => $salesVoucherNumber[$pb->sales_voucher] ?? null,
                        'sales_invoice_serial' => $pb->sales_voucher,
                        'account_id'    => $accountId,
                        'sale_type_id' => $purchaseType[$pb->inv_type],
                        'gst_type'    => 'local',
                        'reference_number' => $pb->ref_no.'-CrNt',
                        'total_quantity'  => 0,
                        'taxable_amount' => 0,
                        'tax_amount' => 0,
                        'net_amount' => $pb->net_total,
                        'grand_total' => 0,
                        'remarks' => $pb->narration,

                    ]);


                    // TODO: migrate credit note items
                    foreach ($creditNoteItems[$pb->voucher_no] ?? [] as $key => $pbi) {
                        CreditNoteItem::create([
                            'credit_note_id'  => $credit_note->id,
                            // 'credit_note_serial' => $credit_note->credit_note_serial,
                            'item_id'         => $itemMaster[$pbi->product_id] ?? null,
                            'quantity'        => $pbi->qty,
                            'rate'            => $pbi->price,
                            'inclusive_rate'  => 0,
                            'amount'          => $pbi->amount,
                            'net_amount'      => 0,
                            'tax_amount'      => 0,
                            'taxable_amount'  => $pbi->amount,
                            'cgst_rate'       => 0,
                            'sgst_rate'       => 0,
                            'igst_rate'       => 0,
                            'cgst_amount'     => 0,
                            'sgst_amount'     => 0,
                            'igst_amount'     => 0,
                            'condition_id'    => null,
                            'destination_id'  => null,
                            'bag_count'       => 0,
                        ]);
                    }

                      $voucher = Voucher::create([
                        'uuid'              => $credit_note->uuid,
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => 10,
                        'voucher_serial'    => $pb->voucher_no,
                        'voucher_number'    => $pb->voucher_no,
                        'voucher_date'      => $credit_note->credit_note_date,
                        'reference_number'  => $credit_note->reference_number,
                        'source_type'       => SourceType::SALES_RETURN,
                        'source_id'         => $credit_note->id,
                        'narration'         => $credit_note->remarks,
                    ]);

                    $credit_note->update(['voucher_id' => $voucher->id]);

                    $voucherLine = [];
                    
                    
                    foreach($ledgerData[$pb->voucher_no]  as $line => $ldData){
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
                            'is_party_account' => $ldAccountId  == $credit_note->account_id,
                            'line_no' => $line+1,
                            'against_account_id' => $oppositeAccount
                        ];

                    }
                    
                    VoucherTransaction::insert($voucherLine);

                    // $ledgerData

                    // TODO: migrate bill sundries
                    $globalAccount = [
                            1003 => 1002, 
                            1005 => 1003,
                            1004 => 1004,
                    ];
                   $saleType = SaleType::where('id', $purchaseType[$pb->inv_type])->first();

                   $newArrayForBillSundry = [];
                    foreach ($creditNoteDetails[$pb->voucher_no] as $key => $row) {
                        if($row->account_id == 1003){
                            $newArrayForBillSundry [] = [
                                'code'  => 1002,
                                'amount' => $row->debit

                            ];
                        }
                        if($row->account_id == 1005){
                            $newArrayForBillSundry [] = [
                                'code'  => 1003,
                                'amount' => $row->debit

                            ];
                        }

                    }
                    $newArrayForBillSundry [] = [
                                'code'  => 1004,
                                'amount' => 0

                            ];
                    $data = [];
                  
                    foreach ($newArrayForBillSundry as $key => $nbs) {
                        $sundry = $sundries[$nbs['code']] ?? null;
                        if (!$sundry) {
                            continue;
                        }

                                 $percentage = 0;
                        if($sundry->code == 1002){
                            $percentage = $saleType->cgst;
                        }
                        if($sundry->code == 1003){
                            $percentage = $saleType->sgst;
                        }
                        if($sundry->code == 1004){
                            $percentage = $saleType->igst;
                        }

                          $data[] = [
                            'credit_note_id'        => $credit_note->id,
                            'sundry_id'               => $sundry->id,
                            'code'                    => $sundry->code,
                            'name'                    => $sundry->name,
                            'bill_sundry_type'        => $sundry->bill_sundry_type,
                            'calculation_type'        => $sundry->calculation_type,
                            'apply_on'                => $sundry->apply_on,
                            'bill_sundry_modal_dr_id' => null,
                            'bill_sundry_modal_cr_id' => null,
                            'base_amount'             => 0,
                            'rate_percent'            =>  $percentage ?? 0,
                            'value'                   => $nbs['amount'] ?? 0,
                            'amount'                  => -$nbs['amount'] ?? 0,
                            'sort_order'              => substr($sundry->code, -2),
                            'affect_net_total'        => true,
                        ];
                    }
                    
                    CreditNoteSundry::insert($data);
                        
                    Reference::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'account_id' => $credit_note->account_id,
                        'reference_number' => $credit_note->reference_number,
                        'reference_date' => $credit_note->invoice_date,
                        'file_number'=> null,
                        'reference_type' => Reference::NewReference,
                        'amount' => $credit_note->net_amount,
                        'settled_amount' => 0,
                        'pending_amount' => $credit_note->net_amount,
                        'voucher_id' => $voucher->id,
                        'source_type'       => SourceType::SALES_RETURN,
                        'source_id'         => $credit_note->id,
                        'direction'          => 'credit',
                    ]);

                    $stock = StockVoucher::create([
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_date'      => $credit_note->credit_note_date,
                        'voucher_id'        => $voucher->id,
                        'voucher_type_id'   => VoucherType::SALES_RETURN,
                        'voucher_number'    => $voucher->voucher_number,
                        'voucher_serial'    => $voucher->voucher_serial,
                        'reference_number'  => $credit_note->reference_number,
                    ]);

                    $itemDet = CreditNoteItem::where('credit_note_id', $credit_note->id)->get();

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

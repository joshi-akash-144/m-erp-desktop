<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
use App\Models\GstEntry;
use App\Models\Item;
use App\Models\ItemMapping;
use App\Models\SalesOrder as ModelSaleOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrderItem;
use App\Models\TdsEntry;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class TdsOldData extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tds {company_id} {financial_year_id}';

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

        $voucherWiseSerial = Voucher::where('company_id', $companyId)->get(['voucher_type_id','id','source_type','source_id','voucher_serial']);

        $vWs = [];

        foreach ($voucherWiseSerial as $key => $vWS) {
            $key = $vWS->voucher_type_id.'-'.$vWS->voucher_serial;

            $vWs [$key] = [
                'id' => $vWS->id,
                'source_type' => $vWS->source_type,
                'source_id' => $vWS->source_id,
                'voucher_type_id' => $vWS->voucher_type_id
            ];
        }
        
        // $accounts = Account::where('company_id', $companyId)->pluck(['name','id']);
        $accounts = Account::with('taxDetail')
        ->where('company_id', $companyId)
        ->get()
        ->mapWithKeys(function ($account) {
            return [
                $account->id => [
                    'name' => $account->name,
                    'pan'  => $account->taxDetail?->pan,
                ]
            ];
        });

        $items = Item::where('company_id', $companyId)->get()->keyBy('id');


        DB::connection('old_db')
            ->table('tds_details')
            ->where('cc_id', $ccId[$companyId])
            // ->where('vchr_type','!=', 10)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($oldGst) use (
                $companyId,
                $financialYearId,
                $itemMaster,
                $conditionMaster,
                $destinationMaster,
                $accountMaster,
                $brokerMaster,
                $type,
                $accounts,
                $vWs,
                $items
            ) {
                foreach ($oldGst as $entry) {

                           $accountId = null;
                           
                    foreach ($accountMaster as $key => $am) {
                        if ($am->old_account_id == $entry->account_id && $am->account_type == $type[$entry->account_type]) {
                            $accountId = $am->new_account_id;
                            break;
                        }
                    }
                    $documentType = [
                        4 => 1,
                        5 => 1,
                        10 => 2
                    ];
                    $supplyType = [
                        'B2B' => 1,
                        'B2C' => 2,
                    ];
                    if(!$entry->voucher_type) continue;
                    $voucherSerial = $entry->voucher_type .'-'.$entry->vchr_no;

                    TdsEntry::create([
                        'uuid'                      => Str::uuid(),
                        'company_id'                => $companyId,
                        'financial_year_id'         => $financialYearId,
                        'voucher_id'                => $vWs[$voucherSerial]['id'],
                        'voucher_transaction_id'    => null,
                        'account_id'                => $accountId,
                        'voucher_type_id'           => $entry->voucher_type,
                        'tds_category_id'           => null,
                        'reference_no'              => $entry->ref_no,
                        'deductee_name'             => $accounts->get($accountId)['name'],
                        'pan_no'                    => $accounts->get($accountId)['pan'],
                        'payment_amount'            => $entry->taxable_value,
                        'payment_date'              => $entry->date,
                        'tds_rate'                  => $entry->tds_per,
                        'tds_amount'                => abs($entry->tds),
                        'total_deducted'            => abs($entry->tds),
                        'tax_deducted_on'           => $entry->date,
                        'section_code'              => null,
                        

                    ]);
                }
            });
    }
}

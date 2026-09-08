<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\Destination;
use App\Models\DestinationMapping;
use App\Models\Freight;
use App\Models\FreightItem;
use App\Models\Godown;
use App\Models\GodownMapping;
use App\Models\GodownModule;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\ItemMapping;
use App\Models\JournalVoucher;
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\PurchaseOrder;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\TransportJournalMapping;
use App\Models\TransportParty;
use App\Models\TransportPartyMapping;
use App\Models\VehicleIncome;
use App\Models\VehicleMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class TransportFreightData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:t-freight {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receipt Data Insert';

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



        $type = [3 => 'account', 1 => 'supplier', 2 => 'customer'];

        // Pre-fetch all mappings before the loop to avoid N+1 queries
        // $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        // $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        // $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->pluck('new_account_id', 'old_account_id');
        // $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        // $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        // $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');

        // current company's creditor and debtor accounts and its child groups
        // $accounts = Account::where('company_id', $companyId)->orderBy('id')->pluck('account_group_id', 'id');


        // $group = AccountGroup::where('company_id', $companyId)->pluck('is_party_group', 'id');


        // $isPartyGroupIds = [];

        // foreach ($accounts as $accountId => $groupId) {
        //     $isPartyGroupIds[$accountId] = $group[$groupId] ?? 0;
        // }







        // $bankGroupIds = AccountGroup::where('company_id', $companyId)
        //     ->where('code', 130)
        //     ->pluck('id')
        //     ->toArray();

        // $bankIds = Account::where('company_id', $companyId)
        //     ->whereIn('account_group_id', $bankGroupIds)
        //     ->pluck('id')
        //     ->toArray();

        $destinationMapping = DestinationMapping::pluck('new_destination_id', 'old_destination_id');

        $partyDestination = TransportPartyMapping::get();
        $vehicleMapping = VehicleMapping::pluck('new_vehicle_id', 'old_vehicle_id');
        $itemMapping = ItemMapping::pluck('new_item_id', 'old_item_id');

        $accounts = AccountMapping::pluck('new_account_id', 'old_account_id');

        $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');
        $financialYearName = 'FY 2026-27';
        $year = substr(str_replace(['-', ' ', 'FY'], '', $financialYearName), -4);

        $mCorporation = DB::connection('old_db')
            ->table('sales_freight_transports')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')->where('company_id', 1)
            ->get();



        DB::connection('old_db')
            ->table('sales_freight_transports')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')

            ->orderBy('id')
            ->chunk(200, function ($freight) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $destinationMapping,
                $partyDestination,
                $vehicleMapping,
                $itemMapping,
                $accounts,
                $freightIncomeId,
                $year
            ) {

                $data = [];
                foreach ($freight as $key => $ft) {


                    $accountType = $ft->account_type;

                    $toDestination = null;

                    $billTo = $accounts[$ft->billing_party_id];
                    $billNo = $ft->bill_no;
                    $invDate = $ft->bill_date;
                    $grn = $ft->grn_no;
                    $lr = $ft->lr_no;
                    $vehicleId = $vehicleMapping[$ft->vehicle_id];
                    $itemId = $itemMapping[$ft->product_id];
                    $consignorId = null;
                    $consigneeId = null;
                    $fromDesId = null;
                    $toDestId = null;
                    $prefix = $ft->prefix;
                    $amount = $ft->freight;
                    $netWeight = $ft->net_weight;
                    $bagType = $ft->bag_type;
                    $freightRate = $ft->freight_rate;
                    $bagCount = $ft->dairybag;






                    if ($accountType == 2) {

                        // $consignorId = $partyDestination[$ft->billing_party_id];
                        $fromDesId = $destinationMapping[$ft->from_destination] ?? null;



                        $consigneeId = null;
                        foreach ($partyDestination  as $key => $ptd) {
                            if ($ptd->old_id == $ft->to_destination && $ptd->account_type == 2) {
                                $consigneeId = $ptd->new_id;
                            }
                            if ($ptd->old_id == $ft->billing_party_id) {
                                $consignorId = $ptd->new_id;
                            }
                        }

                        $data[] = [
                            'billTo'  =>   $billTo,
                            'billNo'  =>   $billNo,
                            'invDate'  =>   $invDate,
                            'lr'  =>   $lr,
                            'grn'  =>   $grn,
                            'vehicleId'  =>   $vehicleId,
                            'itemId'  =>   $itemId,
                            'prefix'  =>   $prefix,
                            'consignorId' => $consignorId,
                            'consigneeId' => $consigneeId,
                            'from' => $fromDesId,
                            'toDestId' => $toDestId,
                            'netWeight' => $netWeight,
                            'bagType' => $bagType,
                            'freightRate' => $freightRate,
                            'freightRate' => $freightRate,
                            'amount' => $amount,
                            'bag_count' => $bagCount,

                        ];
                    }

                    if ($accountType == 1) {

                        if ($accountType == 1 && $ft->to_destination == 0) {
                            $fromDesId =  null;
                            $toDestId = $destinationMapping[$ft->from_destination] ?? null;


                            foreach ($partyDestination  as $key => $ptd) {
                                if ($ptd->old_id == $ft->account_id && $ptd->account_type == 1) {
                                    $consignorId = $ptd->new_id;
                                }
                                if ($ptd->old_id == $ft->billing_party_id) {

                                    $consigneeId = $ptd->new_id;
                                }
                            }

                            $data[] = [
                                'billTo'  =>   $billTo,
                                'billNo'  =>   $billNo,
                                'invDate'  =>   $invDate,
                                'lr'  =>   $lr,
                                'grn'  =>   $grn,
                                'vehicleId'  =>   $vehicleId,
                                'itemId'  =>   $itemId,
                                'prefix'  =>   $prefix,
                                'consignorId' => $consignorId,
                                'consigneeId' => $consigneeId,
                                'from' => $fromDesId,
                                'toDestId' => $toDestId,
                                'netWeight' => $netWeight,
                                'bagType' => $bagType,
                                'freightRate' => $freightRate,
                                'amount' => $amount,
                                'bag_count' => $bagCount,

                            ];


                        }

                        if ($accountType == 1 && $ft->to_destination != 0) {

                            $fromDesId =  null;
                            $toDestId = $destinationMapping[$ft->from_destination] ?? null;

                            foreach ($partyDestination  as $key => $ptd) {
                                if ($ptd->old_id == $ft->to_destination && $ptd->account_type == 2) {
                                    $consigneeId = $ptd->new_id;
                                }
                                if ($ptd->old_id == $ft->account_id && $ptd->account_type == 1) {
                                    $consignorId = $ptd->new_id;
                                }
                            }

                            $data[] = [
                                'billTo'  =>   $billTo,
                                'billNo'  =>   $billNo,
                                'invDate'  =>   $invDate,
                                'lr'  =>   $lr,
                                'grn'  =>   $grn,
                                'vehicleId'  =>   $vehicleId,
                                'itemId'  =>   $itemId,
                                'prefix'  =>   $prefix,
                                'consignorId' => $consignorId,
                                'consigneeId' => $consigneeId,
                                'from' => $fromDesId,
                                'toDestId' => $toDestId,
                                'netWeight' => $netWeight,
                                'bagType' => $bagType,
                                'freightRate' => $freightRate,
                                'amount' => $amount,
                                'bag_count' => $bagCount,

                            ];
                        }
                    }
                }

                $now = now();

                $freightSerial = Freight::where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    ->max('invoice_serial') ?? 0;

                $voucherSerial = Voucher::where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    ->where('voucher_type_id', VoucherType::SALE_INVOICE)
                    ->max('voucher_serial') ?? 0;

                foreach ($data as $row) {

                    $freightSerial++;
                    $invoiceNumber = sprintf('FRI-%s-%05d', $year, $freightSerial);

                    $freightRecord = Freight::create([
                        'uuid'               => Str::uuid(),
                        'company_id'         => $companyId,
                        'financial_year_id'  => $financialYearId,
                        'account_id'         => $row['billTo'],
                        'vehicle_id'         => $row['vehicleId'],
                        'consignor_id'       => $row['consignorId'],
                        'consignee_id'       => $row['consigneeId'],
                        'from_destination_id' => $row['from'],
                        'to_destination_id'  => $row['toDestId'],
                        'grn_serial'         => $row['grn'],
                        'lr_number'          => $row['lr'],
                        'invoice_serial'     => $freightSerial,
                        'invoice_number'     => $invoiceNumber,
                        'prefix'             => $row['prefix'],
                        'invoice_date'       => $row['invDate'],
                        'total_amount'       => $row['amount'],
                        'entry_from'         => Freight::ENTRY_FROM_VOUCHER,
                        'reference_number'   => $row['billNo'],
                    ]);

                    FreightItem::create([
                        'freight_id' => $freightRecord->id,
                        'item_id'    => $row['itemId'],
                        'bag_type'   => $row['bagType'],
                        'bag_count'  => $row['bag_count'],
                        'net_weight' => $row['netWeight'],
                        'quantity'   => $row['netWeight'],
                        'rate'       => $row['freightRate'],
                        'amount'     => $row['amount'],
                    ]);

                   

                    $voucherSerial++;
                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::SALE_INVOICE,
                        'voucher_serial'    => $voucherSerial,
                        'voucher_number'    => $invoiceNumber,
                        'voucher_date'      => $row['invDate'],
                        'source_id'         => $freightRecord->id,
                        'source_type'       => SourceType::FREIGHT,
                        'reference_number'  => $row['prefix'].' '.$row['billNo'],
                        'narration'         => null,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);

                     VehicleIncome::create([
                        'voucher_id' => $voucher->id,
                        'company_id'     => $companyId,
                        'financial_year_id' => $financialYearId,
                        'freight_id'        => $freightRecord->id,
                        'income_account_id' => $freightIncomeId,
                        'amount' => $row['amount'],
                        'vehicle_id' => $row['vehicleId'],
                        'income_date'   => $row['invDate'],
                        'voucher_date' => $row['invDate'],
                    ]);

                    VoucherTransaction::insert([
                        [
                            'voucher_id'         => $voucher->id,
                            'account_id'         => $row['billTo'],
                            'debit'              => $row['amount'],
                            'credit'             => 0,
                            'is_party_account'   => true,
                            'against_account_id' => $freightIncomeId,
                            'narration'          => null,
                            'line_no'            => 1,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ],
                        [
                            'voucher_id'         => $voucher->id,
                            'account_id'         => $freightIncomeId,
                            'debit'              => 0,
                            'credit'             => $row['amount'],
                            'is_party_account'   => false,
                            'against_account_id' => $row['billTo'],
                            'narration'          => null,
                            'line_no'            => 2,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ],
                    ]);

                    $freightRecord->update(['voucher_id' => $voucher->id]);
                }
            });
    }
}

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
use App\Models\DriverExpense;
use App\Models\DriverExpenseItem;
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
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\VehicleIncome;
use App\Models\VehicleMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class TransportSilkData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:t-silk {company_id} {financial_year_id}';

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

        $silakDetails = DB::connection('old_db')
            ->table('driver_silak_details')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')
            ->get()->groupBy('voucher_no');


        $bhatthu = Destination::where('name', 'Bhatthu')->first();

        $vehicles = Vehicle::where('company_id', $companyId)->pluck('name', 'id');

        $driverSilakAccountId = Account::where('company_id', $companyId)->where('name', 'DRIVER SILAK')->value('id');

        DB::connection('old_db')
            ->table('driver_silaks')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')

            ->orderBy('id')
            ->chunk(200, function ($silks) use (
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
                $year,
                $silakDetails,
                $bhatthu,
                $vehicles,
                $driverSilakAccountId
            ) {
                // $voucherSerial = 3000;
                foreach ($silks as $key => $silk) {

                    // base voucher according main voucher
                    // 2000 = 5000 



                    $details = $silakDetails[$silk->voucher_no];

                    // base Account = DRIVER SILAK 

                    foreach ($details as $detail) {
                        $driverId = $accountMaster[$detail->driver_id] ?? null;

                        $voucherSerial = Voucher::where('company_id', $companyId)
                            ->where('financial_year_id', $financialYearId)
                            ->where('voucher_type_id', VoucherType::PAYMENT)
                            ->max('voucher_serial') ?? 0;


                        $voucherSerial++;
                        $voucher = Voucher::create([
                            'uuid'              => Str::uuid(),
                            'company_id'        => $companyId,
                            'financial_year_id' => $financialYearId,
                            'voucher_type_id'   => VoucherType::PAYMENT,
                            'voucher_serial'    => $voucherSerial,
                            'voucher_number'    => $voucherSerial,
                            'voucher_date'      => $detail->voucher_date,
                            'source_id'         => null,
                            'source_type'       => SourceType::PAYMENT,
                            'reference_number'  => null,
                            'narration'         => "",


                        ]);

                        if(!$driverId){
                            dd($detail);
                        }

                        if(!$driverSilakAccountId){
                            dd($detail);
                        }

                        $now = now();
                        VoucherTransaction::insert([
                            [
                                'voucher_id'         => $voucher->id,
                                'account_id'         => $driverId,
                                'debit'              => $detail->driver_total_amount,
                                'credit'             => 0,
                                'is_party_account'   => true,
                                'against_account_id' => $driverSilakAccountId,
                                'narration'          => null,
                                'line_no'            => 1,
                                'created_at'         => $now,
                                'updated_at'         => $now,
                            ],
                            [
                                'voucher_id'         => $voucher->id,
                                'account_id'         => $driverSilakAccountId,
                                'debit'              => 0,
                                'credit'             => $detail->driver_total_amount,
                                'is_party_account'   => false,
                                'against_account_id' => $driverId,
                                'narration'          => null,
                                'line_no'            => 2,
                                'created_at'         => $now,
                                'updated_at'         => $now,
                            ],
                        ]);

                        $pv =  PaymentVoucher::create([
                            'voucher_id' => $voucher->id,
                            'company_id' => $companyId,
                            'financial_year_id' => $financialYearId,
                            'paid_amount' => $detail->driver_total_amount,
                            'bank_id' => null,
                            'is_paid' => true,
                            'is_approved' => true,
                            'is_hold' => false,
                            'account_id' => $driverId,
                            'file_number' => '',
                            'mode',
                            'entry_from' => PaymentVoucher::ENTRY_FROM_PAYMENT_VOUCHER,
                            'payment_id' => null,
                            'is_transport_payment' => true,
                        ]);

                        $voucher->update([
                            'source_id' => $pv->id
                        ]);
                    }
                }
            });
    }
}

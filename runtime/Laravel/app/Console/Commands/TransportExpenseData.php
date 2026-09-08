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

class TransportExpenseData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:t-exp {company_id} {financial_year_id}';

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

        $expeDetail = DB::connection('old_db')
            ->table('driver_silak_expense_details')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')
            ->get()->groupBy('voucher_no');


        $bhatthu = Destination::where('name', 'Bhatthu')->first();

        // $vehicles = Vehicle::where('company_id', $companyId)->pluck('name', 'id');

        DB::connection('old_db')
            ->table('driver_silak_expenses')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')

            ->orderBy('id')
            ->chunk(200, function ($exps) use (
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
                $expeDetail,
                $bhatthu,
                // $vehicles
            ) {

                foreach ($exps as $key => $exp) {
                    // dd($exp);

                    if (! isset($vehicleMapping[$exp->vehicle_id])) {
                        $this->warn("Skipping expense id={$exp->id}: no vehicle mapping for vehicle_id={$exp->vehicle_id}");
                        continue;
                    }

                    $expense =  DriverExpense::create([
                        'uuid' => uuid(),
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_id' => null,
                        'voucher_date' => $exp->voucher_date,
                        'vehicle_id' => $vehicleMapping[$exp->vehicle_id],
                        'account_id' => $accounts[$exp->driver_id] ?? null, //Driver Id
                        'narration' => $exp->narration,
                        'start_kms' => $exp->start_km,
                        'end_kms' => $exp->end_km,
                        'total_kms' => $exp->total_km,

                        'idle_days' => $exp->idle_days ?? 0,
                        'idle_day_wage' => $exp->idle_day_wage ?? 0,
                        'idle_day_wage_amount' => $exp->idle_day_wage_amount ?? 0,
                        'expense_total' =>  0,

                    ]);

                    $driverExpItem = $expeDetail->get($exp->voucher_no);

                    if (! $driverExpItem) {
                        continue;
                    }

                    $itemData = [];
                    $totalExp = 0;

                    foreach ($driverExpItem as $key => $data) {

                        $itemData[] = [
                            'driver_expense_id'    => $expense->id,
                            'billing_date'         => $data->expense_date   ?? null,
                            'expense_account_id'   => $accounts[$exp->expense_account] ?? null,
                            'from_destination_id'  => $destinationMapping['70' . $data->from_mandali] ?? null,
                            'to_destination_id'    => $destinationMapping['70' . $data->to_mandali]   ?? null,
                            'item_id'              => $itemMapping['500' . $data->product]  ?? null,
                            'dc_lr'                =>  null,
                            'rate'                 =>  0,
                            'bags'                 => $data->bags    ?? 0,
                            'weight'               => $data->weight  ?? 0,
                            'trips'                => $data->trips   ?? 0,
                            'amount'               => $data->expense_total_amount  ?? 0,
                            'remark'               => $data->narration  ?? null,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];

                        $totalExp += $data->expense_total_amount;
                    }

                    if ($expense->idle_day_wage_amount > 0) {
                        $itemData[] = [
                            'driver_expense_id'    => $expense->id,
                            'billing_date'         => $data->expense_date   ?? null,
                            'expense_account_id'   => $accounts[$exp->expense_account] ?? null,
                            'from_destination_id'  => $bhatthu->id  ?? null,
                            'to_destination_id'    => $bhatthu->id   ?? null,
                            'item_id'              => null,
                            'dc_lr'                =>  null,
                            'rate'                 =>  0,
                            'bags'                 =>  0,
                            'weight'               =>  0,
                            'trips'                =>  0,
                            'amount'               => abs($expense->idle_day_wage_amount),
                            'remark'               => 'Idle Day Wage' ?? null,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];
                        $totalExp += abs($expense->idle_day_wage_amount);
                    }







                    if ($itemData) {
                        DriverExpenseItem::insert($itemData);
                    }



                    $expense->expense_total = $totalExp;
                    $expense->save();

                    // create journal voucher now 

 $voucherSerial = Voucher::where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    ->where('voucher_type_id', VoucherType::JOURNAL)
                    ->max('voucher_serial') ?? 0;

                    $voucherSerial++;

                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::JOURNAL,
                        'voucher_serial'    => $voucherSerial,
                        'voucher_number'    => $voucherSerial,
                        'voucher_date'      => $exp->voucher_date,
                        'source_id'         => null,
                        'source_type'       => SourceType::JOURNAL,
                        'reference_number'  => $exp->voucher_no,
                        'narration' => "",
                    ]);

                    $expense->expense_total = $totalExp;
                    $expense->voucher_id = $voucher->id;
                    $expense->save();

                    VehicleExpense::create([
                        'voucher_id' => $voucher->id,
                        'company_id'     => $companyId,
                        'financial_year_id' => $financialYearId,
                        'vehicle_id' => $vehicleMapping[$exp->vehicle_id] ?? null,
                        'expense_account_id' => $accounts[$exp->expense_account] ?? null,
                        'bill_date' => null,
                        'income_account_id' => $freightIncomeId,
                        'amount' => $totalExp,
                        'voucher_date' =>  $voucher->voucher_date,
                    ]);


                    VoucherTransaction::insert([
                        [
                            'voucher_id'         => $voucher->id,
                            'account_id'         => $accounts[$exp->expense_account],
                            'debit'              => $totalExp,
                            'credit'             => 0,
                            'is_party_account'   => false,
                            'against_account_id' => $expense->account_id,
                            'narration'          => null,
                            'line_no'            => 1,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ],
                        [
                            'voucher_id'         => $voucher->id,
                            'account_id'         => $expense->account_id,
                            'debit'              => 0,
                            'credit'             => $totalExp,
                            'is_party_account'   => true,
                            'against_account_id' => $accounts[$exp->expense_account],
                            'narration'          => null,
                            'line_no'            => 2,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ],
                    ]);

                    JournalVoucher::create([
                        'voucher_id' => $voucher->id,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'vehicle_id' => $vehicleMapping[$exp->vehicle_id] ?? null,
                        'gst_nature' => 'gst_not_applicable',
                        'entry_from' => JournalVoucher::TRANSPORT_EXP_VOUCHER,
                        'bill_date' => null,
                    ]);
                }
            });
    }
}

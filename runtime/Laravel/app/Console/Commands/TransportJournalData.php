<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\ConditionMapping;
use App\Models\DestinationMapping;
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
use App\Models\Reference;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\TransportJournalMapping;
use App\Models\VehicleExpense;
use App\Models\VehicleMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class TransportJournalData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:t-journal {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Journal Data Insert';

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





        $vehicleMapping = VehicleMapping::pluck('new_vehicle_id', 'old_vehicle_id');

        $silakExpense = DB::connection('old_db')
            ->table('driver_silak_expenses')
            // ->where('voucher_no', $journals->pluck('id'))
            // ->orderBy('payment_mode','desc')
            ->where('cc_id', 47)

            ->get()
            ->keyBy('voucher_no');

        $silakExpAccount = Account::where('name', 'VEHICLE LABOUR EXP.')->first();
        // dd($silakExpAccount);

        $driverSalaryVoucher = DB::connection('old_db')->table('driver_salary_details')->where('cc_id', 47)->get()->groupBy('voucher_no')->toArray();

        $driverExpVoucher = DB::connection('old_db')->table('driver_silak_expenses')->where('cc_id', 47)->get()->groupBy('voucher_no')->toArray();

        $skipVoucher = array_merge(array_keys($driverSalaryVoucher), array_keys($driverExpVoucher));
        // dd($skipVoucher);

        DB::connection('old_db')
            ->table('transport_vouchers')
            ->where('cc_id', 47)
            ->whereNull('deleted_at')
            ->where('voucher_type', 1)
            ->whereNotIn('voucher_number', array_values($skipVoucher))
            ->where('expense_account_type', '!=', 1021)
            ->orderBy('id')
            ->chunk(200, function ($journals) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $vehicleMapping,
                $silakExpense,
                $silakExpAccount
            ) {
                // dd($journals->pluck('id')?);
                $allDetails = DB::connection('old_db')
                    ->table('transport_voucher_details')
                    ->whereIn('transport_voucher_id', $journals->pluck('id'))
                    ->where('voucher_type', 1)
                    ->orderBy('payment_mode', 'desc')
                    ->get()
                    ->groupBy('transport_voucher_id');

                foreach ($journals as $key => $journal) {
                    $newExpId = $accountMaster[$journal->expense_account_type] ?? null;

                    $serial = $journal->voucher_number;

                     $voucherSerial = Voucher::where('company_id', $companyId)
                    ->where('financial_year_id', $financialYearId)
                    ->where('voucher_type_id', VoucherType::JOURNAL)
                    ->max('voucher_serial') ?? 0;



                    $detialData = $allDetails->get($journal->id, collect());

                    $voucherSerial++;


                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::JOURNAL,
                        'voucher_serial'    => $voucherSerial,
                        'voucher_number'    => $voucherSerial,
                        'voucher_date'      => $journal->voucher_date,
                        'reference_number'  => $journal->voucher_bill_no,
                        'source_type'       => SourceType::JOURNAL,
                        'narration'         => ""
                    ]);


                    $voucherLine = [];

                    $voucherLine = [];

                    $crId = null;
                    $amount = 0;
                    $newVehicleId = null;

                    // Credit Entry
                    foreach ($detialData as $jvd) {
                        if ($jvd->payment_mode == 1) {

                            $accountId = $accountMaster[$jvd->account_id] ?? null;

                            $voucherLine[] = [
                                'voucher_id'         => $voucher->id,
                                'account_id'         => $accountId,
                                'debit'              => 0,
                                'credit'             => $jvd->amount,
                                'narration'          => null,
                                'is_party_account'   => true,
                                'against_account_id' => $newExpId,
                            ];

                            $crId = $accountId;
                            $amount = $jvd->amount;
                        }else{
                            // check hear account name start with GJ
                            if (Str::startsWith($jvd->account_name, 'GJ')) {
                                // dd($vehicleMapping , $jvd->account_id);
                                    $vehicleId =  $vehicleMapping[$jvd->account_id] ?? null; 
                                    if ($vehicleId) {
                                $newVehicleId = $vehicleId;
                            }
                                }

                           
                        }
                    }

                    // Debit Entry
                    $voucherLine[] = [
                        'voucher_id'         => $voucher->id,
                        'account_id'         => $newExpId,
                        'debit'              => $amount,
                        'credit'             => 0,
                        'narration'          => null,
                        'is_party_account'   => false,
                        'against_account_id' => $crId,
                    ];

                    if($newVehicleId){
                        VehicleExpense::create([
                             'voucher_id' => $voucher->id,
                            'company_id' => $companyId,
                            'financial_year_id' => $financialYearId,
                            'vehicle_id' => $newVehicleId,
                            'expense_account_id' => $newExpId,
                            'amount' => $amount ?? 0,
                            'bill_date' => $voucher->voucher_date,
                            'voucher_date' => $voucher->voucher_date,
                        ]);
                    }
                     

                    // If you need to swap the first two lines
                    [$voucherLine[0], $voucherLine[1]] = [$voucherLine[1], $voucherLine[0]];

                    // Assign line numbers according to the final order
                    foreach ($voucherLine as $index => &$line) {
                        $line['line_no'] = $index + 1;
                    }
                    unset($line);

                    // Insert
                    VoucherTransaction::insert($voucherLine);
                }
            });
    }
}

//  foreach ($journals as $pv) {
//                     $newExpId = $accountMaster[$pv->expense_account_type] ?? null;

//                     $serial = $pv->voucher_number;
//                     //  if($serial == 3894){
//                     //     dd( $pv);
//                     // }

//                     if (Voucher::where('company_id', $companyId)
//                         ->where('financial_year_id', $financialYearId)
//                         ->where('voucher_type_id', VoucherType::JOURNAL)
//                         ->where('voucher_serial', $serial)
//                         ->exists()
//                     ) {
//                         $serial = Voucher::where('company_id', $companyId)
//                             ->where('financial_year_id', $financialYearId)
//                             ->where('voucher_type_id', VoucherType::JOURNAL)
//                             ->max('voucher_serial') + 1;
//                     }

//                     TransportJournalMapping::create([
//                         'company_id'        => $companyId,
//                         'old_voucher_no'    => $pv->voucher_number,
//                         'new_voucher_no'    => $serial,
//                         'old_table_id'    => $pv->id,
//                     ]);

//                     $voucher = Voucher::create([
//                         'uuid'              => Str::uuid(),
//                         'company_id'        => $companyId,
//                         'financial_year_id' => $financialYearId,
//                         'voucher_type_id'   => VoucherType::JOURNAL,
//                         'voucher_serial'    => $serial,
//                         'voucher_number'    => $serial,
//                         'voucher_date'      => $pv->voucher_date,
//                         'reference_number'  => null,
//                         'source_type'       => SourceType::JOURNAL,
//                         'narration'         => $pv->narration
//                     ]);

//                     $detialData = $allDetails->get($pv->id, collect());

//                     $voucherLine = [];
//                     if (!$newExpId) {
//                         dd($pv);
//                     }

//                     //   if($serial == 3894){
//                     //     dd( $pv);
//                     // }

//                     $crId = null;
//                     $amount  = 0;
//                     $isEntryPost = false;
//                     foreach ($detialData as $line => $ldData) {


//                         if ($ldData->payment_mode == 2 && $isEntryPost == false) {
//                             $ldAccountId = null;
//                             if (in_array($pv->voucher_number, ['331', '335', '336', '337', '338', '396', '397', '485', '486', '487', '488', '489'])) {

//                                 $salaryData = $driverSalary->get($pv->voucher_number, collect());
//                                 foreach ($salaryData as $sdt) {
//                                     if ($sdt->vehicle_id == $ldData->account_id) {
//                                         // dd($sdt->driver_id, $accountMaster[$sdt->driver_id]);
//                                         $ldAccountId = $accountMaster[$sdt->driver_id] ?? null;
//                                     }
//                                 }

//                                 if (!$ldAccountId) {
//                                     if ($pv->voucher_number == 331 && $ldData->account_id == 33) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 335 && $ldData->account_id == 74) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 336 && $ldData->account_id == 57) {
//                                         continue;
//                                     }
//                                        if ($pv->voucher_number == 336 && $ldData->account_id == 72) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 338 && $ldData->account_id == 35) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 397 && $ldData->account_id == 2035) {
//                                         continue;
//                                     }

//                                     if ($pv->voucher_number == 485 && $ldData->account_id == 48) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 487 && $ldData->account_id == 24) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 488 && $ldData->account_id == 56) {
//                                         continue;
//                                     }
//                                      if ($pv->voucher_number == 488 && $ldData->account_id == 72) {
//                                         continue;
//                                     }
//                                     if ($pv->voucher_number == 489 && $ldData->account_id == 39) {
//                                         continue;
//                                     }
//                                     dd($ldData);
//                                 }


//                                 $crId = $ldAccountId;
//                                 $amount = $ldData->amount;
//                                 $voucherLine[] = [
//                                     'voucher_id' => $voucher->id,
//                                     'account_id' => $ldAccountId,
//                                     'debit'  => 0,
//                                     'credit' => $ldData->amount,
//                                     'narration' => null,
//                                     'is_party_account' =>  true,
//                                     'line_no' => $line + 1,
//                                     'against_account_id' => $newExpId
//                                 ];
//                                 $isEntryPost = true;
//                             }
//                         } else {
//                             if ($isEntryPost == true) {
//                                 continue;
//                             }
//                             $ldAccountId = $accountMaster[$ldData->account_id] ?? null;
//                             if (!$ldAccountId) {
//                                 dd("else", $ldData);
//                             }

//                             $crId = $ldAccountId;
//                             $amount = $ldData->amount;
//                             $voucherLine[] = [
//                                 'voucher_id' => $voucher->id,
//                                 'account_id' => $ldAccountId,
//                                 'debit'  => 0,
//                                 'credit' => $ldData->amount,
//                                 'narration' => null,
//                                 'is_party_account' =>  true,
//                                 'line_no' => $line + 1,
//                                 'against_account_id' => $newExpId
//                             ];
//                             $isEntryPost = true;
//                         }
//                     }
//                     // dd($silakExpense);
//                     // if($serial == 3894){
//                     //     dd( $silakExpense->get($serial));
//                     // }
//                     $silckExp = $silakExpense->get($serial);
//                     if ($silckExp) {
//                         $vilExpId = $silakExpAccount->id;
//                         // dd($silckExp);
//                         $driverId = $accountMaster[$silckExp->driver_id] ?? null;

//                         $voucherLine[] = [
//                             'voucher_id' => $voucher->id,
//                             'account_id' =>  $vilExpId,
//                             'debit'  => $amount,
//                             'credit' => 0,
//                             'narration' => null,
//                             'is_party_account' =>  true,
//                             'line_no' => 2,
//                             'against_account_id' => $driverId
//                         ];

//                         $voucherLine[0]['account_id'] = $driverId;
//                         $voucherLine[0]['against_account_id'] = $vilExpId;
//                     }else{

//                         // exp entry
//                         $voucherLine[] = [
//                             'voucher_id' => $voucher->id,
//                             'account_id' => $newExpId,
//                             'debit'  => $amount,
//                             'credit' => 0,
//                             'narration' => null,
//                             'is_party_account' =>  true,
//                             'line_no' => 2,
//                             'against_account_id' => $crId
//                         ];
//                     }
//                     VoucherTransaction::insert($voucherLine);

//                     $journal = JournalVoucher::create([
//                         'voucher_id' => $voucher->id,
//                         'company_id' => $companyId,
//                         'financial_year_id' => $financialYearId,
//                         'gst_nature' => 'gst_not_applicable'
//                     ]);

//                     $voucher->update([
//                         'source_id' => $journal->id
//                     ]);
//                 }
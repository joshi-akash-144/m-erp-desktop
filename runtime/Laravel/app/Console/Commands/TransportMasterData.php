<?php

namespace App\Console\Commands;

use App\Enums\FuelType;
use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\AccountBankDetail;
use App\Models\AccountCodeSequence;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\AccountPreference;
use App\Models\AccountTaxDetail;
use App\Models\Broker;
use App\Models\BrokerMapping;
use App\Models\Condition;
use App\Models\ConditionMapping;
use App\Models\DairyParameter;
use App\Models\DairyParameterDetail;
use App\Models\DairyParameterMapping;
use App\Models\Destination;
use App\Models\DestinationMapping;
use App\Models\Driver;
use App\Models\DriverMapping;
use App\Models\Godown;
use App\Models\GodownMapping;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\ItemMapping;
use App\Models\PurchaseType;
use App\Models\PurchaseTypeMapping;
use App\Models\SaleType;
use App\Models\SaleTypeMapping;
use App\Models\TaxCategory;
use App\Models\Unit;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\VoucherService;
use App\Models\Element;
use App\Models\ElementMapping;
use App\Models\ParameterDetail;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\TransportParty;
use App\Models\TransportPartyMapping;
use App\Models\Vehicle;
use App\Models\VehicleMapping;
use App\Models\VehicleOwner;
use App\Models\VoucherTransaction;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class TransportMasterData extends Command
{
    protected $signature = 'app:t-master {company_id} {financial_year_id}';
    protected $description = 'Migrate data from the old ERP database to the new system.';

    // ─── Change these before running for a different company / year ──────────
    // private const COMPANY_ID        = 2;
    private const COMPANY_STATE_ID  = 11;  // state_id of the company (for GST local/interstate)
    // private const FINANCIAL_YEAR_ID = 2;

    private int $companyId;
    private int $financialYearId;

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->companyId = (int) $this->argument('company_id');
        $this->financialYearId = (int) $this->argument('financial_year_id');

        $this->setOldData();
    }

    public function setOldData()
    {
        DB::beginTransaction();
        try {
            $this->getVehicleOwner();

            $this->account();
            $this->transportParty();
            $this->getVehicle();
            $this->item();
            $this->destination();
            $this->zone();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function zone()
    {
        $companyId = $this->companyId;
        $olVO = DB::connection('old_db')->table('zones')->get();


        foreach ($olVO as $key => $v) {


            $transporter =  Zone::create([
                'uuid' => Str::uuid(),
                'company_id' => $companyId,
                'name' => $v->zone_number,
                'rate' => $v->zone_rate,
                'remarks' => $v->remark,
                'created_by',
            ]);
        }
    }

    public function account()
    {
        $accounts = DB::connection('old_db')
            ->table('transport_accounts')
            ->whereIn('account_type', [1, 2, 6])
            ->where('cc_id', 9)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $companyId = $this->companyId;

        $oldAccountGroups = DB::connection('old_db')->table('account_heads')->get()->keyBy('id');

        $newAccountGroups = AccountGroup::where('company_id', $companyId)->get()->keyBy('code');

        $sequenceCodes      = AccountCodeSequence::where('company_id', $companyId)->get()->keyBy('account_group_id');
        $sequenceIncrements = [];


        $now = now();

        
        $creditor =  AccountGroup::where('code', 270)->where('company_id', $companyId)->value('id');
        $debtor =  AccountGroup::where('code', 170)->where('company_id', $companyId)->value('id');

        $olVO = DB::connection('old_db')->table('drivers')->where('cc_id', 9)->get()->keyBy('id');

        // Bulk collect arrays — inserted in one shot after the loop
        $taxDetails       = [];
        $preferences      = [];
        $bankDetails      = [];
        $voucherLines     = [];
        $accountMappings  = [];

        $financialYearName = 'FY 2026-27';
        $year = substr(str_replace(['-', ' ', 'FY'], '', $financialYearName), -4);

        $lastVoucher = Voucher::where('voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $this->financialYearId)
            ->orderByDesc('voucher_serial')
            ->first();
        $lastSerial = $lastVoucher ? $lastVoucher->voucher_serial : 0;


        $offsetAccountId = Account::where('code', '35000')->where('company_id', $companyId)->value('id');



        foreach ($accounts as $key => $account) {


            $oldAccountGroupId = $account->account_head_id;
            if (!$oldAccountGroupId) continue;
            $oldAccountGroup = $oldAccountGroups[$oldAccountGroupId];
            if (!isset($newAccountGroups[$oldAccountGroup->code])) {
                continue;
            }

            $newAccountGroup   = $newAccountGroups[$oldAccountGroup->code];
            $newAccountGroupId = $newAccountGroup->id;


            if (!isset($sequenceCodes[$newAccountGroupId])) {
                throw new \Exception("Account code sequence not found for company  and group [$newAccountGroupId]");
            }

            $currentCode = $sequenceCodes[$newAccountGroupId]->last_code
                + ($sequenceIncrements[$newAccountGroupId] ?? 0);

            if ($account->account_type == 2) {

                $v = $olVO->get($account->account_id);


                $driverName = Account::where('company_id', $companyId)
                    ->where('name', $v->driver_name)
                    ->exists()
                    ? $v->driver_name . ' (' . $currentCode . ')'
                    : $v->driver_name;

                $vaid = Account::create([
                    'uuid'                      => Str::uuid(),
                    'company_id'                => $companyId,
                    'account_group_id'          =>  $creditor,
                    'code'                      => $currentCode,
                    'name'                      => $driverName,
                    'print_name'                 => $driverName,
                    'city'                        => $v->city,
                    'postal_code'               => $v->pin_code,

                    'address_one'               => $v->address,
                    'address_two'           => null,
                    'mobile_number'         => $v->mobile_number,
                    'whatsapp_number'       => null,
                    'email'                 => null,
                    'is_billwise'           => true,
                    'state_id'              => 11,
                    'country_id'            => 1,

                    'party_type'            => 'account',
                    'gst_type'              => 'local',

                    'is_hidden'             => false,

                ]);

                $sequenceIncrements[$newAccountGroupId] = ($sequenceIncrements[$newAccountGroupId] ?? 0) + 1;

                AccountBankDetail::create([
                    'account_id' => $vaid->id,
                    'bank_beneficiary_name' => null,
                    'bank_name' => $v->bank_name,
                    'bank_branch_name' => $v->bank_branch,

                    'bank_account_number' => $v->bank_acc_no,
                    'bank_ifsc' => $v->bank_IFSCcode,
                    'bank_account_type' => null,
                    'is_default_bank' => false,
                    'rtgs_form_view_id' => null,
                ]);

                AccountTaxDetail::create([
                    'account_id' => $vaid->id,
                    'type_of_dealer' => null,
                    'filing_frequency' => null,
                    'gst_number' => null,

                    'tax_category_id' => null,
                    'hsn_sac_code' => null,
                    'itc_eligibility' => null,
                    'rcm_nature' => null,
                    'pan' => null,
                    'tin' => null,
                    'tax_type' => null,
                    'gst_type' => null,
                ]);

                AccountPreference::create([
                    'account_id' => $vaid->id,
                    'transport_mode' => 'road',
                    'sale_type_id' => null,
                    'purchase_type_id' => null,
                    'distance' => 0,

                    'station' => null,
                    'contact_person' => null,
                    'transport' => null,
                    'purchase_unit_id' => null,
                    'sale_unit_id' => null,
                    'purchase_commission_rate' => 0,
                    'sale_commission_rate' => 0
                ]);

                $driver =  Driver::create([

                    'account_id' => $vaid->id,
                    // 'vehicle_id',
                    'company_id'                => $companyId,
                    'date_of_joining' => $v->date_of_joining,
                    'license_number' => $v->license_number,
                    'adhara_number' => $v->aadhaar_number,
                    'religion' => $v->religion,
                    'qualification' => $v->qualification,
                    'marital_status' => null,
                    'blood_group' => null,
                    'salary' => $v->salary,

                    'license_category' => 'cd',
                    'license_issuing_authority' => null,
                    'license_expiry_date_tr' => $v->license_expiry_date_tr  == '0000-00-00' ? null : $v->license_expiry_date_tr,
                    'license_expiry_date_nt' => $v->license_expiry_date_tr  == '0000-00-00' ? null : $v->license_expiry_date_tr,

                ]);

                $nextSerial    = ++$lastSerial;
                $voucherNumber = sprintf("%s-%s-%05d", 'OPB', $year, $nextSerial);

                $voucher = Voucher::create([
                    'uuid'              => uuid(),
                    'company_id'        => $companyId,
                    'financial_year_id' => $this->financialYearId,
                    'voucher_date'      => '2026-04-01',
                    'voucher_type_id'   => VoucherType::OPENING_BALANCE,
                    'reference_id'      => null,
                    'reference_type'    => 'opening_balance',
                    'reference_number'  => null,
                    'voucher_serial'    => $nextSerial,
                    'voucher_number'    => $voucherNumber,
                    'narration'         => 'Opening Balance as on 2026-04-01',
                    'is_opening'        => true,
                ]);

                $amount =   $v->opening_balance ?? 0;


                $balanceType = $amount >= 0 ? 'd' : 'c';

                if ($balanceType == 'd') {
                    VoucherTransaction::create(['voucher_id' => $voucher->id, 'account_id' => $vaid->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now]);

                    VoucherTransaction::create(['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Contra', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now]);
                } else {
                    VoucherTransaction::create(['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Contra', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now]);

                    VoucherTransaction::create(['voucher_id' => $voucher->id, 'account_id' => $vaid->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now]);
                }


                AccountMapping::create([
                    'old_account_id'       => $account->id,
                    'new_account_id'       =>  $vaid->id,
                    'company_id'           => $companyId,
                    'old_account_group_id' => $oldAccountGroupId,
                    'new_account_group_id' => $newAccountGroupId,
                    'old_account_name'     => $account->account_name,
                    'account_type'         => $account->account_type,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                continue;
            } else {

                // $isDuplicate = Account::where('company_id', $companyId)->where('name', $account->account_name)->exists();

                // if ($isDuplicate) {
                //     $account->account_name = $account->account_name . '';
                // }


                // if($account->account_type == 6 &&  $account->id ==2071){
                //         dd($account);
                //     }

                $accountGroupId = null;
                if($account->account_type == 2){
                    $accountGroupId = $creditor;
                }else if($account->account_type == 6){
                    $accountGroupId = $debtor;
                }else{
                    $accountGroupId = $newAccountGroupId;
                }


                $accountCreate = Account::create([
                    'uuid'             => Str::uuid(),
                    'code'             => $currentCode,
                    'account_group_id' => $accountGroupId,
                    'name'             => $account->account_name,
                    'print_name'       => $account->account_name,
                    'city'             => $account->city,
                    'postal_code'      => $account->pin_code,
                    'address_one'      => $account->address1,
                    'address_two'      => $account->address2,
                    'mobile_number'    => $account->mobile,
                    'gst_type'         => $account->state_id == self::COMPANY_STATE_ID ? Account::GST_TYPE_LOCAL : Account::GST_TYPE_INTERSTATE,
                    'whatsapp_number'  => $account->mobile,
                    'email'            => strtolower(trim($account->email_id ?? '')),
                    'is_billwise'      => false,
                    'party_type'       => $oldAccountGroupId == 2 ? 'customer' : 'account',
                    'state_id'         => $account->state_id,
                    'country_id'       => $account->country_id,
                    'company_id'       => $companyId,
                ]);

                $sequenceIncrements[$newAccountGroupId] = ($sequenceIncrements[$newAccountGroupId] ?? 0) + 1;

                $gstType = ($account->gst_no && strlen($account->gst_no) >= 15) ? 'registered' : 'unregistered';
                $taxDetails[] = [
                    'account_id'       => $accountCreate->id,
                    'type_of_dealer'   => $gstType,
                    'filing_frequency' => 'not_known',
                    'tax_type'         => null,
                    'gst_type'         => $gstType === 'registered' ? 'gst_applicable' : 'gst_not_applicable',
                    'gst_number'       => $account->gst_no,
                    'tin'              => null,
                    'pan'              => $account->pan_no,
                    'hsn_sac_code'     => null,
                    'itc_eligibility'  => 'none',
                    'rcm_nature'       => 'not_applicable',
                    'tax_category_id'  => null,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                $preferences[] = [
                    'account_id' => $accountCreate->id,
                    'distance'   => $account->kms ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $bankDetails[] = [
                    'account_id'            => $accountCreate->id,
                    'bank_name'             => $account->bank_name,
                    'bank_ifsc'             => $account->bank_IFSCcode,
                    'bank_branch_name'      => $account->bank_branch,
                    'bank_account_number'   => $account->bank_acc_no,
                    'bank_beneficiary_name' => null,
                    'rtgs_form_view_id'     => 1,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

                $nextSerial    = ++$lastSerial;
                $voucherNumber = sprintf("%s-%s-%05d", 'OPB', $year, $nextSerial);

                $voucher = Voucher::create([
                    'uuid'              => uuid(),
                    'company_id'        => $companyId,
                    'financial_year_id' => $this->financialYearId,
                    'voucher_date'      => '2026-04-01',
                    'voucher_type_id'   => VoucherType::OPENING_BALANCE,
                    'reference_id'      => null,
                    'reference_type'    => 'opening_balance',
                    'reference_number'  => null,
                    'voucher_serial'    => $nextSerial,
                    'voucher_number'    => $voucherNumber,
                    'narration'         => 'Opening Balance as on 2026-04-01',
                    'is_opening'        => true,
                ]);

                $amount = $account->opening_bal ?? 0;

                $balanceType = $amount >= 0 ? 'd' : 'c';

                if ($balanceType === 'd') {
                    $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $accountCreate->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now];
                    $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Contra', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now];
                } else {
                    $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Contra', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now];
                    $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $accountCreate->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now];
                }


                $accountMappings[] = [
                    'old_account_id'       => $account->id,
                    'new_account_id'       => $accountCreate->id,
                    'company_id'           => $companyId,
                    'old_account_group_id' => $oldAccountGroupId,
                    'new_account_group_id' => $newAccountGroupId,
                    'old_account_name'     => $account->account_name,
                    'account_type'         => $account->account_type,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        // Bulk insert child records in chunks
        foreach (array_chunk($taxDetails, 500) as $chunk) {
            DB::table('account_tax_details')->insert($chunk);
        }
        foreach (array_chunk($preferences, 500) as $chunk) {
            DB::table('account_preferences')->insert($chunk);
        }
        foreach (array_chunk($bankDetails, 500) as $chunk) {
            DB::table('account_bank_details')->insert($chunk);
        }
        foreach (array_chunk($voucherLines, 500) as $chunk) {
            DB::table('voucher_transactions')->insert($chunk);
        }
        foreach (array_chunk($accountMappings, 500) as $chunk) {
            DB::table('account_mappings')->insert($chunk);
        }

        // Update sequence codes with total increment per group in one query each
        foreach ($sequenceIncrements as $groupId => $increment) {
            AccountCodeSequence::where('account_group_id', $groupId)
                ->where('company_id', $companyId)
                ->increment('last_code', $increment);
        }
    }

    public function destination()
    {
        $companyId = $this->companyId;

        $erpDestination = DB::connection('old_db')
            ->table('destinations')
            ->orderBy('id')
            ->get();

        foreach ($erpDestination as $key => $epd) {

            $baseName = $epd->destination_name;
            $suffix = '';
            while (Destination::where('company_id', $companyId)->where('name', $baseName . $suffix)->exists()) {
                $suffix .= '.';
            }
            $epd->destination_name = $baseName . $suffix;

            $nd = Destination::create([
                'uuid'                => Str::uuid(),
                'name'                => $epd->destination_name,
                'contact_person_name' => null,
                'email'               => null,
                'mobile_number'       => null,
                'phone_number'        => null,
                'kms'                 => 0,
                'address_one'         => null,
                'address_two'         => null,
                'city'                => null,
                'district'            => null,
                'taluka'              => $epd->taluka ?? null,
                'state_id'            => 11,
                'country_id'          => 1,
                'postal_code'         => null,
                'company_id'          => $companyId,
            ]);

            DestinationMapping::create([
                'company_id'         => $companyId,
                'old_destination_id' => $epd->id,
                'new_destination_id' => $nd->id,
                'name'               => $epd->destination_name,
            ]);
        }


        $trp = DB::connection('old_db')
            ->table('mandalis')
            ->where('cc_id', 9)
            ->orderBy('id')
            ->get();

        // Generate unique names based on old ID
        $uniqueNames = [];
        $nameCounts = [];

        foreach ($trp as $tm) {
            $name = trim($tm->name);

            if (isset($nameCounts[$name])) {
                $nameCounts[$name]++;
                $uniqueNames[$tm->id] = $name . ' ' . $nameCounts[$name];
            } else {
                $nameCounts[$name] = 0;
                $uniqueNames[$tm->id] = $name;
            }
        }

        // DB::beginTransaction();

        // try {
        foreach ($trp as $value) {

            $baseName = trim($uniqueNames[$value->id]);
            $suffix = '';
            while (Destination::where('company_id', $companyId)->where('name', $baseName . $suffix)->exists()) {
                $suffix .= '.';
            }
            $destinationName = $baseName . $suffix;

            $newDestination = Destination::create([
                'uuid'                => Str::uuid(),
                'name'                => $destinationName,
                'contact_person_name' => null,
                'email'               => null,
                'mobile_number'       => null,
                'phone_number'        => null,
                'kms'                 => 0,
                'address_one'         => null,
                'address_two'         => null,
                'city'                => null,
                'district'            => null,
                'taluka'              => $value->taluka ?? null,
                'state_id'            => 11,
                'country_id'          => 1,
                'postal_code'         => null,
                'company_id'          => $companyId,
            ]);

            DestinationMapping::create([
                'company_id'         => $companyId,
                'old_destination_id' => '70' . $value->id,
                'new_destination_id' => $newDestination->id,
                'name'               => $destinationName,
            ]);
        }

         Destination::create([
                'uuid'                => Str::uuid(),
                'name'                => 'Bhatthu',
                'contact_person_name' => null,
                'email'               => null,
                'mobile_number'       => null,
                'phone_number'        => null,
                'kms'                 => 0,
                'address_one'         => null,
                'address_two'         => null,
                'city'                => null,
                'district'            => null,
                'taluka'              => $value->taluka ?? null,
                'state_id'            => 11,
                'country_id'          => 1,
                'postal_code'         => null,
                'company_id'          => $companyId,
            ]);


        // $erpDestination = DB::connection('old_db')
        //     ->table('destinations')
        //     ->orderBy('id')
        //     ->get();

        // foreach ($erpDestination as $key => $epd) {

        //     $baseName = $epd->destination_name;
        //     $suffix = '';
        //     while (Destination::where('company_id', $companyId)->where('name', $baseName . $suffix)->exists()) {
        //         $suffix .= '.';
        //     }
        //     $epd->destination_name = $baseName . $suffix;

        //     $nd = Destination::create([
        //         'uuid'                => Str::uuid(),
        //         'name'                => $epd->destination_name,
        //         'contact_person_name' => null,
        //         'email'               => null,
        //         'mobile_number'       => null,
        //         'phone_number'        => null,
        //         'kms'                 => 0,
        //         'address_one'         => null,
        //         'address_two'         => null,
        //         'city'                => null,
        //         'district'            => null,
        //         'taluka'              => $epd->taluka ?? null,
        //         'state_id'            => 11,
        //         'country_id'          => 1,
        //         'postal_code'         => null,
        //         'company_id'          => $companyId,
        //     ]);

        //     DestinationMapping::create([
        //         'company_id'         => $companyId,
        //         'old_destination_id' => $epd->id,
        //         'new_destination_id' => $nd->id,
        //         'name'               => $epd->destination_name,
        //     ]);
        // }


        // DB::commit();

        return [
            'status'  => true,
            'message' => 'Destinations imported successfully.',
        ];
        // } catch (\Exception $e) {

        //     DB::rollBack();

        //     return [
        //         'status'  => false,
        //         'message' => $e->getMessage(),
        //     ];
        // }
    }
    public function item()
    {
        $companyId = $this->companyId;
        $trp = DB::connection('old_db')->table('transport_products')->where('cc_id', 9)->get();
        $erpItem = DB::connection('old_db')->table('products')->get();
        // dd($trp);
        $itemGroup = ItemGroup::where('company_id', $companyId)->first();
        $nK = 600;

        foreach ($erpItem as $key => $ei) {
            $nt = Item::create([
                'uuid' => Str::uuid(),
                'name' => $ei->product_name,
                'sku'  => 'SKU-' . str_pad($nK, 5, '0', STR_PAD_LEFT),
                'item_group_id' => $itemGroup->id ?? null,
                'print_name' => $ei->product_name,
                'unit_id' => null,
                'tax_category_id' => null,
                'hsn_sac_code' => '',

                'sale_type_local_id' => null,
                'sale_type_interstate_id' => null,
                'purchase_type_local_id' => null,
                'purchase_type_interstate_id' => null,

                'is_maintain_stock_balance' => false,
                'company_id' => $companyId,
            ]);

            ItemMapping::create([
                'old_item_id' => $ei->id,
                'new_item_id' => $nt->id,
                'company_id' => $companyId,
                'name' => $ei->product_name,
            ]);
            $nK = $nK + 1;
        }
        // dd($trp->toArray());
        foreach ($trp as $key => $tp) {
            $isDuplicte  = Item::where('company_id', $companyId)->where('name', $tp->product_name)->first();
            if ($isDuplicte) {
                $tp->product_name = $tp->product_name . '.';
            }
            $newItem = Item::create([
                'uuid' => Str::uuid(),
                'name' => $tp->product_name,
                'sku'  => 'SKU-' . str_pad($nK, 5, '0', STR_PAD_LEFT),
                'item_group_id' => $itemGroup->id ?? null,
                'print_name' => $tp->product_name,
                'unit_id' => null,
                'tax_category_id' => null,
                'hsn_sac_code' => $tp->hsn_code,

                'sale_type_local_id' => null,
                'sale_type_interstate_id' => null,
                'purchase_type_local_id' => null,
                'purchase_type_interstate_id' => null,

                'is_maintain_stock_balance' => false,
                'company_id' => $companyId,
            ]);

            ItemMapping::create([
                'old_item_id' => '500' . $tp->id,
                'new_item_id' => $newItem->id,
                'company_id' => $companyId,
                'name' => $tp->product_name,
            ]);
            $nK = $nK + 1;
        }
    }

    public function getVehicleOwner()
    {
        $companyId = $this->companyId;
        $olVO = DB::connection('old_db')->table('vehicle_owners')->get();


        foreach ($olVO as $key => $v) {

            $transporter =  VehicleOwner::create([
                'uuid'                      => Str::uuid(),
                'company_id'                => $companyId,
                'name' => $v->owner_name,
            ]);
        }
    }

    // public function driver()
    // {
    //     $companyId = $this->companyId;
    //     $olVO = DB::connection('old_db')->table('drivers')->where('cc_id', 9)->get()->keyBy('id');
    //     $accountGroupId = AccountGroup::where('company_id', $companyId)->where('code', 270)->value('id');

    //     $accounts = DB::connection('old_db')
    //         ->table('transport_accounts')
    //         ->where('account_type', 2)
    //         ->where('cc_id', 9)
    //         ->orderBy('id')
    //         ->get();




    //     $sequenceCodes      = AccountCodeSequence::where('company_id', $companyId)->get()->keyBy('account_group_id');
    //     $sequenceIncrements = [];

    //     foreach ($accounts as $key => $a) {

    //         $v = $olVO->get($a->account_id);

    //         if (!isset($sequenceCodes[$accountGroupId])) {
    //             throw new \Exception("Account code sequence not found for company [$companyId] and group [$accountGroupId]");
    //         }

    //         $currentCode = $sequenceCodes[$accountGroupId]->last_code
    //             + ($sequenceIncrements[$accountGroupId] ?? 0);

    //         $account = Account::create([
    //             'uuid'                      => Str::uuid(),
    //             'company_id'                => $companyId,
    //             'account_group_id'          => $accountGroupId,
    //             'code'                      => $currentCode,
    //             'name'                      => $v->driver_name,
    //             'print_name'                 => $v->driver_name,
    //             'city'                        => $v->city,
    //             'postal_code'               => $v->pin_code,

    //             'address_one'               => $v->address,
    //             'address_two'           => null,
    //             'mobile_number'         => $v->mobile_number,
    //             'whatsapp_number'       => null,
    //             'email'                 => null,
    //             'is_billwise'           => true,
    //             'state_id'              => 11,
    //             'country_id'            => 1,

    //             'party_type'            => 'account',
    //             'gst_type'              => 'local',

    //             'is_hidden'             => false,

    //         ]);

    //         AccountBankDetail::create([
    //             'account_id' => $account->id,
    //             'bank_beneficiary_name' => null,
    //             'bank_name' => $v->bank_name,
    //             'bank_branch_name' => $v->bank_branch,

    //             'bank_account_number' => $v->bank_acc_no,
    //             'bank_ifsc' => $v->bank_IFSCcode,
    //             'bank_account_type' => null,
    //             'is_default_bank' => false,
    //             'rtgs_form_view_id' => null,
    //         ]);

    //         AccountTaxDetail::create([
    //             'account_id' => $account->id,
    //             'type_of_dealer' => null,
    //             'filing_frequency' => null,
    //             'gst_number' => null,

    //             'tax_category_id' => null,
    //             'hsn_sac_code' => null,
    //             'itc_eligibility' => null,
    //             'rcm_nature' => null,
    //             'pan' => null,
    //             'tin' => null,
    //             'tax_type' => null,
    //             'gst_type' => null,
    //         ]);

    //         AccountPreference::create([
    //             'account_id' => $account->id,
    //             'transport_mode' => 'road',
    //             'sale_type_id' => null,
    //             'purchase_type_id' => null,
    //             'distance' => 0,

    //             'station' => null,
    //             'contact_person' => null,
    //             'transport' => null,
    //             'purchase_unit_id' => null,
    //             'sale_unit_id' => null,
    //             'purchase_commission_rate' => 0,
    //             'sale_commission_rate' => 0
    //         ]);

    //         $driver =  Driver::create([

    //             'account_id' => $account->id,
    //             // 'vehicle_id',
    //             'company_id'                => $companyId,
    //             'date_of_joining' => $v->date_of_joining,
    //             'license_number' => $v->license_number,
    //             'adhara_number' => $v->aadhaar_number,
    //             'religion' => $v->religion,
    //             'qualification' => $v->qualification,
    //             'marital_status' => null,
    //             'blood_group' => null,
    //             'salary' => $v->salary,

    //             'license_category' => 'cd',
    //             'license_issuing_authority' => null,
    //             'license_expiry_date_tr' => $v->license_expiry_date_tr  == '0000-00-00' ? null : $v->license_expiry_date_tr,
    //             'license_expiry_date_nt' => $v->license_expiry_date_tr  == '0000-00-00' ? null : $v->license_expiry_date_tr,

    //         ]);

    //         $sequenceIncrements[$accountGroupId] = ($sequenceIncrements[$accountGroupId] ?? 0) + 1;

    //         AccountMapping::create([
    //             'old_account_id'       => $a->id,
    //             'new_account_id'       => $account->id,
    //             'company_id'           => $companyId,
    //             'old_account_group_id' => $a->account_head_id,
    //             'new_account_group_id' => $accountGroupId,
    //             'old_account_name'     => $a->account_name,
    //             'account_type'         => $a->account_type,
    //             'created_at'           => now(),
    //             'updated_at'           => now(),
    //         ]);

    //         // DriverMapping::create([
    //         //     'company_id'                => $companyId,
    //         //     'name' => $v->driver_name,
    //         //     'old_driver_id' => $v->id,
    //         //     'new_driver_id' => $driver->account_id
    //         // ]);
    //     }
    // }



    public function getVehicle()
    {
        $companyId = $this->companyId;
        $olVO = DB::connection('old_db')->table('vehicle_details')->where('cc_id', 9)->get()->keyBy('id');

        $accounts = DB::connection('old_db')
            ->table('transport_accounts')
            ->where('account_type', 5)
            ->whereNull('deleted_at')
            ->where('cc_id', 9)
            ->orderBy('id')
            ->get();


        // $vehicleOwner = VehicleOwner::where('company_id', $companyId)->get()->keyBy('id');

        foreach ($accounts as $key => $account) {
            $ol =  $olVO->get($account->account_id);

            if (!$ol) continue;


            // if ($ol->vehicle_number == 'RELEAVER') continue;

            $vehicle = Vehicle::create([
                'uuid'                      => Str::uuid(),
                'company_id'                => $companyId,
                'name' => str_replace('/', '', $ol->vehicle_number),
                // 'license_number'    => null,
                'renewal_date' => $ol->renewal_date == '0000-00-00' ? null : $ol->renewal_date,
                'model' => $ol->vehicle_model,
                'mfg_year' => $ol->mfg_year,
                'manufacturer' => $ol->manufacturer,
                'chassis_no' => $ol->chassis_no,
                'engine_no' => $ol->engine_no,
                'fuel_type' => FuelType::DIESEL,
                'fuel_tank_capacity' => $ol->fuel_tank_capacity,
                'gross_weight' => $ol->vehicle_gross_weight,
                'unladen_weight' => $ol->vehicle_unladen_weight,
                'weight_capacity' => $ol->vehicle_weight_capacity,
                'account_id' => null,
                'vehicle_owner_id' => $ol->vehicle_owner_id,
                'driver_id' => null,

                'national_permit_due_date' => $ol->national_permit_due_date == '0000-00-00' ? null : $ol->national_permit_due_date,
                'fitness_due_date' => $ol->fitness_due_date == '0000-00-00' ? null : $ol->fitness_due_date,
                'policy_due_date' => $ol->policy_due_date == '0000-00-00' ? null : $ol->policy_due_date,
                'passing_due_date' => $ol->passing_due_date == '0000-00-00' ? null : $ol->passing_due_date,
                'tax_due_date' => $ol->tax_due_date == '0000-00-00' ? null : $ol->tax_due_date,
                'permit_due_date' => $ol->permit_due_date == '0000-00-00' ? null : $ol->permit_due_date,
                'puc_no' => $ol->puc_no ?? null,
                'puc_due_date' => $ol->puc_due_date == '0000-00-00' ? null : $ol->puc_due_date,
                'insurance_company_name' => $ol->insurance_company_name ?? null,
                'insurance_policy_no' => $ol->insurance_policy_no ?? null,
                'power_cc' => $ol->vehicle_power_cc ?? null,

            ]);

            // $transportId =  DB::connection('old_db')->table('transport_accounts')->where('cc_id', 9)->where('account_type',5)->where('account_id', $account->id)->value('id');
            // dd($transportId);
            // if(!$transportId){}
            VehicleMapping::create([
                'company_id'                => $companyId,
                'name' => $ol->vehicle_number,
                'old_vehicle_id' => $account->id,
                'new_vehicle_id' => $vehicle->id
            ]);
        }
    }

    private function parseCombinedId(int $uniqueId): array
    {
        $type = intdiv($uniqueId, 1000000);
        $id = $uniqueId % 1000000;

        return ['type' => $type, 'id' => $id];
    }

    private function makeCombinedId(int $type, int $id): int
    {
        return $type * 1000000 + $id;
    }


    public function transportParty()
    {
        $companyId = $this->companyId;

        $skipAccountArray = [
            1 => '2001',
            2 => '2002',
            3 => '2003',
            4 => '2004',
            5 => '2005',
            6 => '2006',
            7 => '2007',
            8 => '2008',
        ];

        // get first accounts
        $accounts = DB::connection('old_db')
            ->table('accounts')
            ->leftJoin('account_details', 'accounts.id', '=', 'account_details.account_id')
            // ->whereIn('accounts.cc_id', [$companyId, 0])
            ->where('accounts.deleted_at', null)
            ->whereIn('accounts.id', array_values($skipAccountArray))

            ->select(
                'accounts.*',
                'account_details.*',   // FIRST include all detail columns
                'accounts.id as account_id',       // then alias accounts
                'accounts.account_name as account_name',
                'account_details.id as detail_id',  // then alias the detail ID
                'account_details.pin_code as pin_code',
            )
            ->selectRaw("'account' as account_type")
            ->orderBy('cc_id', 'asc')
            ->get();

        //get supplier
        $suppliers = DB::connection('old_db')
            ->table('suppliers')
            ->leftJoin('supplier_details', 'suppliers.id', '=', 'supplier_details.supplier_id')
            // ->where('suppliers.cc_id', $companyId)
            ->where('suppliers.deleted_at', null)
            ->select(
                'suppliers.*',
                'supplier_details.*',
                'suppliers.id as account_id',
                'suppliers.supplier_name as account_name',
                'supplier_details.id as supplier_detail_id',
                'supplier_details.pin as pin_code',
                'supplier_details.state as state_id',
                'supplier_details.email as email_id',
                'supplier_details.country as country_id',
            )
            ->selectRaw("'supplier' as account_type")
            ->get();

        //get vendors
        $vendors = DB::connection('old_db')
            ->table('vendors')
            ->leftJoin('vendor_details', 'vendors.id', '=', 'vendor_details.vendor_id')
            // ->where('vendors.cc_id', $companyId)
            ->where('vendors.deleted_at', null)
            ->select(
                'vendors.*',
                'vendor_details.*',
                'vendors.id as account_id',
                'vendors.vendor_name as account_name',
                'vendor_details.id as vendor_detail_id',
                'vendor_details.pin as pin_code',
                'vendor_details.state as state_id',
                'vendor_details.email as email_id',
                'vendor_details.country as country_id',
            )
            ->selectRaw("'customer' as account_type")
            ->get();


        $oldAccounts = array_merge($accounts->toArray(), $suppliers->toArray(), $vendors->toArray());



        $typeMap = ['account' => 3, 'supplier' => 1, 'customer' => 2];

        $now = now();




        // Bulk collect arrays — inserted in one shot after the loop
        $taxDetails       = [];
        $preferences      = [];
        $bankDetails      = [];
        $voucherLines     = [];
        $accountMappings  = [];
        foreach ($oldAccounts as $account) {
            // dd($account);

            $baseName = $account->account_name;
            $suffix = '';
            while (TransportParty::where('name', $baseName . $suffix)->exists()) {
                $suffix .= '.';
            }
            $account->account_name = $baseName . $suffix;

            $party =   TransportParty::create([
                'uuid'             => Str::uuid(),
                'company_id'       => $companyId,
                'name'              => $account->account_name,
                'city'              =>  $account->city,
                'state'             => $account->state_id,
                'mobile_number'    => $account->mobile,
                'postal_code'      => $account->pin_code,
                'gst_number'       => $account->gst_no

            ]);

            $accountType = [
                'account' => 3,
                'supplier' => 1,
                'customer' => 2
            ];
            // dd($account, $account->account_id);

            TransportPartyMapping::create([

                'company_id'       => $companyId,
                'name'         => $party->name,
                'new_id'           => $party->id,
                'old_id'            => $account->account_id,
                'account_type'     => $accountType[$account->account_type]

            ]);
        }




        $transportExtraParties = [
            ['id' => 2063, 'name' => 'SHREE MAHAKALI CORPORATION'],
            ['id' => 2064, 'name' => 'SHREE MAHAKALI AGRO FEED PVT LTD'],
            ['id' => 2065, 'name' => 'AMBICA TRADING COMPANY'],
            ['id' => 2066, 'name' => 'SHREE VINAYAK CORPORATION'],
            ['id' => 2067, 'name' => 'SHREE MAHAKALI FEED TRADE LLP'],
            ['id' => 2068, 'name' => 'SHREE RAM CORPORATION'],
            ['id' => 2069, 'name' => 'SHRI LAXMI AGRO INDUSTRY'],
            ['id' => 2070, 'name' => 'SHREE BHAVANA CORPORATION'],
            ['id' => 2071, 'name' => 'SHREE AMBICA ROADLINES'],

        ];


        foreach ($transportExtraParties as $key => $te) {

            $tei = TransportParty::create([
                'id' => $te['id'],
                'uuid'             => Str::uuid(),
                'company_id'       => $companyId,
                'name'              => $te['name'] . '.',


            ]);

            TransportPartyMapping::create([

                'company_id'       => $companyId,
                'name'         => $te['name'],
                'new_id'           => $tei->id,
                'old_id'            => $te['id'],
                'account_type'     => 3
            ]);
        }
    }
}

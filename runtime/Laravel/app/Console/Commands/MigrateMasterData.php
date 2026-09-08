<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\AccountCodeSequence;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\Broker;
use App\Models\BrokerMapping;
use App\Models\Condition;
use App\Models\ConditionMapping;
use App\Models\DairyParameter;
use App\Models\DairyParameterDetail;
use App\Models\DairyParameterMapping;
use App\Models\Destination;
use App\Models\DestinationMapping;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class MigrateMasterData extends Command
{
    protected $signature = 'app:master {company_id} {financial_year_id}';
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
            $this->getAccounts();
            $this->getPurchaseTypes();
            $this->getSalesTypes();
            $this->getItems();
            $this->getDestination();
            $this->getCondition();
            $this->getGodown();
            $this->getElement();
            $this->getParameter();
            $this->getBroker();
            $this->getTransporter();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getTransporter(){
        $companyId = $this->companyId;
        $olTra = DB::connection('old_db')->table('transporters')->where('c_id', $companyId)->get();
        

        foreach ($olTra as $key => $t) {
            
           $transporter =  Transporter::create([
                 'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,    // pass company ID
                'name' => $t->name,
                'gstin' => $t->gstin,
                'pan_no' => $t->pan_no,
                'contact_person' => $t->contact_person,
                'mobile'  => $t->mobile,
                'phone' => $t->phone,
                'email' => $t->email,
                'address_line1' => $t->address_line1,
                'address_line2'=> $t->address_line2,
                'city' => $t->city,
                'state'=> $t->state, 
                'postal_code' => $t->pincode, 
                'bank_name' => $t->bank_name,
                'bank_account_number' => $t->bank_account_number,
                'bank_ifsc'  => $t->ifsc_code,
            ]);

             TransporterMapping::create([
                'old_transporter_id' => $t->id,
                'new_transporter_id' => $transporter->id,
                'company_id'     => $this->companyId,
                'name' => $transporter->name
            ]);
        }
    }

    public function getBroker(){
        $companyId = $this->companyId;
        $oldBrokers = DB::connection('old_db')->table('brokers')
        ->leftJoin('broker_details', 'broker_details.broker_id', '=', 'brokers.id')
        ->whereIn('cc_id', [$companyId,0])->get();

         foreach ($oldBrokers as $broker) {
         $newBroker = Broker::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,    // pass company ID
            'code' => $broker->id == 1 ? 27000 : 0,
            'name' => $broker->broker_name,
            'print_name' => $broker->broker_name,
            'pan' => $broker->pan_number,
            'mobile_number' => $broker->phone,
            'email' => $broker->email,
            'country_id' => 1,
            'state_id' => $broker->state_id,
            'city' => $broker->city,
            'postal_code' => $broker->pincode,
            'address_one' => $broker->address1,
            'address_two' => $broker->address2,
            'sale_commission_rate' => $broker->sales_commition_rate,
            'purchase_commission_rate' => $broker->purch_commition_rate,
        ]);
            BrokerMapping::create([
                'old_broker_id' => $broker->id,
                'new_broker_id' => $newBroker->id,
                'company_id'     => $this->companyId,
                'name' => $broker->broker_name
            ]);
         }

    }

    public function getElement()
    {
        $oldElements = DB::connection('old_db')->table('elements')->get();
        foreach ($oldElements as $element) {
            $query = [
                // 'id' => $element->id,
                'uuid'        => Str::uuid(),
                'name'        => $element->element_name,
                'print_name'  => $element->element_name,
                'range'       => $element->element_range,
                'company_id'  => $this->companyId,
                'created_by'  => auth()->id(),
                'updated_by'  => null,
                'deleted_by'  => null,
                'status'      => 1,
                'deleted_at'  => null,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ];

            $data = Element::create($query);

            ElementMapping::create([
                'old_element_id' => $element->id,
                'new_element_id' => $data->id,
                'company_id'     => $this->companyId,
                'name' => $element->element_name
            ]);
        }

        return response()->json([
            'message' => 'Old data imported successfully!'
        ]);
    }

    public function getParameter()
    {
        $companyId = $this->companyId;

        $oldParameters = DB::connection('old_db')
            ->table('parameters')
            ->where('cc_id', $companyId)
            // ->where('status', 0)
            ->get();

        $conditionMapping = ConditionMapping::get()->keyBy('old_condition_id');
        $elementMapping = ElementMapping::get()->keyBy('old_element_id');

        foreach ($oldParameters as $parameter) {


            $condition = $conditionMapping[$parameter->da_condition] ?? null;
            $element = $elementMapping[$parameter->da_element] ?? null;

            if (!$condition) continue;
            if (!$element) continue;

            $details = DB::connection('old_db')
                ->table('parameter_details')
                // ->where('status', 0)
                ->where('parameter_id', $parameter->parameter_id)->get();



            $dairy = DairyParameter::create([
                // no manual id
                'uuid' => Str::uuid(),
                'company_id' => $parameter->cc_id,
                'condition_id' => $condition->new_condition_id,
                'element_id' => $element->new_element_id,
                'guarantee' => $parameter->da_guarantee,
                'status' => 1,
                'created_by'  => null,
                'created_at'  => $parameter->created_at,
                'updated_at'  => $parameter->updated_at,
            ]);


            foreach ($details as $dt) {
                DairyParameterDetail::create([
                    'parameter_id' => $dairy->id,
                    'from' => $dt->da_from ?? 0,
                    'to' => $dt->da_to ?? 0,
                    'difference' => $dt->da_difference ?? 0,
                    'rebate' => $dt->da_rebate ?? 0,
                    'premium' => $dt->da_premium ?? 0,
                    'created_at' => null,
                    'updated_at' => null,
                ]);
            }


            //  also set hear detail entry 

            DairyParameterMapping::create([
                'company_id' => $companyId,
                'new_dairy_parameter_id' => $dairy->id,
                'old_dairy_parameter_id' => $parameter->parameter_id,
            ]);
        }
    }

    // Data of godowns table from old database godown_masters
    // getting old table to new table
    public function getGodown()
    {
        $companyId = $this->companyId;
        $oldGodown = DB::connection('old_db')->table('godown_masters')->where('cc_id', $companyId)->get()->keyBy('id');

        $destinationMapping = DestinationMapping::where('company_id', $companyId)->get()->keyBy('old_destination_id');

        // dd($destinationMapping);
        
        
        foreach ($oldGodown as $godown) {
            
                    $isExist = Godown::where('godown_name', $godown->godown_name)->where('company_id', $companyId)->exists();
            
                        if($isExist){
                            $godown->godown_name = $godown->godown_name.'.';
                        }
            $newGodown = Godown::create([
                'uuid'           => Str::uuid(),
                'godown_name'    => $godown->godown_name,
                'destination_id' => $destinationMapping[$godown->destination_id]->new_destination_id ?? null, // Fix here
                'remark'         => $godown->godown_remark,
                'company_id'     => $companyId,
            ]);

            // dd($newGodown->name);
            GodownMapping::create([
                'company_id' => $companyId,
                'old_godown_id' => $godown->id,
                'new_godown_id' => $newGodown->id,
                'name' => $newGodown->godown_name,
            ]);
        }
    }


    public function getCondition()
    {
        $companyId = $this->companyId;
        $conditions = DB::connection('old_db')->table('conditions')->where('deleted_at', null)->get()->keyBy('id');

        foreach ($conditions as $condition) {

            $newCondition =  Condition::create([
                'uuid'        => Str::uuid(),
                'name'        => $condition->condition_name,
                'print_name'  => $condition->condition_name,
                'company_id'  => $companyId,
            ]);

            ConditionMapping::create([
                'company_id' => $companyId,
                'old_condition_id' => $condition->id,
                'new_condition_id' => $newCondition->id,
                'name' => $newCondition->name,
            ]);
        }
    }

    public function getDestination()
    {
        $companyId = $this->companyId;
        $destinations = DB::connection('old_db')
            ->table('destinations')
            // ->where('deleted_at', null)
            ->leftJoin('destination_details', 'destination_details.destination_id', '=', 'destinations.id')
            ->where('destinations.cc_id', $companyId)
            ->select(
                'destinations.*',
                'destination_details.*',
                'destinations.id as destination_id',
                'destination_details.id as detail_id'
            )
            ->get()
            ->keyBy('destination_id');

        // dd($destinations);


        foreach ($destinations as $destination) {
            $newDestination = Destination::create([
                'uuid' => Str::uuid(),
                'name' => $destination->destination_name ?? null,
                'contact_person_name' => $destination->contact_person ?? null,
                'email' => $destination->email ?? null,
                'mobile_number' => $destination->mobile ?? null,
                'phone_number' => $destination->phone_no ?? null,
                'kms' => $destination->kms ?? 5,
                'address_one' => $destination->address1 ?? null,
                'address_two' => $destination->address2 ?? null,
                'city' => $destination->city ?? null,
                'district' => $destination->district ?? null,
                'taluka' => $destination->taluka ?? null,
                'state_id' => $destination->state_id ?? 11,
                'country_id' => $destination->country_id ?? 1,
                'postal_code' => $destination->pincode ?? null,
                'company_id' => $companyId,
            ]);

            DestinationMapping::create([
                'company_id' => $companyId,
                'old_destination_id' => $destination->destination_id,
                'new_destination_id' => $newDestination->id,
                'name' => $newDestination->name,
            ]);
        }
    }

    public function getItems()
    {
        $companyId = $this->companyId;


        // 1) Old company products list
        $currentCompanyProduct = DB::connection('old_db')
            ->table('company_product_entity_map')
            ->where('company_id', $companyId)
            ->pluck('product_id')
            ->toArray();

        // 2) Default type (keyed by product_id)
        $defaultProductType = DB::connection('old_db')
            ->table('default_type_of_products')
            ->whereIn('product_id', $currentCompanyProduct)
            ->where('c_id', $companyId)
            ->get()
            ->keyBy('product_id');

        // 3) Mappings
        $purchaseTypeMapping = PurchaseTypeMapping::where('company_id', $companyId)
            ->get()
            ->keyBy('old_purchase_type_id');

        $salesTypeMapping = SaleTypeMapping::where('company_id', $companyId)
            ->get()
            ->keyBy('old_sale_type_id');

        // 4) Items from OLD DB
        $items = DB::connection('old_db')
            ->table('products')
            ->whereIn('products.id', $currentCompanyProduct)
            ->leftJoin('product_details', 'product_details.product_id', '=', 'products.id')
            // ->where('deleted_at', null)
            ->select(
                'products.*',
                'product_details.*',
                'products.id as product_id',
                'product_details.product_id as detail_id'
            )
            ->get()
            ->keyBy('product_id');
        // dd($items);

        // 5) Units
        $oldUnits = DB::connection('old_db')->table('units')->get()->keyBy('id');
        $newUnits = Unit::where('company_id', $companyId)->get()->keyBy('code');

        // 6) Other dependencies
        $itemGroup = ItemGroup::where('company_id', $companyId)->first();

        $taxCategories = TaxCategory::where('company_id', $companyId)
            ->where('type', 'goods')
            ->get()
            ->keyBy('code');

        // GST mapping
        $gstRate = [
            27 => 1007,
            18 => 1006,
            12 => 1005,
            5  => 1004,
            0  => 1001,
        ];

        // Helper function to get safe mapping
        $safeMap = function ($map, $key, $field) {
            return $map[$key][$field] ?? null;
        };

        foreach ($items as $key => $item) {

            $default = $defaultProductType[$item->product_id] ?? null;

            // Unit resolve
            $oldUnit = $oldUnits[$item->unit_id] ?? null;
            $newUnitId = $oldUnit && isset($newUnits[$oldUnit->code])
                ? $newUnits[$oldUnit->code]->id
                : null;

            // GST resolve
            $gstPercent = ($item->cgst + $item->sgst);
            $taxCategoryId =
                (isset($gstRate[$gstPercent]) &&
                    isset($taxCategories[$gstRate[$gstPercent]]))
                ? $taxCategories[$gstRate[$gstPercent]]->id
                : null;

            // Sale / Purchase type mapping
            $sale_local = $safeMap($salesTypeMapping, $default->sales_type_local_id ?? null, 'new_sale_type_id');
            $sale_inter = $safeMap($salesTypeMapping, $default->sales_type_central_id ?? null, 'new_sale_type_id');

            $purchase_local = $safeMap($purchaseTypeMapping, $default->purchase_type_local_id ?? null, 'new_purchase_type_id');
            $purchase_inter = $safeMap($purchaseTypeMapping, $default->purchase_type_central_id ?? null, 'new_purchase_type_id');

            // Create new item
            $newItem = Item::create([
                'uuid' => Str::uuid(),
                'name' => $item->product_name,
                'sku'  => 'SKU-' . str_pad($key, 5, '0', STR_PAD_LEFT),
                'item_group_id' => $itemGroup->id ?? null,
                'print_name' => $item->product_name,
                'unit_id' => $newUnitId,
                'tax_category_id' => $taxCategoryId,
                'hsn_sac_code' => $item->hsn_code,

                'sale_type_local_id' => $sale_local,
                'sale_type_interstate_id' => $sale_inter,
                'purchase_type_local_id' => $purchase_local,
                'purchase_type_interstate_id' => $purchase_inter,

                'is_maintain_stock_balance' => true,
                'company_id' => $companyId,
            ]);

            // Mapping table entry
            ItemMapping::create([
                'old_item_id' => $item->product_id,
                'new_item_id' => $newItem->id,
                'company_id' => $companyId,
                'name' => $item->product_name,
            ]);
        }
    }



    public function getPurchaseTypes()
    {
        $companyId = $this->companyId;
        $oldPurchaseTypes = DB::connection('old_db')->table('transaction_types')->where('voucher_type', 'purchase')->where('c_id', $companyId)->get()->keyBy('id');

        $accountMappings = AccountMapping::where('company_id', $companyId)->get()->keyBy('old_account_id');

        // dd($oldPurchaseTypes);
        foreach ($oldPurchaseTypes as $key => $type) {

            $tType = $this->getTypes($type->taxation_type);

            $type->account_id = $accountMappings[$type->account_id]->new_account_id;

            $purchaseType = PurchaseType::create([
                'uuid'       => Str::uuid(),
                'company_id' => $companyId,
                'name'       => trim($type->name),
                'account_id' => $type->account_id,
                'region'     => $type->region == 'Local' ? 'local' : 'interstate',
                'taxation_type' => $tType,
                'cgst'       => $type->cgst ?? 0,
                'sgst'       => $type->sgst ?? 0,
                'igst'       => $type->igst ?? 0,
                'is_system'  => true,
                'transaction_type' => $type->type == 'other' ? 'Domestic' : $type->type,
                'status'     => true,
            ]);

            PurchaseTypeMapping::create([
                'company_id' => $companyId,
                'old_purchase_type_id' => $type->id,
                'new_purchase_type_id' => $purchaseType->id,
                'name' => trim($type->name),
            ]);
        }
    }

    public function getSalesTypes()
    {
        $companyId = $this->companyId;
        $oldSaleTypes = DB::connection('old_db')->table('transaction_types')->where('voucher_type', 'Sales')->where('c_id', $companyId)->get()->keyBy('id');

        $accountMappings = AccountMapping::where('company_id', $companyId)->get()->keyBy('old_account_id');

        // dd($oldSaleTypes);
        foreach ($oldSaleTypes as $key => $type) {

            $tType = $this->getTypes($type->taxation_type);

            $type->account_id = $accountMappings[$type->account_id]->new_account_id;

            $saleType = SaleType::create([
                'uuid'       => Str::uuid(),
                'company_id' => $companyId,
                'name'       => trim($type->name),
                'account_id' => $type->account_id,
                'region'     => $type->region == 'Local' ? 'local' : 'interstate',
                'taxation_type' => $tType,
                'cgst'       => $type->cgst ?? 0,
                'sgst'       => $type->sgst ?? 0,
                'igst'       => $type->igst ?? 0,
                'is_system'  => true,
                'transaction_type' => $type->type == 'other' ? 'Domestic' : $type->type,
                'status'     => true,
            ]);

            SaleTypeMapping::create([
                'company_id' => $companyId,
                'old_sale_type_id' => $type->id,
                'new_sale_type_id' => $saleType->id,
                'name' => trim($type->name),
            ]);
        }
    }

    public function getTypes($type)
    {
        switch ($type) {
            case 'Taxable':
                return 'taxable';
            case 'Exempt':
                return 'exempt';
            case 'Nil-Rated':
                return 'nil_rated';
            case 'Zero-Rated':
                return 'zero_rated';
            case 'Non-GST':
                return 'non_gst';
            default:
                return 'taxable';
        }
    }

    public function getAccounts()
    {
        $companyId = $this->companyId;
        $oldAccountGroups = DB::connection('old_db')->table('account_heads')->get()->keyBy('id');

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
        $skipAccountArray = [
            1 => '2001',
            2 => '2002',
            3 => '2003',
            // 4 => '2004',
            5 => '2005',
            6 => '2006',
            7 => '2007',
            7 => '2008',
        ];

        $skipAccountId = $skipAccountArray[$companyId] ?? null;
        // get first accounts
        $accounts = DB::connection('old_db')
            ->table('accounts')
            ->leftJoin('account_details', 'accounts.id', '=', 'account_details.account_id')
            ->whereIn('accounts.cc_id', [$companyId, 0])
            ->where('accounts.deleted_at', null)
            ->when($skipAccountId, function ($query) use ($skipAccountId) {
                return $query->where('accounts.id', '!=', $skipAccountId);
            })
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
            ->where('suppliers.cc_id', $companyId)
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
            ->where('vendors.cc_id', $companyId)
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

        $newAccountGroups = AccountGroup::where('company_id', $companyId)->get()->keyBy('code');



        $openingBalance = DB::connection('old_db')->table('ledger_balances')->where('cc_id', $ccId[$companyId])->where('deleted_at', null)->pluck('opening_balance', 'combined_id')->toArray();

        $offsetAccountId = Account::where('code', '35000')->where('company_id', $companyId)->value('id');

        $financialYearName = 'FY 2026-27';
        $year = substr(str_replace(['-', ' ', 'FY'], '', $financialYearName), -4);

        $lastVoucher = Voucher::where('voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $this->financialYearId)
            ->orderByDesc('voucher_serial')
            ->first();
        $lastSerial = $lastVoucher ? $lastVoucher->voucher_serial : 0;

        $sequenceCodes      = AccountCodeSequence::where('company_id', $companyId)->get()->keyBy('account_group_id');
        $sequenceIncrements = [];

        $isBillWise = fn($code) => $code == 270 || $code == 170;

        $typeMap = ['account' => 3, 'supplier' => 1, 'customer' => 2];

        $now = now();

        // Bulk collect arrays — inserted in one shot after the loop
        $taxDetails       = [];
        $preferences      = [];
        $bankDetails      = [];
        $voucherLines     = [];
        $accountMappings  = [];
        foreach ($oldAccounts as $account) {
            $oldAccountGroupId = $account->account_head_id;
            if (!$oldAccountGroupId) continue;
            $oldAccountGroup = $oldAccountGroups[$oldAccountGroupId];
            if (!isset($newAccountGroups[$oldAccountGroup->code])) {
                continue;
            }
            $newAccountGroup   = $newAccountGroups[$oldAccountGroup->code];
            $newAccountGroupId = $newAccountGroup->id;

            if (!isset($sequenceCodes[$newAccountGroupId])) {
                throw new \Exception("Account code sequence not found for company [$companyId] and group [$newAccountGroupId]");
            }

            $currentCode = $sequenceCodes[$newAccountGroupId]->last_code
                + ($sequenceIncrements[$newAccountGroupId] ?? 0);
            $isExist = Account::where('name', $account->account_name)->where('company_id', $companyId)->exists();
            if($isExist){
                $account->account_name = $account->account_name.'.';
            }

            $accountCreate = Account::create([
                'uuid'             => Str::uuid(),
                'code'             => $currentCode,
                'account_group_id' => $newAccountGroupId,
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
                'is_billwise'      => in_array($account->account_type, ['supplier', 'customer']) && $isBillWise($oldAccountGroup->code),
                'party_type'       => $account->account_type,
                'state_id'         => $account->state_id,
                'country_id'       => 1,
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

            $combinedId  = $typeMap[$account->account_type] * 1000000 + $account->account_id;
            $balanceType = isset($openingBalance[$combinedId]) && $openingBalance[$combinedId] < 0 ? 'c' : 'd';
            $amount      = isset($openingBalance[$combinedId]) ? abs($openingBalance[$combinedId]) : 0;

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

            if ($balanceType === 'd') {
                $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $accountCreate->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now];
                $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance Contra', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now];
            } else {
                $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $offsetAccountId, 'debit' => $amount, 'credit' => 0, 'narration' => 'Opening Balance Contra', 'line_no' => 1, 'created_at' => $now, 'updated_at' => $now];
                $voucherLines[] = ['voucher_id' => $voucher->id, 'account_id' => $accountCreate->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Opening Balance', 'line_no' => 2, 'created_at' => $now, 'updated_at' => $now];
            }

            $accountMappings[] = [
                'old_account_id'       => $account->account_id,
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
}

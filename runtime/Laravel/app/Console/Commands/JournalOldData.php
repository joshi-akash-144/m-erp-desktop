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
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class JournalOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:journal {company_id} {financial_year_id}';

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
        // $itemMaster       = ItemMapping::where('company_id', $companyId)->pluck('new_item_id', 'old_item_id');
        // $conditionMaster  = ConditionMapping::where('company_id', $companyId)->pluck('new_condition_id', 'old_condition_id');
        // $destinationMaster = DestinationMapping::where('company_id', $companyId)->pluck('new_destination_id', 'old_destination_id');
        $accountMaster    = AccountMapping::where('company_id', $companyId)->get();
        // $brokerMaster    = BrokerMapping::where('company_id', $companyId)->pluck('new_broker_id', 'old_broker_id');
        // $transporter    = TransporterMapping::where('company_id', $companyId)->pluck('new_transporter_id', 'old_transporter_id');
        // $godownUnit  = GodownMapping::where('company_id', $companyId)->pluck('new_godown_id', 'old_godown_id');

        // current company's creditor and debtor accounts and its child groups
        $accounts = Account::where('company_id', $companyId)->orderBy('id')->pluck('account_group_id', 'id');


        $group = AccountGroup::where('company_id', $companyId)->pluck('is_party_group', 'id');


        $isPartyGroupIds = [];

        foreach ($accounts as $accountId => $groupId) {
            $isPartyGroupIds[$accountId] = $group[$groupId] ?? 0;
        }





        $ledgerData = DB::connection('old_db')
            ->table('ledger_details')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->where('voucher_type', VoucherType::JOURNAL)
            ->orderBy('drcr_status', 'desc')
            ->get()
            ->groupBy('voucher_id');


        $journalVoucherDetail = DB::connection('old_db')
            ->table('journal_details')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->get()
            ->groupBy('vch_id')->toArray();

        // $voucherWisePaymentDetails = [];
        // foreach ($paymentVoucherDetail as $paymentVoucherId => $details) {
        //     foreach ($details as $detail) {
        //         $voucherWisePaymentDetails[$detail->payment_voucher_id] = $detail->payment_id;
        //     }
        // }

        // $bankGroupIds = AccountGroup::where('company_id', $companyId)
        //     ->where('code', 130)
        //     ->pluck('id')
        //     ->toArray();

        // $bankIds = Account::where('company_id', $companyId)
        //     ->whereIn('account_group_id', $bankGroupIds)
        //     ->pluck('id')
        //     ->toArray();

        $ref = DB::connection('old_db')
            ->table('voucher_transaction_details')->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')->where('voucher_type', VoucherType::JOURNAL)->get()->groupBy('voucher_number');


        DB::connection('old_db')
            ->table('journals')
            ->where('cc_id', $ccId[$companyId])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($journals) use (
                $companyId,
                $financialYearId,
                $accountMaster,
                $type,
                $ledgerData,
                $isPartyGroupIds,
                $journalVoucherDetail,
                $ref
            ) {
                foreach ($journals as $pv) {
                    $firstVoucherDetail = $journalVoucherDetail[$pv->voucher_no][0] ?? null;

                    $voucher = Voucher::create([
                        'uuid'              => Str::uuid(),
                        'company_id'        => $companyId,
                        'financial_year_id' => $financialYearId,
                        'voucher_type_id'   => VoucherType::JOURNAL,
                        'voucher_serial'    => $pv->voucher_no,
                        'voucher_number'    => $pv->voucher_no,
                        'voucher_date'      => $firstVoucherDetail->journal_date,
                        'reference_number'  => null,
                        'source_type'       => SourceType::JOURNAL,
                        'narration'         => $pv->narration
                    ]);

                    $voucherLine = [];
                    foreach ($ledgerData[$pv->voucher_no]  as $line => $ldData) {
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

                        if ($ldData->drcr_status == 1) {
                            $credit = $ldData->amount;
                        }
                        if ($ldData->drcr_status == 2) {
                            $debit = $ldData->amount;
                        }

                        $voucherLine[] = [
                            'voucher_id' => $voucher->id,
                            'account_id' => $ldAccountId,
                            'debit'  => $debit,
                            'credit' => $credit,
                            'narration' => null,
                            'is_party_account' => $isPartyGroupIds[$ldAccountId] ?? false,
                            'line_no' => $line + 1,
                            'against_account_id' => $oppositeAccount
                        ];
                    }
                    VoucherTransaction::insert($voucherLine);

                    $journal = JournalVoucher::create([
                        'voucher_id' => $voucher->id,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'gst_nature' => 'gst_not_applicable'
                    ]);


                    $voucher->update([
                        'source_id' => $journal->id
                    ]);


                    $references = $ref[$pv->voucher_no] ?? null;

                    if ($references == null) {
                        continue;

                    }

                    foreach ($references as $oldRef) {
                        $accountId = null;
                        foreach ($accountMaster as $key => $am) {
                            if ($am->old_account_id == $oldRef->account_id && $am->account_type == $type[$oldRef->account_type]) {
                                $accountId = $am->new_account_id;
                                break;
                            }
                        }
                        // Here you can create entries in the Reference and ReferenceItem tables using the $oldRef data
                        // Make sure to map the old reference data to the new structure as needed


                        Reference::create([
                            'company_id' => $companyId,
                            'financial_year_id' => $financialYearId,
                            'account_id' => $accountId, // Map this from $oldRef if needed
                            'reference_number' => $oldRef->ref_no,
                            'reference_date' => $oldRef->ref_date,
                            'file_number' => $oldRef->file_no,
                            'reference_type' => Reference::NewReference, // Map this from $oldRef if needed
                            'amount' => $oldRef->pay_amount,
                            'settled_amount' => 0,
                            'pending_amount' => $oldRef->pay_amount,
                            'is_hold' => $oldRef->is_hold,
                            'is_closed' => false,
                            'closed_at' => null,
                            'voucher_id' => $voucher->id, // Link to the newly created voucher
                            'source_id' => $journal->id, // Set this if you have a source ID in the old reference data
                            'direction' => $oldRef->payment_mode == 2 ? 'debit' : 'credit', // Map this from $oldRef if needed
                        ]);
                    }
                }
            });
    }
}

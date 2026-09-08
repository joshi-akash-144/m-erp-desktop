<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
use App\Models\CompanyGstCredential;
use App\Models\CompanyMailConfig;
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
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\Transporter;
use App\Models\TransporterMapping;
use App\Models\TransportJournalMapping;
use App\Models\User;
use App\Models\VehicleMapping;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GSTCred extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gst-cred';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'User Data Insert';

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
        $companies = DB::connection('old_db')
            ->table('company_masters')
            ->where('id', '<=', 8)
            ->get();

        foreach ($companies as $c) {
            // e-Invoice credentials
            CompanyGstCredential::firstOrCreate(
                ['company_id' => $c->id, 'type' => 'e_invoice'],
                [
                    'sandbox_client_id'    => $c->sandbox_client_id,
                    'sandbox_secret_id'    => $c->sandbox_client_secret_id,
                    'sandbox_gstin'        => $c->sandbox_gstin,
                    'sandbox_email'        => $c->sandbox_email,
                    'sandbox_username'     => $c->sandbox_username,
                    'sandbox_password'     => $c->sandbox_password,
                    'production_client_id' => $c->production_client_id,
                    'production_secret_id' => $c->production_client_secret_id,
                    'production_gstin'     => $c->production_gstin,
                    'production_email'     => $c->production_email,
                    'sandbox_base_url'      => 'https://sandbox.example.com/api',
                    'production_base_url'   => 'https://api.example.com/api',
                    'production_username'  => $c->production_username,
                    'production_password'  => $c->production_password,
                ]
            );

            // E-Way Bill credentials
            CompanyGstCredential::firstOrCreate(
                ['company_id' => $c->id, 'type' => 'eway_bill'],
                [
                    'sandbox_client_id'    => $c->sandbox_ewaybill_client_id,
                    'sandbox_secret_id'    => $c->sandbox_ewaybill_client_secret,
                    'sandbox_gstin'        => $c->sandbox_ewaybill_gstin,
                    'sandbox_email'        => $c->sandbox_email,
                    'sandbox_username'     => $c->sandbox_ewaybill_username,
                    'sandbox_password'     => $c->sandbox_ewaybill_password,
                    'production_client_id' => $c->production_ewaybill_client_id,
                    'production_secret_id' => $c->production_ewaybill_client_secret,
                    'production_gstin'     => $c->production_ewaybill_gstin,
                    'sandbox_base_url'      => 'https://sandbox.example.com/api',
                    'production_base_url'   => 'https://api.example.com/api',

                    'production_username'  => $c->production_ewaybill_username,
                    'production_email'     => $c->production_email,

                    'production_password'  => $c->production_ewaybill_password,
                ]
            );
        }
    }
}

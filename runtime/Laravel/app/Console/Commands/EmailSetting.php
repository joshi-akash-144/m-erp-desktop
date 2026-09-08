<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountMapping;
use App\Models\BrokerMapping;
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

class EmailSetting extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mail';

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

    public function setOldData() {
            $mail = DB::connection('old_db')
            ->table('company_mail_settings')->get();

            foreach ($mail as $key => $v) {
                if($v->company_id > 8) continue;
                if($v->type == 'normal')
                  CompanyMailConfig::create([
                     'company_id' => $v->company_id,
                    'host' => $v->host,
                    'port' => $v->port,
                    'encryption' => $v->encryption ?? 'tls',
                    'username' => $v->username,
                    'password' => $v->password,
                    'from_address' => $v->from_address,
                    'from_name' => $v->from_name,
                    'is_active' => true,
                    
            ]);
            }

          

            
            

    }
}



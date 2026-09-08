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
use Hash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OldUserData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user';

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
            $users = [
                ['name' => 'Amrutbhai', 'email' => 'amrut@gmail.com', 'password' => '^()AP$%^*@#'],
                ['name' => 'Chintanbhai', 'email' => 'cp@gmail.com', 'password' => '@#^^CP&*#'],
                ['name' => 'Jagdishbhai', 'email' => 'jap@gmail.com', 'password' => 't27q$%de)+7-'],
                ['name' => 'Rameshbhai', 'email' => 'rt@gmail.com', 'password' => '_strT4>$*_1'],
                ['name' => 'Sudhirbhai', 'email' => 'sp@gmail.com', 'password' => '};#%0z![p0'],
                ['name' => 'Bhaveshbhai', 'email' => 'bhavesh@gmail.com', 'password' => '$%B6P3[]@!4^'],
                ['name' => 'Payment', 'email' => 'payment@gmail.com', 'password' => '9n##uh(^e3x0'],
                ['name' => 'Analysis', 'email' => 'analysis@gmail.com', 'password' => 'u@8i,%7j>'],
                ['name' => 'Ashokbhai', 'email' => 'ashok@gmail.com', 'password' => '!G?H9-.YH'],
                ['name' => 'Viravada', 'email' => 'cp1@gmail.com', 'password' => '-t1tcVMEu'],
                ['name' => 'Kankrol', 'email' => 'cp2@gmail.com', 'password' => 'B.p))!58C'],
                ['name' => 'Kankrol Lab', 'email' => 'glab2@gmail.com', 'password' => '}xf7CxjJ!'],
                ['name' => 'Gumansinh', 'email' => 'gc@gmail.com', 'password' => '-=u7ZAp:1'],
                ['name' => 'Godown', 'email' => 'godown@gmail.com', 'password' => 'RFUh$%g9E=x'],
                ['name' => 'Viravada Lab', 'email' => 'glab@gmail.com', 'password' => '^6ZUg_EYV'],
                ['name' => 'Hajikaka', 'email' => 'hk@gmail.com', 'password' => 'JQ,Fv3G2K'],
                ['name' => 'Harshvardhansinh', 'email' => 'harsh@gmail.com', 'password' => ']4}1G@up)'],
                ['name' => 'Hasimbhai', 'email' => 'hasim@gmail.com', 'password' => 'Bi}H*dj0D'],
                ['name' => 'Hirenbhai', 'email' => 'hiren@gmail.com', 'password' => 'j3}:rV}}v'],
                ['name' => 'Jagudan', 'email' => 'jagudan@gmail.com', 'password' => '>wJs7QF!}'],
                ['name' => 'Katarva', 'email' => 'katarva@gmail.com', 'password' => 'E)9@8UaXj'],
                ['name' => 'MobileGrn', 'email' => 'mobile@gmail.com', 'password' => '8C1wm+FG9'],
                ['name' => 'Narendrasinh', 'email' => 'nj@gmail.com', 'password' => 'nJ3@#n7x>9'],
                ['name' => 'Palanpur', 'email' => 'palanpur@gmail.com', 'password' => '41-b%EG^v'],
                ['name' => 'Purchase', 'email' => 'purchase@gmail.com', 'password' => 'MM?Cv+Q-5'],
                ['name' => 'Sabardan', 'email' => 'sabardan@gmail.com', 'password' => ':bP6Uu7s)'],
                ['name' => 'Sales', 'email' => 'sales@gmail.com', 'password' => '@Ty--a#43r2'],
                ['name' => 'Ubkhal', 'email' => 'ubkhal@gmail.com', 'password' => 'nGu7e_}6>'],
                ['name' => 'Vijaybhai', 'email' => 'vijay@gmail.com', 'password' => '*tf4*Q5rv'],
                ['name' => 'Viravada.', 'email' => 'viravada@gmail.com', 'password' => 'gmCC9+Tn}'],
                ['name' => 'ViravadaLab', 'email' => 'vlab@gmail.com', 'password' => 'Fo)?~?=K5'],
                ['name' => 'Mkc', 'email' => 'mkc@gmail.com', 'password' => '$%^M%$K^)@']
            ];
            $this->setOldData($users);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function setOldData($users)
    {
        // $users = DB::connection('old_db')
        //     ->table('users')->get();

        foreach ($users as $user) {
            $username = $user['name'];


            $newUser = User::updateOrCreate(
                ['username' => $username],
                [
                    'uuid'        => (string) Str::uuid(),
                    'name'        => $user['name'],
                    'email'       => $user['email'],
                    'password'    => Hash::make($user['password']),
                    'status'      => true,
                    'is_approved' => true,
                ]
            );

            if ($newUser->wasRecentlyCreated) {
                $newUser->uuid = Str::uuid();
                $newUser->save();
            }

            // if (!$newUser->hasRole($adminRole)) {
            //     $newUser->assignRole($adminRole);
            // }
        }
    }
}

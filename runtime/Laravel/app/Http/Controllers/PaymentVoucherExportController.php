<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use App\Models\Reference;

class PaymentVoucherExportController extends Controller
{
    
    public function print(Request $request){
       
        return view('company.pages.payment-voucher-register.print', $data);
    }



}
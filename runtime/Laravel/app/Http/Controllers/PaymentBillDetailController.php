<?php

namespace App\Http\Controllers;

use App\Models\PaymentVoucher;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Services\PaymentVoucherService;

class PaymentBillDetailController extends Controller
{
    protected MasterDataService $masterDataService;
    protected PaymentVoucherService $paymentVoucherService;

    public function __construct(MasterDataService $masterDataService, PaymentVoucherService $paymentVoucherService)
    {
        $this->middleware('permission:payment_hold.list')->only(['index']);
        $this->middleware('permission:payment_hold.update')->only('hold');

        $this->masterDataService = $masterDataService;
        $this->paymentVoucherService = $paymentVoucherService;
    }

    public function show(Request $request) {
        $paymentVoucher = PaymentVoucher::findOrFail($request->id);
        
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $paymentVoucherIds = [$paymentVoucher->id];

        $data = $this->paymentVoucherService->printPaymentAdvice($paymentVoucherIds, $companyId, $financialYearId);
        $voucherData = $data['voucher'][$paymentVoucher->id] ?? null;

        return response()->json([
            'html' => view('company.pages.payment-approve._bill-detail', compact('paymentVoucher', 'voucherData'))->render()
        ]);
    }
}

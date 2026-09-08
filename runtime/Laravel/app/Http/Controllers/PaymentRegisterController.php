<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Services\MasterDataService;
use App\Services\PaymentVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRegisterController extends Controller
{
    protected MasterDataService $masterDataService;
    protected PaymentVoucherService $paymentVoucherService;

    public function __construct(MasterDataService $masterDataService, PaymentVoucherService $paymentVoucherService)
    {
        $this->masterDataService     = $masterDataService;
        $this->paymentVoucherService = $paymentVoucherService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',
                'voucher_no',
                'file_number',
                'cheque_number',
                'with_deleted',
            ]);
            $page = (int) $request->input('page', 1);
            $size = (int) $request->input('size', 100);

            $result = $this->paymentVoucherService->getPaymentRegisterList(
                company_id(),
                financial_year_id(),
                $filters,
                $page,
                $size
            );

            return response()->json([
                'data'        => $result['data'],
                'total'       => $result['total'],
                'last_page'   => $result['last_page'],
                'permissions' => $result['permissions'],
            ]);
        }

        $vouchers = $this->paymentVoucherService->voucherSerials(company_id(), financial_year_id());
        $accounts = $this->masterDataService->get('accounts', company_id(), ['id', 'name', 'city']);
        return view('company.pages.payment-register.index', compact('accounts', 'vouchers'));
    }

    public function print(Request $request): JsonResponse
    {
        $filters = $request->only([
            'start_date',
            'end_date',
            'account_id',
            'voucher_no',
            'file_number',
            'cheque_number',
            'with_deleted',
        ]);

        $result = $this->paymentVoucherService->getPaymentRegisterList(
            company_id(),
            financial_year_id(),
            $filters,
            1,
            10000
        );

        if (empty($result['data']) || $result['data']->isEmpty()) {
            return AjaxResponse::error('No records found for the given filters.');
        }

        $company = Company::find(company_id());
        $html = view('company.pages.payment-register.print', [
            'rows'    => $result['data'],
            'filters' => $filters,
            'company' => $company,
        ])->render();

        return AjaxResponse::success('Register printed successfully.', ['html' => $html]);
    }
}

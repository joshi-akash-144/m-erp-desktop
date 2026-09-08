<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Services\MasterDataService;
use App\Services\ReceiptVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptRegisterController extends Controller
{
    protected MasterDataService $masterDataService;
    protected ReceiptVoucherService $receiptVoucherService;

    public function __construct(MasterDataService $masterDataService, ReceiptVoucherService $receiptVoucherService)
    {
        $this->masterDataService     = $masterDataService;
        $this->receiptVoucherService = $receiptVoucherService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only(['start_date', 'end_date', 'account_id','voucher_no']);
            $page    = (int) $request->input('page', 1);
            $size    = (int) $request->input('size', 100);

            $result = $this->receiptVoucherService->getReceiptRegisterList(
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

        $vouchers = $this->receiptVoucherService->voucherSerials(company_id(), financial_year_id());
        $accounts = $this->masterDataService->get('accounts', company_id());
        return view('company.pages.receipt-register.index', compact('accounts', 'vouchers'));
    }

    public function print(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date', 'account_id', 'voucher_no']);

        $result = $this->receiptVoucherService->getReceiptRegisterList(
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
        $html    = view('company.pages.receipt-register.print', [
            'rows'    => $result['data'],
            'filters' => $filters,
            'company' => $company,
        ])->render();

        return AjaxResponse::success('Register printed successfully.', ['html' => $html]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\VoucherType;
use App\Services\MasterDataService;
use App\Services\PaymentPayableService;
use App\Services\ReferenceService;
use App\Services\VoucherService;
use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentReceivableController extends Controller
{
    protected MasterDataService $masterDataService;
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;

    public function __construct(MasterDataService $masterDataService, VoucherService $voucherService, ReferenceService $referenceService)
    {
        $this->middleware('permission:payment_receivable.list')->only(['index']);
        $this->middleware('permission:payment_receivable.create')->only(['create', 'store']);
        $this->middleware('permission:payment_receivable.update')->only(['edit', 'update']);
        $this->middleware('permission:payment_receivable.delete')->only('destroy');
        $this->middleware('permission:payment_receivable.restore')->only('restore');

        $this->masterDataService = $masterDataService;
        $this->voucherService = $voucherService;
        $this->referenceService = $referenceService;
    }


    public function create(Request $request)
    {
        $receiptVoucherSerial = $this->voucherService->getNextVoucherNumber(VoucherType::RECEIPT, company_id(), financial_year_id())->serial;

        $customers       = $this->masterDataService->getCreditorAndDebtor(company_id());
        $ledgerAccounts  = $this->masterDataService->get('accounts', company_id(), ['id', 'code', 'name']);

        return view('company.pages.payment-receivable.index', compact('customers', 'receiptVoucherSerial', 'ledgerAccounts'));
    }

    public function store(Request $request)
    {
        try {
            $receivable = $request->all();
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $response = $this->referenceService->settlementByReceivable($companyId, $financialYearId, $receivable);

            if ($response['status'] === true) {
                return AjaxResponse::success(
                    message: $response['message'] ?? "Payment Saved Successfully",
                    data: $response,
                    code: 200
                );
            }

            return AjaxResponse::error(
                message: $response['message'] ?? "Something went wrong",
                code: $response['code'] ?? 400
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: "Something went wrong",
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\PaymentVoucher;
use App\Services\MasterDataService;
use App\Services\PaymentVoucherService;
use App\Services\RtgsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class PaymentAdviceController extends Controller
{
    protected MasterDataService $masterDataService;
    protected PaymentVoucherService $paymentVoucherService;

    public function __construct(MasterDataService $masterDataService, PaymentVoucherService $paymentVoucherService)
    {
        // $this->middleware('permission:payment_advice.list')->only(['index', 'fetchPending']);
        $this->masterDataService     = $masterDataService;
        $this->paymentVoucherService = $paymentVoucherService;
    }

    public function index()
    {
        $banks = $this->masterDataService->banks(company_id());
        return view('company.pages.payment-advice.index', compact('banks'));
    }

    public function fetchPending(Request $request)
    {
        $validated = $request->validate([
            'date'       => 'required|date_format:Y-m-d',
            'bank_id'    => 'required|integer|exists:accounts,id',
        ]);
        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $filters = [
                'date'        => $validated['date'],
                'bank_id'     => $validated['bank_id'] ?? null,
            ];

            $records = $this->paymentVoucherService->fetchPendingPaymentForRTGSScreen($companyId, $financialYearId, $filters);

            $html    = view('company.pages.payment-online-rtgs._pending-list', compact('records'))->render();

            return AjaxResponse::success('Data fetched successfully.',
             data: [
                    'html' => $html,
                    'records' => $records
             ]);
        } catch (Throwable $e) {
            dd($e);
            report($e);
            return AjaxResponse::error('Failed to load RTGS data.');
        }
    }


    public function printCheque(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
            'bank_id'               => 'required|integer|exists:accounts,id',
            'cheque_no'             => 'required|string|max:100',
            'date'                  => 'required|date_format:Y-m-d',
        ]);

        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $chequeData = $this->paymentVoucherService->getChequePrintDataForRTGS(
                $validated['payment_voucher_ids'],
                $companyId,
                $financialYearId
            );

            return AjaxResponse::success('Cheque data fetched successfully.', data: ['cheque_print_html' => $chequeData]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to fetch cheque data: ' . $e->getMessage());
        }
    }

    public function printRegister(Request $request)
    {
        $validated = $request->validate([
            'bank_id'               => 'required|integer|exists:accounts,id',
            'cheque_no'             => 'nullable|string|max:100',
            'date'                  => 'required|date_format:Y-m-d',
        ]);

        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $registerData = $this->paymentVoucherService->getPaymentRegisterPrint(
                $validated,
                $companyId,
                $financialYearId
            );


            if (empty($registerData['html'])) {
                return AjaxResponse::error('No payment data found with the given criteria. Please check the cheque details.');
            }

            return AjaxResponse::success('Register data fetched successfully.', data: ['register_print_html' => $registerData['html']]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to fetch register data: ' . $e->getMessage());
        }
    }

    public function printPaymentAdvice(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
        ]);

        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $data = $this->paymentVoucherService->printPaymentAdvice(
                $validated['payment_voucher_ids'],
                $companyId,
                $financialYearId
            );

            if (empty($data) || empty($data['voucher'])) {
                return AjaxResponse::error('No payment advice data found for the selected records.');
            }

            $html = view('company.pages.payment-online-rtgs.payment-advice-print', compact('data'))->render();

            return AjaxResponse::success('Payment advice prepared.', data: ['html' => $html]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to prepare payment advice: ' . $e->getMessage());
        }
    }

    public function printRtgs(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
            'bank_id'               => 'required|integer|exists:accounts,id',
            'cheque_no'             => 'required|string|max:100',
            'date'                  => 'required|date_format:Y-m-d',
        ]);

        try {
            $companyId       = company_id();

            // Build RTGS print data
            $rtgsService = new RtgsService();
            $rtgsData = $rtgsService->buildPrintData($validated['payment_voucher_ids'], $companyId);
            // dd($rtgsData['html']);

            if (empty($rtgsData['html'])) {
                return AjaxResponse::error('No RTGS print data found.');
            }

            return AjaxResponse::success('RTGS print data fetched successfully.', data: ['html' => $rtgsData['html']]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to fetch RTGS print data: ' . $e->getMessage());
        }
    }


    public function fetchByCheque(Request $request)
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:100',
            'bank_id'   => 'required|integer|exists:accounts,id',
            'date'      => 'required|date_format:Y-m-d',
        ]);

        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $records = $this->paymentVoucherService->fetchVouchersByChequeNo(
                $validated['cheque_no'],
                $validated['bank_id'],
                $validated['date'],
                $companyId,
                $financialYearId
            );

            if (empty($records)) {
                return AjaxResponse::error('No records found for cheque no. ' . $validated['cheque_no'] . '.');
            }

            $html = view('company.pages.payment-online-rtgs._pending-list', compact('records'))->render();

            return AjaxResponse::success('Cheque data loaded successfully.', data: [
                'html'      => $html,
                'cheque_no' => $validated['cheque_no'],
            ]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to load cheque data: ' . $e->getMessage());
        }
    }
}

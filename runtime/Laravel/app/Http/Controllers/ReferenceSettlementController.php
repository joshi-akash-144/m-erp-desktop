<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\ReferenceService;
use Illuminate\Http\Request;

class ReferenceSettlementController extends Controller
{
    protected ReferenceService $refService;

    public function __construct(ReferenceService $service)
    {
        $this->refService = $service;
    }



    // 🔹 Payment Payable Screen
    public function payables(Request $request)
    {
        // validate request
        $request->validate([
            'payment_voucher_date'  => 'date_format:Y-m-d',
            'account_id'            => 'nullable|exists:accounts,id',
        ]);

        try {
             $filter = [
                'company_id'            => company_id(),
                'financial_year_id'     => financial_year_id(),
                'account_id'            => $request->account_id ?? null,
                'file_number'           => $request->file_number ?? null,
                'on_advance'            => $request->on_advance ?? null,
                'filter_by'             => $request->filter_by ?? null, // cr,dr(cr account dr value in ledger),all(advance + and cr account dr value in ledger),partial(only cr)
                'payment_voucher_date'  => $request->payment_voucher_date,
                'reference_ids'         => $request->reference_ids ?? []
        ];


            $paymentDate = $request->payment_voucher_date ?? date('Y-m-d');
            $data = $this->refService->getPayable($filter);
            $yearStartDate = financial_year_start();

        // Load Html of payment payable table based on filter and return
        $html = view('company.pages.payment-payable._transaction', compact('paymentDate','data','yearStartDate'))->render(); 
        
                return AjaxResponse::success(
                message: "Data Fetched Successfully",
                data: [
                    'reference' => $data,
                    'html' => $html,
                    'unique_request_id' => uuid()
                ],
                code: 200
            );
        } catch (\Throwable $th) {
            return AjaxResponse::error(
                message: $th->getMessage(),
                code: 500
            );
        }
       
    }

    public function receivables(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        try {
            $filter = [
                'company_id'        => company_id(),
                'financial_year_id' => financial_year_id(),
                'account_id'        => $request->account_id ?? null,
            ];

            $data = $this->refService->getReceivables($filter);

            $paymentDate = date('Y-m-d');
            $html = view('company.pages.payment-receivable._transaction', compact('paymentDate', 'data'))->render();

            return AjaxResponse::success(
                message: "Data Fetched Successfully",
                data: [
                    'reference'         => $data,
                    'html'              => $html,
                    'unique_request_id' => uuid()
                ],
                code: 200
            );
        } catch (\Throwable $th) {
            return AjaxResponse::error(
                message: $th->getMessage(),
                code: 500
            );
        }
    }

    public function pending(Request $request){
         $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

         try {
            $filter = [
                'company_id'        => company_id(),
                'financial_year_id' => financial_year_id(),
                'account_id'        => $request->account_id ?? null,
            ];

            $data = $this->refService->getPendingRef($filter);

            $paymentDate = date('Y-m-d');

            return AjaxResponse::success(
                message: "Data Fetched Successfully",
                data: [
                    'reference'         => $data,
                ],
                code: 200
            );
        } catch (\Throwable $th) {
            return AjaxResponse::error(
                message: $th->getMessage(),
                code: 500
            );
        }
    }

    public function store() {}

    // // 🔹 Payment Receivable Screen
    // public function receivables($customerId)
    // {
    //     return $this->refService->getReceivables($customerId);
    // }

    // // 🔹 Common bulk settlement API
    // public function bulkSettle(Request $req)
    // {
    //     return $this->refService->settleReferences(
    //         $req->settlements
    //     );
    // }
}
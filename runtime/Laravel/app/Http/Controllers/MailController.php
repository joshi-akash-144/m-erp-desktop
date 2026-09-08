<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class MailController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /*--------------------------------------------------------------
    | RTGS — Preview (Step 1)
    | Resolves the email template, generates the Excel attachment,
    | and returns all data needed to populate the compose modal.
    | POST /mail/rtgs-email-preview
    --------------------------------------------------------------*/
    public function previewRtgsToBankEmail(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
            'bank_id'               => 'required|integer|exists:accounts,id',
            'cheque_no'             => 'required|string|max:100',
            'date'                  => 'required|date_format:Y-m-d',
        ]);

        try {
            $data = $this->mailService->previewRtgsToBankEmail(
                $validated['payment_voucher_ids'],
                (int) $validated['bank_id'],
                $validated['cheque_no'],
                company_id(),
                financial_year_id()
            );

            return AjaxResponse::success('Preview ready.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

    /*--------------------------------------------------------------
    | Payment Advice — Preview (Step 1)
    | Groups vouchers by party, validates emails + template.
    | POST /mail/payment-advice-email-preview
    --------------------------------------------------------------*/
    public function previewPaymentAdviceEmail(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
        ]);

        try {
            $data = $this->mailService->previewPaymentAdviceEmail(
                $validated['payment_voucher_ids'],
                company_id(),
                financial_year_id()
            );

            return AjaxResponse::success('Preview ready.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

    /*--------------------------------------------------------------
    | Payment Advice — Send to single party (Step 2, compose modal)
    | POST /mail/payment-advice-email-send
    --------------------------------------------------------------*/
    public function sendPaymentAdviceEmail(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
            'to_email'              => 'required|email|max:255',
            'cc_emails'             => 'nullable|array',
            'cc_emails.*'           => 'email|max:255',
            'subject'               => 'required|string|max:500',
            'body'                  => 'required|string',
        ]);

        try {
            $result = $this->mailService->sendPaymentAdviceEmail(
                $validated['payment_voucher_ids'],
                company_id(),
                financial_year_id(),
                $validated['to_email'],
                $validated['cc_emails'] ?? [],
                $validated['subject'],
                $validated['body']
            );

            return AjaxResponse::success('Payment advice email sent successfully.', data: $result);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

    /*--------------------------------------------------------------
    | Payment Advice — Bulk send (one email per party)
    | POST /mail/payment-advice-email-bulk-send
    --------------------------------------------------------------*/
    public function sendPaymentAdviceBulkEmail(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
        ]);

        try {
            $result = $this->mailService->sendPaymentAdviceBulkEmail(
                $validated['payment_voucher_ids'],
                company_id(),
                financial_year_id()
            );

            return AjaxResponse::success('Bulk send completed.', data: $result);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

    /*--------------------------------------------------------------
    | RTGS — Send to Bank Email (Step 2)
    | User has reviewed and confirmed in the compose modal.
    | Sends with the pre-generated Excel, custom subject/body, and CC.
    | POST /mail/rtgs-send-to-bank
    --------------------------------------------------------------*/
    public function sendRtgsToBankEmail(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
            'bank_id'               => 'required|integer|exists:accounts,id',
            'cheque_no'             => 'required|string|max:100',
            'date'                  => 'required|date_format:Y-m-d',
            'to_email'              => 'required|email|max:255',
            'cc_emails'             => 'nullable|array',
            'cc_emails.*'           => 'email|max:255',
            'subject'               => 'required|string|max:500',
            'body'                  => 'required|string',
            'excel_filename'        => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->mailService->sendRtgsToBankEmail(
                $validated['payment_voucher_ids'],
                (int) $validated['bank_id'],
                $validated['cheque_no'],
                company_id(),
                financial_year_id(),
                $validated['to_email'],
                $validated['cc_emails'] ?? [],
                $validated['subject'],
                $validated['body'],
                $validated['excel_filename'] ?? ''
            );

            return AjaxResponse::success('Email sent to bank successfully.', data: $result);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

    public function previewDairyAnalysisEmail(Request $request){

        $validated = $request->validate([
            'dairy_analysis_ids'   => 'required|array|min:1',
        ]);
        try {
            $data = $this->mailService->previewDairyAnalysisEmail(
                $validated['dairy_analysis_ids'],
                company_id(),
                financial_year_id()
            );
            return AjaxResponse::success('Preview ready.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }
    public function sendDairyAnalysisEmail(Request $request){
        $validated = $request->validate([
            'dairy_analysis_ids'   => 'required|array|min:1',
            'dairy_analysis_ids.*' => 'required|integer',
            'to_email'             => 'required|email|max:255',
            'cc_emails'            => 'nullable|array',
            'cc_emails.*'          => 'email|max:255',
            'subject'              => 'required|string|max:500',
            'body'                 => 'required|string',
        ]);
        try {
            $data = $this->mailService->sendDairyAnalysisEmail(
                $validated['dairy_analysis_ids'],
                company_id(),
                financial_year_id(),
                $validated['to_email'],
                $validated['cc_emails'] ?? [],
                $validated['subject'],
                $validated['body']
            );
            return AjaxResponse::success('Email sent successfully.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }

   public function previewGodownAnalysisEmail(Request $request){

        $validated = $request->validate([
            'godown_analysis_ids'   => 'required|array|min:1',
        ]);
        try {
            $data = $this->mailService->previewGodownAnalysisEmail(
                $validated['godown_analysis_ids'],
                company_id(),
                financial_year_id()
            );
            return AjaxResponse::success('Preview ready.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }
    public function sendGodownAnalysisEmail(Request $request){
        $validated = $request->validate([
            'godown_analysis_ids'   => 'required|array|min:1',
            'godown_analysis_ids.*' => 'required|integer',
            'to_email'             => 'required|email|max:255',
            'cc_emails'            => 'nullable|array',
            'cc_emails.*'          => 'email|max:255',
            'subject'              => 'required|string|max:500',
            'body'                 => 'required|string',
        ]);
        try {
            $data = $this->mailService->sendGodownAnalysisEmail(
                $validated['godown_analysis_ids'],
                company_id(),
                financial_year_id(),
                $validated['to_email'],
                $validated['cc_emails'] ?? [],
                $validated['subject'],
                $validated['body']
            );
            return AjaxResponse::success('Email sent successfully.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }
    //TODO : select multiple purchase order where same account then pass in one email . 
    public function previewUrgentPurchaseOrderEmail(Request $request){
        $validated = $request->validate([
            'purchase_order_ids'   => 'required|array|min:1',
        ]);
        try {
            $data = $this->mailService->previewUrgentPurchaseOrderEmail(
                $validated['purchase_order_ids'],
                company_id(),
                financial_year_id()
            );
            return AjaxResponse::success('Preview ready.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }
    
    public function sendUrgentPurchaseOrderEmail(Request $request){
        $validated = $request->validate([
            'purchase_order_ids'   => 'required|array|min:1',
            'purchase_order_ids.*' => 'required|integer',
            'to'                   => 'nullable|email',
            'cc'                   => 'nullable|array',
            'subject'              => 'nullable|string',
            'body'                 => 'nullable|string',
            'is_custom'            => 'nullable|boolean'
        ]);
        try {
            if (!empty($validated['is_custom'])) {
                $data = $this->mailService->sendUrgentPurchaseOrderCustomEmail(
                    $validated['purchase_order_ids'],
                    company_id(),
                    financial_year_id(),
                    $validated['to'],
                    $validated['cc'] ?? [],
                    $validated['subject'],
                    $validated['body']
                );
                return AjaxResponse::success('Email sent successfully.', data: $data);
            }

            $data = $this->mailService->sendUrgentPurchaseOrderBulkEmail(
                $validated['purchase_order_ids'],
                company_id(),
                financial_year_id()
            );

            if (empty($data['sent']) && !empty($data['failed'])) {
                return AjaxResponse::error('Failed to send emails: ' . $data['failed'][0]['error']);
            }

            return AjaxResponse::success('Emails sent successfully.', data: $data);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error($e->getMessage());
        }
    }
}

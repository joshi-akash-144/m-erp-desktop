<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChequeMaster;
use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentVoucher;
use App\Models\RtgsFormView;
use Carbon\Carbon;

class RtgsService
{
    /**
     * Build the data array needed by cheque_print.blade.php.
     *
     * @param  array $input  Keys: cheque_name, cheque_date (Y-m-d), amount, ac_pay, rtgs, cheque_no, bank_name
     * @return array         Keys: cheque_data, ac_payee_flag
     */
    public function buildPrintData(array $voucherIds, $companyId): array
    {
        $vouchers = PaymentVoucher::with([
            'bank.bankDetail',
            'bank.bankDetails',
            'payment',
            'account',
            'account.bankDetail',
        ])->whereIn('id', $voucherIds)->get();

        if ($vouchers->isEmpty()) {
            throw new \Exception("Vouchers not found for IDs: " . implode(', ', $voucherIds));
        }

        $company = Company::find($companyId);
        $htmlFormViewId = null;
        $data    = [];

        foreach ($vouchers as $voucher) {
            $payment         = $voucher->payment;
            $bankAccount     = $voucher->bank;
            $bankDetail      = $bankAccount?->bankDetail;
            $beneficiaryAcc  = $voucher->account;
            $beneficiaryBank = $beneficiaryAcc?->bankDetail;
            $htmlFormViewId  = $bankDetail?->rtgs_form_view_id ?? null;

            $amount = $payment->amount ?? 0;

            $applicant = [
                'account_type'   => $bankDetail->bank_account_type ?? 'Saving/Current',
                'account_number' => $bankDetail->bank_account_number ?? 'N/A',
                'bank_name'      => $bankDetail->bank_name ?? 'N/A',
                'branch'         => $bankDetail->bank_branch_name ?? 'N/A',
                'ifsc'           => $bankDetail->bank_ifsc ?? 'N/A',
                'name'           => $company->name ?? 'N/A',
                'telephone'      => $company->phone_number ?? $company->mobile_number ?? 'N/A',
                'fax'            => null,
                'address_one'    => $company->address_one ?? 'N/A',
                'address_two'    => $company->address_two ?? 'N/A',
                'pan'            => $company->pan ?? 'N/A',
                'lei'            => '',
            ];

            $beneficiary = [
                'name'           => $beneficiaryBank->bank_beneficiary_name ?? $beneficiaryAcc->name ?? 'N/A',
                'bank_name'      => $beneficiaryBank->bank_name ?? 'N/A',
                'branch'         => $beneficiaryBank->bank_branch_name ?? 'N/A',
                'ifsc'           => $beneficiaryBank->bank_ifsc ?? 'N/A',
                'account_number' => $beneficiaryBank->bank_account_number ?? 'N/A',
                'account_type'   => $beneficiaryBank->bank_account_type ?? 'N/A',
                'city'           => $beneficiaryAcc->city ?? 'N/A',
                'telephone'      => $beneficiaryAcc->mobile_number ?? 'N/A',
                'branch_tel'     => null,
                'lei'            => '',
            ];

            $fund_transfer = [
                'signature_verified' => true,
                'amount'             => $amount,
                'amount_in_words'    => amountInWords($amount),
                'bank_charges'       => 0.00,
                'total_amount'       => $amount,
                'debited_account'    => $bankDetail->bank_account_number ?? 'N/A',
                'financial_tran_id'  => $payment->utr_number ?? null,
                'cheque_number'      => $payment->cheque_number ?? '',
            ];

            $office_use = [
                'rtgs_serial_no' => null,
                'mf_no'          => null,
                'sign'           => null,
                'date'           => null,
                'time'           => null,
                'checker_sign'   => null,
                'checker_date'   => null,
                'checker_time'   => null,
            ];

            $acknowledgment = [
                'received_from'      => $applicant['name'],
                'account_number'     => $applicant['account_number'],
                'amount'             => $amount,
                'date'               => $payment?->payment_date
                    ? Carbon::parse($payment->payment_date)->format('d/m/Y')
                    : now()->format('d/m/Y'),
                'beneficiary_name'   => $beneficiary['name'],
                'beneficiary_bank'   => $beneficiary['bank_name'],
                'beneficiary_branch' => $beneficiary['branch'],
                'beneficiary_ifsc'   => $beneficiary['ifsc'],
                'beneficiary_acc'    => $beneficiary['account_number'],
            ];

            $data[] = compact('applicant', 'beneficiary', 'fund_transfer', 'office_use', 'acknowledgment');
        }

        $viewName =   $htmlFormViewId ? RtgsFormView::find($htmlFormViewId)->view_name : RtgsFormView::where('is_default', true)->first()->view_name;
        $htmlFormView = 'company.pages.rtgs.' . $viewName;
        $rtgsFormHtml = [];
        foreach ($data as $index => $datum) {
            $rtgsFormHtml['html'][] = view($htmlFormView, ['data' => $datum])->render();
        }
        return $rtgsFormHtml;
    }
}

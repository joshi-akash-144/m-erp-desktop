<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChequeMaster;
use App\Models\Payment;
use Carbon\Carbon;

class ChequeService
{
    /**
     * Build the data array needed by cheque_print.blade.php.
     *
     * @param  array $input  Keys: cheque_name, cheque_date (Y-m-d), amount, ac_pay, rtgs, cheque_no, bank_name
     * @return array         Keys: cheque_data, ac_payee_flag
     */
    public function buildPrintData(int $paymentId): array
    {
        // Fetch payment
        $payment = Payment::find($paymentId);
        if (!$payment) {
            throw new \Exception("Payment with ID $paymentId not found.");
        }

        // Fetch bank if selected
        $bank = $payment->bank_id ? Account::find($payment->bank_id) : null;

        // Detect cheque format
        $chequeFormatId = $bank?->cheque_master_id;

        // Load master (bank-specific OR default)
        if (!$chequeFormatId) {
            $master = ChequeMaster::with('properties')
                ->where('is_default', 1)
                ->first();
        } else {
            $master = ChequeMaster::with('properties')
                ->where('id', $chequeFormatId)
                ->first();
        }

        if (!$master) {
            throw new \Exception("Cheque format not found.");
        }

        // Convert amount to words
        $amountWords = amountInWords($payment->amount ?? 0);

        // ----------------------------------------------------
        // Build cheque style array (your older key logic)
        // ----------------------------------------------------

        $cheque_data = [
            'cheque_date' => Carbon::parse($payment->payment_date)->format('d-m-Y'),
            'cheque_name' => $payment->cheque_name ?? 'LOREM IPSUM / JOHN DOE',
            'cheque_amount' => $payment->amount ?? 0,
            'cheque_amount_in_word' => $amountWords ?? 'ZERO RUPEES',
            'id' => $bank->bank_id ?? 0,
            'ac_payee_flag' => $payment->ac_pay == 'Y' ? 'y' : 'n',
            'show_or_not' => 0,
            'cheque_height' => '600px',
            'cheque_width' => '100%',
            'left_margin' => $master->left_margin ?? '',
            'top_margin' => $master->top_margin ?? '',
        ];

        $properties = $master?->properties ?? [];

        foreach ($properties as $property) {

            // Remove fields that are NOT style attributes
            $attributes = collect($property->getAttributes())
                ->except([
                    'id',
                    'cheque_master_id',
                    'column_value',
                    'created_at',
                    'updated_at'
                ])
                ->toArray();

            // Convert attributes to CSS format
            $styleAttributes = implode('; ', array_map(
                fn($k, $v) =>
                convertToSpaceSeparated($k) . ': ' .
                    (
                        // multiply measured values by 100 and add px
                        (in_array($k, ['top', 'left', 'width', 'height']) && is_numeric($v))
                        ? ($v * 100) . 'px'
                        : ((in_array($k, ['font_size']))
                            ? $v . 'px'
                            : $v)
                    ),
                array_keys($attributes),
                $attributes
            ));

            // Old logic: use snake_case version of column_value
            $key = convertToSnakeCase($property->column_value);

            $cheque_data[$key] = $styleAttributes;
        }

        // Return all data (remove dd)
        return [
            'payment' => $payment,
            'bank' => $bank,
            'master' => $master,
            'cheque_data' => $cheque_data,
            'html' => view('company.pages.payment-voucher.cheque_print', ['cheque_data' => $cheque_data])->render(),
        ];
    }
}

<?php

namespace App\Helpers;

use App\Models\PurchaseOrder;

class TaxHelper
{
      /**
     * Calculate inclusive rate and tax details from exclusive rate.
     *
     * @param float $rate          Exclusive rate (without GST)
     * @param float $cgstPercent   CGST %
     * @param float $sgstPercent   SGST %
     * @param float $igstPercent   IGST %
     * @param float $quantity      Quantity (optional, for totals)
     * @return array
     */
    public static function calculateInclusiveRate(
        float $rate,
        float $cgstPercent = 0,
        float $sgstPercent = 0,
        float $igstPercent = 0,
        float $quantity = 1,
        string $accountType = PurchaseOrder::TAX_LOCAL, // 'local' or 'interstate'
        bool $isInclusive = false      // whether rate is inclusive
    ): array {
        // Pick correct tax set
        $totalTaxPercent = $accountType === PurchaseOrder::TAX_LOCAL
            ? $cgstPercent + $sgstPercent
            : $igstPercent;
    
        if ($isInclusive) {
            // Reverse calculation (inclusive → exclusive)
            $exclusiveRate = $rate / (1 + ($totalTaxPercent / 100));
        } else {
            $exclusiveRate = $rate;
        }
    
        $inclusiveRate = $exclusiveRate * (1 + ($totalTaxPercent / 100));
    
        // Per-unit taxes
        $cgstAmount = $exclusiveRate * ($cgstPercent / 100);
        $sgstAmount = $exclusiveRate * ($sgstPercent / 100);
        $igstAmount = $exclusiveRate * ($igstPercent / 100);
    
        // Totals
        $amount      = $exclusiveRate * $quantity;
        $taxAmount   = ($cgstAmount + $sgstAmount + $igstAmount) * $quantity;
        $totalAmount = $inclusiveRate * $quantity;
    
        return [
            'rate'           => round($exclusiveRate, 2),
            'inclusive_rate' => round($inclusiveRate, 2),
            'cgst_amount'    => round($cgstAmount, 2),
            'sgst_amount'    => round($sgstAmount, 2),
            'igst_amount'    => round($igstAmount, 2),
            'taxable_amount' => round($amount, 2),
            'amount'         => round($amount, 2),
            'tax_amount'     => round($taxAmount, 2),
            'total_amount'   => round($totalAmount, 2),
        ];
    }
    
}

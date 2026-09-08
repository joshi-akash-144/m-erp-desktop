<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format number in Indian format
     *
     * @param float|int $number
     * @param int $decimals
     * @return string
     */
    public static function indianFormat($number, $decimals = 2)
    {
        $number = number_format($number, $decimals, '.', '');
        $parts = explode('.', $number);
        $integer = $parts[0];
        $decimal = $parts[1] ?? '00';

        $lastThree = substr($integer, -3);
        $rest = substr($integer, 0, -3);

        if ($rest != '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest . ',' . $lastThree;
        } else {
            $formatted = $lastThree;
        }

        return $formatted . '.' . $decimal;
    }
}

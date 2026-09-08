<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidFinancialYearDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fyStart = financial_year_start();
        $fyEnd   = financial_year_end();

        if ($value < $fyStart || $value > $fyEnd) {
            $fail('The :attribute must be within the financial year.');
        }
    }
}

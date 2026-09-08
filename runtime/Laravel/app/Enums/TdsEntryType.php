<?php

namespace App\Enums;

enum TdsEntryType: string
{
    case Purchase = 'purchase';
    case Sales    = 'sales';
    case Payment  = 'payment';
    case Journal  = 'journal';
    case Expense  = 'expense';

    public function label(): string
    {
        return match($this) {
            self::Purchase => 'Purchase Invoice',
            self::Sales    => 'Sales Invoice',
            self::Payment  => 'Payment Voucher',
            self::Journal  => 'Journal Voucher',
            self::Expense  => 'Expense',
        };
    }
}

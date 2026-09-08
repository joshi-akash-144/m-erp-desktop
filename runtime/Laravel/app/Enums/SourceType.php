<?php

namespace App\Enums;

use App\Models\Freight;

class SourceType
{
    const SALES = 'sales_invoice';
    const PURCHASE = 'purchase_invoice';
    const PAYMENT = 'payment';
    const RECEIPT = 'receipt';
    const JOURNAL = 'journal';
    const DEBIT_NOTE = 'debit_note';
    const CREDIT_NOTE = 'credit_note';
    const SALES_RETURN = 'sales_return';
    const PURCHASE_RETURN = 'purchase_return';
    const FREIGHT = 'freight_invoice';
    const ACCOUNT = 'account';
}
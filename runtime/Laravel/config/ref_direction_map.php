<?php

return [

    'direction' => [

        'purchase_invoice' => 'credit',   // You owe vendor
        'sales_invoice'    => 'debit',    // Customer owes you

        'payment'          => 'debit',    // You pay → debit
        'receipt'          => 'credit',   // You receive → credit

        'debit_note'       => 'debit',    // Increases receivable
        'credit_note'      => 'credit',   // Reduces payable (rebate)

        // Journal entries
        'journal_debit'    => 'debit',
        'journal_credit'   => 'credit',

        // Receipt entries
        'receipt_debit'    => 'debit',
        'receipt_credit'   => 'credit',

        // Payment entries
        'payment_debit'    => 'debit',
        'payment_credit'   => 'credit',

        'sales_return'      => 'credit',
        'purchase_return'   => 'debit'
    ],

];

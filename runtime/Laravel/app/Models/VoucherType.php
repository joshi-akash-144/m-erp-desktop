<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    
    const JOURNAL               = 1;   // General Journal Entry
    const PAYMENT               = 2;   // Cash/Bank Payment
    const RECEIPT               = 3;   // Cash/Bank Receipt
    const PURCHASE_INVOICE      = 4;   // Purchase Invoice
    const SALE_INVOICE          = 5;   // Sales Invoice
    const SALE_ORDER            = 6;   // Sales Order
    const PURCHASE_ORDER        = 7;   // Purchase Order
    const GOODS_RECEIPT_NOTE    = 8;   // Goods Receipt (Stock IN)
    const PURCHASE_RETURN       = 9;   // Debit Adjustment
    const SALES_RETURN          = 10;  // Credit Adjustment
    const CONTRA                = 11;  // Cash/Bank Transfer between accounts
    const EXPENSE               = 12;  // Expense Voucher
    const INCOME                = 13;  // Non-sales income
    const ADVANCE_PAYMENT       = 14;  // Payment in advance to supplier
    const ADVANCE_RECEIPT       = 15;  // Receipt in advance from customer
    const BANK_RECONCILIATION   = 16;  // Bank Reconciliation Entry
    const OPENING_BALANCE       = 17;  // Opening Balance Entry
    const INVENTORY_ADJUSTMENT  = 18;  // Stock/Inventory Adjustments
    const PAYROLL               = 19;  // Salary/Payroll Payment
    const TAX_PAYMENT           = 20;  // GST/TDS/Other Tax Payment
    const TAX_RECEIPT           = 21;  // Tax Refund / Recovery
    const PETTY_CASH            = 22;  // Petty Cash Transactions
    const CONTRA_RECEIPT        = 23;  // Bank Transfer Receipt
    const CONTRA_PAYMENT        = 24;  // Bank Transfer Payment
    const GOODS_ISSUE_NOTE      = 25;  // Goods Issue (Stock OUT)
    const STOCK                 = 26;
    const DELIVERY_CHALLAN      = 27;
    const OPENING_STOCK         = 28;
    const CREDIT_NOTE           = 29;
    const DEBIT_NOTE            = 30;

    // // Optional: table relationship
    // public function vouchers()
    // {
    //     return $this->hasMany(Voucher::class, 'voucher_type_id');
    // }
}

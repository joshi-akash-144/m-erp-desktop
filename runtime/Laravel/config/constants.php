<?php
return [
    'tax_category_types' => [
        'goods' => 'Goods',
        'services' => 'Service',
    ],
    'zero_rate_tax_type' => [
        'exempt' => 'Exempt',
        'zero_rated' => 'Zero Rated',
        'non_gst' => 'Non-GST',
        'nil_rated' => 'Nil Rated',
        'taxable' => 'Taxable',
    ],
    'gender'=>[
        'male'=>'Male',
        'female'=>'Female',
        'other'=>'Others'
    ],
    'bank_name' => [
        '1' => 'HDFC Bank',
        '2' => 'AXIS Bank',
        '3' => 'Bank Of Baroda',
        '4' => 'State Bank Of India'
    ],
    'rtgs_name' => [
        '1' => 'HDFC RTGS Form',
        '2' => 'AXIS RTGS Form',
        '3' => 'BOB RTGS Form',
        '4' => 'SBI RTGS Form',
    ],
    'cheque_name' => [
        '1' => 'HDFC Cheque',
        '2' => 'AXIS Cheque',
        '3' => 'BOB Cheque',
        '4' => 'SBI Cheque',
    ],
    'account_group_types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'income' => 'Income',
        'expense' => 'Expense',
    ],
    'type_of_dealer' => [
        'registered' => 'Registered',
        'unregistered' => 'Unregistered',
        'composition' => 'Composition',
        'uni_holder' => 'UIN Holder',
    ],
    'filing_frequency' => [
        'not_known' => 'Not Known',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
    ],
    'transport_modes' => [
        'road' => 'Road',
        'air' => 'Air',
        'rail' => 'Rail',
        'ship' => 'Ship',
    ],
    'is_bill_wise' => [
        '1' => 'Yes',
        '0'  => 'No',
    ],
    'bank_account_types' => [
        'saving' => 'Saving',
        'current' => 'Current',
        'od' => 'Overdraft',
        'fd' => 'Fixed Deposit',
        'rd' => 'Recurring Deposit',
        'cc' => 'Cash Credit',
        'nro' => 'NRO',
        'nre' => 'NRE',
        'fcnr' => 'FCNR',
        'ppf' => 'PPF',
        'escrow' => 'Escrow',
        'loan' => 'Loan',
        'salary' => 'Salary',
        'demat' => 'Demat',
    ],
    'itc_eligibility' => [
        'input_goods' => 'Input Goods',
        'input_service' => 'Input Service',
        'capital_goods' => 'Capital Goods',
    ],
    'rcm_nature' => [
        'based_on_daily_limit' => 'Based on Daily Limit',
        'compulsory' => 'Compulsory(Reg. Dealer)',
        'service_import' => 'Service Import',
    ],

    'gst_type' => [
        'gst_applicable' => 'GST Applicable',
        'gst_not_applicable' => 'GST Not Applicable',
        'non_gst' => 'Non GST',
    ],

    'tax_type' => [
        'igst' => 'IGST',
        'cgst' => 'CGST',
        'sgst' => 'SGST',
        'professional_tax' => 'Professional Tax',
    ],

    'account_type' => [
        'account' => 'Account',
        'supplier' => 'Supplier',
        'customer' => 'Customer',
    ],
    'is_maintain_stock_balance' => [
        '1' => 'Yes',
        '0' => 'No',
    ],
    'bill_sundry_nature' => [
        'other' => 'Other',
        'sgst' => 'SGST',
        'cgst' => 'CGST',
        'igst' => 'IGST',
        'round_off' => 'Round Off',
    ],
    'bill_sundry_type' => [
        'additive' => 'Additive',
        'subtractive' => 'Subtractive',
    ],
    'sale_adjust_in_amount' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'purchase_adjust_in_amount' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'purchase_adjust_in_party_amount' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'sale_adjust_in_party_amount' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'bill_sundry_amount_round_off' => [
        '1' => 'Yes',
        '0' => 'No'
    ],

    'purchase_post_over_and_above' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'purchase_account_type' => [
        'specify_account' => 'Specify Account hear',
        'specify_account_in_voucher' => 'Specify Account in Voucher'
    ],
    'purchase_party_account_type' => [
        'specify_account' => 'Specify Account hear',
        'specify_account_in_voucher' => 'Specify Account in Voucher'
    ],

    'sale_post_over_and_above' => [
        '1' => 'Yes',
        '0' => 'No'
    ],
    'sale_account_type' => [
        'specify_account' => 'Specify Account hear',
        'specify_account_in_voucher' => 'Specify Account in Voucher'
    ],
    'sale_party_account_type' => [
        'specify_account' => 'Specify Account hear',
        'specify_account_in_voucher' => 'Specify Account in Voucher'
    ],
    'bill_sundry_nature' => [
        'gst' => 'GST',
        'other' => 'Other',
        'tds' => 'TDS'
    ],
    'sale_taxation_types' => [
        'taxable' => 'Taxable',
        'exempt' => 'Exempt',
        'nil_rated' => 'Nil Rated',
        'zero_rated' => 'Zero Rated',
        'non_gst' => 'Non-GST',
    ],

    'sale_transaction_types' => [
        'domestic' => 'Domestic',
        'export' => 'Export',
    ],
    'sale_regions' => [
        'local' => 'Local',
        'interstate' => 'Inter State',
    ],
    'purchase_taxation_types' => [
        'taxable' => 'Taxable',
        'exempt' => 'Exempt',
        'nil_rated' => 'Nil Rated',
        'zero_rated' => 'Zero Rated',
        'non_gst' => 'Non-GST'
    ],
    'purchase_transaction_types' => [
        'domestic' => 'Domestic',
        'import' => 'Import',
    ],
    'purchase_regions' => [
        'local' => 'Local',
        'interstate' => 'Inter State',
    ],
    'party_type' => [
        'account' => 'Account',
        'supplier' => 'Supplier',
        'customer' => 'Customer',
    ],

    'tds_category_types' => [
        'tds' => 'TDS',
        'tcs' => 'TCS',
        'higher' => 'HIGHER',
    ],
    'applicable_to' => [
        'individual' => 'Individual',
        'both' => 'Both',
        'company' => 'Company',
    ],
    'range'=>[
        '1'=>'Greater Than >=',
        '2'=>'Less Than <='
    ],
    'statuses'=>[
        'open' => 'Open',
        'close' =>'Close',
        'cancel'=>'Cancel',
        'hold'=>'Hold',
    ],
    'qc_status'=>[
        'pending' => 'Pending',
        'passed' => 'Passed',
        'failed' => 'Failed',
    ],
    'bag_types' => [
        'gunny' => 'Gunny',
        'plastic' => 'Plastic'
    ],
    'calculation_type'=>[
        'percentage' => 'Percentage',
        'fixed' => 'Fixed',
    ],
    'apply_on'=>[
        'basic' => 'Basic',
        'running_total' => 'Running Total',
        'previous_row' => 'Previous Row',
        'grand_total' => 'Grand Total',
    ],
    'affect_grand_total'=>[
        '1' => 'Yes',
        '0' => 'No'
    ],
    'payment_status'=>[
        'unpaid' => 'Unpaid',
        'partially_paid' => 'Partially Paid',
        'fully_paid' => 'Fully Paid',
        'overpaid'=>'Overpaid',
    ],
    'product_status'=>[
        'all' => 'All',
        'product_in' => 'Product In',
        'product_out' => 'Product Out',
        'product_in_out' => 'Product In Out',
        'pending_in' => 'Pending In',
        'pending_out' => 'Pending Out',
        'pending_in_out' => 'Pending In Out',
    ],
    'print_type'=>[
        'ticket' => 'Ticket',
        'letter' => 'Letter',
        'gatepass' => 'Gatepass',
        'grn' => 'GRN',
    ],

    'rebate_status' => [
        '1' => 'Rebate Pending',
        '2' => 'Payment Clear and Rebate Pending',
        '3' => 'Sales Bill Map Remaining with Purchase',
    ],
    'delivery_challan_status'=>[
        'all' => 'All',
        'open' => 'Open',
        'hold' => 'Hold',
        'close' =>'Close',
        'billed'=>'Billed',
        'cancel'=>'Cancel',
        'rejected'=>'Rejected'
        ],
'voucher_type' => [
        'all' => 'All',
        'journal' => 'Journal',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'sales_invoice' => 'Sales',
        'purchase_invoice' => 'Purchase',
        'debit_note' => 'Debit Note',
        'credit_note' => 'Credit Note',
    ],
    
    'fuel_type' => [
        '1' => 'Diesel',
        '2' => 'Petrol',
        '3' => 'CNG',        
    ],

    'license_category' => [
        'ld' => 'Learner\'s driving license',
        'pd' => 'Permanent driving license',
        'cd' => 'Commercial driving license',   
        'idp' => 'International driving permit',
    ],
    'marital_status'=>[        
        'single'=>"Single",
        'married'=>"Married",      
    ],
    'blood_group' => [
        'A+' => 'A+',
        'A-' => 'A-',
        'B+' => 'B+',
        'B-' => 'B-',
        'AB+' => 'AB+',
        'AB-' => 'AB-',
        'O+' => 'O+',
        'O-' => 'O-',
    ],

    'is_audit_enable' => env('IS_AUDIT_ENABLE', true),

];

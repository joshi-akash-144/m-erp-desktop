<?php
return [

    //===================Start Master Module ===================//
    'tax_category' => [
        'name' => 'Tax Category Name',
        'type' => 'Select Type',
        'igst' => 'Integrated Tax (IGST) %',
        'cgst' => 'Central Tax (CGST) %',
        'sgst' => 'State/UT Tax (SGST) %',
        'zero_tax_type' => 'Select Zero Tax Type',
    ],
    'account_group' => [
        'name' => 'Enter Account Group Name',
        'type' => 'Group Type',
        'parent_group' => 'Select Parent Group',
        'confirmation_for_primary_group' => 'Select Yes/No',
    ],
    'user_profile' => [
        'user_details' => 'User Details',
        'full_name' => 'Full Name',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'email' => 'Email Id',
        'role' => 'Role',
        'phone_number' => 'Phone Number',
        'address' => 'Address',
        'joining_date' => 'Joining Date',
        'date_of_birth' => 'Date Of Birth',
        'gender' => 'Select Gender',
        'profile_picture' => 'Profile Picture'
    ],
    'bank_configuration' => [
      'bank_name' => 'Select Bank Name',
      'rtgs_name' => 'Select RTGS Name',
      'cheque_name' => 'Select Cheque Name'
    ],
    'account' => [
        'name' => 'Enter Account Name',
        'print_name' => 'Enter Print Name',
        'group' => 'Select Account Group',
        'opening_balance' => 'Enter Opening Balance',
        'address_one' => 'Enter Address Line 1',
        'address_two' => 'Enter Address Line 2',
        'state_id' => 'Select State',
        'country_id' => 'Select Country',
        'city' => 'Enter City',
        'type_of_dealer' => 'Select Type of Dealer',
        'gst_number' => 'Enter GST Number',
        'mobile_number' => 'Enter Mobile Number',
        'pan' => 'Enter IT PAN',
        'tin' => 'Enter TIN',
        'whatsapp_number' => 'Use Country Code Ex. 910000000000',
        'email' => 'Ex. abc@gmail.com',
        'station' => 'Enter Station',
        'postal_code' => 'Enter Postal Code',
        'distance' => 'Enter Distance in Km.',
        'transport' => 'Enter Transporter',
        'contact_person' => 'Enter Contact Person',
        'transport_modes' => 'Select Transport Mode',
        'bank_beneficiary_name' => 'Enter Bank Beneficiary Name',
        'bank_name' => 'Enter Bank Name',
        'bank_branch_name' => 'Enter Branch Name',
        'bank_account_number' => 'Enter Bank A/C No.',
        'bank_ifsc' => 'Enter Bank IFSC',
        'is_billwise' => 'Select Yes/No',
        'default_sale_type' => 'Select Sale Type',
        'default_purchase_type' => 'Select Purchase Type',
        'tax_category' => 'Select Tax Category',
        'hsn_sac_code' => 'Enter SAC/HSN Code',
        'itc_eligibility' => 'Select Itc Eligibility',
        'rcm_nature' => 'Select Reverse Charge',
        'gst_type' => 'Select GST Type',
        'tax_type' => 'Select Tax Type',
        'filing_frequency' => 'Select Filing Frequency',
        'party_type' => '-- Select Party Type --',
    ],

    'unit' => [
        'name' => 'Enter Unit Name',
        'print_name' => 'Enter Unit Print Name',
        'uqc' => 'Enter UQC Code',
    ],

    'unit_conversion' => [
        'main_unit_id' => 'Select Main Unit',
        'sub_unit_id' => 'Select Sub Unit',
        'conversion_factor' => 'Enter Con. Factor',
    ],
    'item' => [
        'name' => 'Enter Item Name',
        'print_name' => 'Enter Print Item Name',
        'item_group_id' => 'Select Item Group',
        'unit_id' => 'Select Unit',
        'opening_qty' => 'Enter Opening Stock',
        'opening_value' => 'Enter Opening Value',
        'tax_category_id' => 'Select Tax Category',
        'hsn_sac_code' => 'Enter SAC/HSN Code',
        'is_maintain_stock_balance'    => 'Select Yes/No',
    ],
    'bill_sundry' => [
        'name' => 'Enter Bill Sundry Name',
        'print_name' => 'Enter Bill Sundry Print Name',
        'bill_sundry_type' => 'Select Type',
        'nature' => 'Select Nature',
        'default_value' => 'Enter Default Value',

        //Sale
        'sale_adjust_in_amount' => 'Select Yes/No',
        'sale_account_id' => 'Select Account',
        'sale_party_account_id' => 'Select Account',
        'sale_adjust_in_party_amount' => 'Select Yes/No',
        'sale_post_over_and_above' => 'Select Yes/No',
        'sale_account_type' => 'Select Option',
        'sale_party_account_type' => 'Select Option',

        //Purchase
        'purchase_adjust_in_amount' => 'Select Yes/No',
        'purchase_account_id' => 'Select Account',
        'purchase_party_account_id' => 'Select Account',
        'purchase_adjust_in_party_amount' => 'Select Yes/No',
        'purchase_post_over_and_above' => 'Select Yes/No',
        'purchase_account_type' => 'Select Option',
        'purchase_party_account_type' => 'Select Option',

        'bill_sundry_amount_round_off' => 'Select Yes/No',
        'bill_sundry_nature' => 'Select Nature',
    ],
    'sale_type' => [
        'name' => 'Enter Sale Type Name',
        'account_id' => 'Select Account',
        'taxation_type' => 'Select Taxation Type',
        'region' => 'Select Region',
        'transaction_type' => 'Select Transaction Type',
        'cgst' => 'Enter CGST %',
        'sgst' => 'Enter SGST %',
        'igst' => 'Enter IGST %',
    ],

    'purchase_type' => [
        'name' => 'Enter Purchase Type Name',
        'account_id' => 'Select Account',
        'taxation_type' => 'Select Taxation Type',
        'cgst' => 'Enter CGST %',
        'sgst' => 'Enter SGST %',
        'igst' => 'Enter IGST %',
        'region' => 'Select Region',
        'transaction_type' => 'Select Transaction Type',
    ],
     'item_group'=>[
        'name'=>'Enter Item Group Name'
    ],
    'company'=>[
        'name'=>'Enter Company Name',
        
        'legal_name'=>'Enter Legal Name',
        'print_name'=>'Enter Print Name',
        'state_id' => 'Select State',
        'country_id' => 'Select Country',
        'address_one'=>'Enter Address Line 1',
        'address_two'=>'Enter Address Line 2',
        'cin'=>'Enter CIN',
        'pan'=>'Enter PAN',
        'gst_number'=>'GST Number',
        'tan'=>'Enter TAN',
        'phone_number'=>'Enter Phone Number',
        'email'=>'Enter Email',
        'currency'=>'Currency',
        'type_of_dealer'=>'Select Type of Dealer',
        'mobile_number' => 'Enter Mobile Number',
    ],

    'broker' => [
        'name' => 'Enter Name',
        'account_group_id' => 'Select Group',
        'print_name' => 'Enter Print Name',
        'pan' => 'Enter PAN',
        'mobile_number' => 'Enter Mobile Number',
        'email' => 'Enter Email',
        'address_one' => 'Enter Address Line 1',
        'address_two' => 'Enter Address Line 2',
        'country_id' => 'Select Country',
        'state_id' => 'Select State',
        'city' => 'Enter City',
        'postal_code' => 'Enter PIN',

        'commission_details' => 'Commission Details',
        'sale_commission_rate' => 'Sale Commission Rate (%)',
        'sale_unit_id' => 'Select Sale Commission Unit',
        'purchase_commission_rate' => 'Purchase Commission Rate (%)',
        'purchase_unit_id' => 'Select Purchase Commission Unit',
        
        'bank_details' => ' Bank Details',
        'bank_name' => 'Enter Bank Name',
        'bank_branch_name' => 'Enter Branch Name',
        'bank_account_number' => 'Enter Account No.',
        'bank_ifsc' => 'Enter IFSC Code',
        'is_billwise' => 'Select Bill By Bill'
    ],
    
    'condition'=>[
        'name'=>'Enter Condition Name',
        'print_name'=>'Enter Print Name',
    ],

    'element' => [
        'name' => 'Enter Element Name',
        'print_name' => 'Enter Element Print Name',
        'range' => 'Enter Range',
    ],

    'dairy_parameter' => [
        'condition' => 'Select Dairy Parameter Condition',
        'element' => 'Select Dairy Parameter Element',
        'guarantee' => 'Enter Guarantee ',
    ],

    'purchase_order'   => [
        'po_number'           => 'Enter PO No.',
        'order_date'          => 'DD-MM-YYYY',
        'contract_number'     => 'Contract No.',
        'broker_id'           => 'Select Broker',
        'account_id'          => 'Select Supplier',
        'delivery_days'       => 'Enter Delivery Days',
        'destination_id'      => 'Enter Destination',
        'due_date'            => 'DD-MM-YYYY',
        'purchase_order_id'   => '--Select P.O.--',
    ],

    'destination' => [
        'name' => 'Enter Godown Name',
        'contact_person_name' => 'Enter Contact Person Name',
        'email' => 'Enter Email',
        'mobile' => 'Enter Mobile Number',
        'phone' => 'Enter Phone Number',
        'kms' => 'Enter kilometers',
        'address_one' => 'Enter Address Line 1',
        'address_two' => 'Enter Address Line 2',
        'city' => 'Enter City',
        'dist' => 'Enter District',
        'taluka' => 'Enter Taluka',
        'state' => 'Select  State',
        'country' => 'Select Country',
        'postal_code' => 'Enter Postal Code',
    ],

    'godown' => [
        'destination_id' => '--Select Destination Name--',        
        'godown_name' => 'Enter Godown Name',
        'remark' => 'Enter Godown Remark',
    ],

    'role' => [
        'name' => 'Name',        
    ],

    'payee_category' => [        
        'payee_category' => 'Enter Payee Category',        
    ],
    'user' => [
        'name' => 'Enter User Name',
        'email' => 'Enter Email',
        'username' => 'Enter Username',
        'password' => 'Enter Password',
    ],


    'tds_category' => [
        'section' => 'Enter Section Name',
        'category_name' => 'Enter Category Name',
        'rate' => 'Enter Rate',
        'type' => '--Select Type--',
        'applicable_to' => '--Select Applicable Type--',
        'description' => ' Enter Description',
    ],
    //===================End Master Module ===================//
];

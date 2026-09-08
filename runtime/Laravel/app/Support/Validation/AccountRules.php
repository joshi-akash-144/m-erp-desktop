<?php

namespace App\Support\Validation;

use App\Models\Account;
use App\Models\AccountPreference;
use App\Models\AccountTaxDetail;
use App\Models\AccountYearBalance;
use Illuminate\Validation\Rule;

class AccountRules
{

    public static function generalInfo(): array
    {
        return [
            'account_group_id'  => ['required', 'exists:account_groups,id'],
            'print_name'        => ['nullable', 'string', 'max:255'],
            'city'              => ['nullable', 'string', 'max:255'],
            'address_one'       => ['nullable', 'string', 'max:255'],
            'address_two'       => ['nullable', 'string', 'max:255'],
            'postal_code'       => ['nullable', 'string', 'digits_between:5,10'],
            'country_id'        => ['nullable', 'exists:countries,id'],
            'state_id'          => ['nullable', 'exists:states,id'],
            'mobile_number'     => ['nullable', 'string', 'digits_between:10,15'],
            'whatsapp_number'   => ['nullable', 'string', 'digits_between:10,15'],
            'email'             => ['nullable', 'email'],
            'is_billwise'       => ['nullable', 'boolean'],
            'party_type'       => ['required', Rule::in(Account::PARTY_TYPE)],
        ];
    }


    public static function openingBalance(): array
    {
        return [
            'opening_balance'   => ['nullable', 'numeric', 'min:0'],
            'opening_type'      => ['nullable', Rule::in(AccountYearBalance::OPENING_TYPE)],
        ];
    }


    public static function taxDetail(): array
    {
        return [
            'type_of_dealer'    => ['nullable', Rule::in(AccountTaxDetail::TAX_DETAIL)],
            'filing_frequency'  => ['nullable', Rule::in(AccountTaxDetail::FILING_FREQUENCY)],
            'gst_number'        => ['nullable', 'string', 'size:15'],
            'tax_category_id'   => ['nullable', 'exists:tax_categories,id'],
            'hsn_sac_code'      => ['nullable', 'string', 'max:255'],
            'itc_eligibility'   => ['nullable', Rule::in(AccountTaxDetail::ITC_ELIGIBILITY)],
            'rcm_nature'        => ['nullable', Rule::in(AccountTaxDetail::RCM_NATURE)],
            'pan'               => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'tin'               => ['nullable', 'string', 'max:20'],
            'gst_status'        => ['nullable', Rule::in(AccountTaxDetail::GST_STATUS)],
            'tax_type'          => ['nullable', Rule::in(AccountTaxDetail::TAX_TYPE)],
            'gst_type'          => ['nullable', Rule::in(AccountTaxDetail::GST_TYPE)],
            'tds_category_id'   => ['nullable', 'exists:tds_categories,id'],
            'payee_category_id' => ['nullable', 'exists:payee_categories,id'],
        ];

        $typeOfDealer = request('type_of_dealer');
        if (
            $typeOfDealer &&
            in_array($typeOfDealer, array_diff(AccountTaxDetail::TAX_DETAIL, ['unregistered']), true)
        ) {
            $rules['gst_number'] = ['required', 'string', 'size:15'];
        }

        return $rules;
    }


    public static function transportDetail(): array
    {
        return [
            'station'           => ['nullable', 'string', 'max:255'],
            'distance'          => ['nullable', 'integer', 'min:0'],
            'contact_person'    => ['nullable', 'string', 'max:255'],
            'transport'         => ['nullable', 'string', 'max:255'],
            'transport_mode'    => ['nullable', Rule::in(AccountPreference::TRANSPORT_MODE)],
        ];
    }


    public static function bankDetail(): array
    {
        return [
            'bank_beneficiary_name' => ['nullable', 'string', 'max:255'],
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_branch_name'      => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'is_default_bank'       => ['nullable', 'boolean'],
            'rtgs_form_view_id'     => ['nullable'],
            'bank_ifsc'             => ['nullable', 'string'],
            'cheque_id'             => ['nullable', 'exists:cheque_masters,id'],
            'rtgs_id'               => ['nullable', 'exists:rtgs_form_views,id'],
        ];
    }


    public static function preferenceDetail(): array
    {
        return [
            'sale_type_id'                  => ['nullable', 'exists:sale_types,id'],
            'purchase_type_id'              => ['nullable', 'exists:purchase_types,id'],
            'credit_limit'                  => ['nullable', 'numeric', 'min:0'],
            'credit_days'                   => ['nullable', 'integer', 'min:0'],
            'purchase_commission_rate'      => ['nullable', 'numeric'],
            'sale_commission_rate'          => ['nullable', 'numeric'],
            'sale_unit_id'                  => ['nullable', 'exists:units,id'],
            'purchase_unit_id'              => ['nullable', 'exists:units,id'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\BillSundry;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillSundryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('bill_sundry.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bill_sundries')->where(function ($query) {
                    return $query->where('company_id', session('company_id'))->whereNull('deleted_at');
                })
            ],
            'print_name' => [
                'required',
                'string',
                'max:255',
            ],
            'bill_sundry_type' => ['required', Rule::in(BillSundry::BILL_SUNDRY_TYPE)],
            'default_value' => 'required|numeric',
            'bill_sundry_nature'=>'required',
            'calculation_type'=> 'required',
            // 'apply_on'=> ['required','string'],
            // 'bill_sundry_amount_round_off' => ['required','boolean', Rule::in(BillSundry::BILL_SUNDRY_AMOUNT_ROUND_OFF)],
            'bill_sundry_amount_round_off' => ['required', 'boolean'],
            'uuid' => 'required|uuid|unique:bill_sundries,uuid',
            'purchase_adjust_in_amount' => ['required', 'boolean'],

            // 'purchase_adjust_in_party_amount' => ['required', Rule::in(BillSundry::PURCHASE_ADJUST_IN_PARTY_AMOUNT)],
            'purchase_adjust_in_party_amount' => ['required', 'boolean'],
            // 'sale_adjust_in_amount' => ['required', Rule::in(BillSundry::SALE_ADJUST_IN_AMOUNT)],
            'sale_adjust_in_amount' => ['required', 'boolean'],
            // 'sale_adjust_in_party_amount' => ['required', Rule::in(BillSundry::SALE_ADJUST_IN_PARTY_AMOUNT)],
            'sale_adjust_in_party_amount' => ['required', 'boolean'],
        ];
        if($this->input('calculation_type') === 'percentage'){
            $rules['apply_on']=['required','string'];
        }
        if ($this->input('purchase_adjust_in_amount') == false) {
            $rules['purchase_account_type'] = ['required', Rule::in(BillSundry::PURCHASE_ACCOUNT_TYPE)];
        }

        if ($this->input('sale_adjust_in_amount') == false) {
            $rules['sale_account_type'] = ['required', Rule::in(BillSundry::SALE_ACCOUNT_TYPE)];
        }

        if (
            ($this->input('purchase_adjust_in_amount') == true) 
            xor 
            ($this->input('purchase_adjust_in_party_amount') == true)
        ) {
            // $rules['purchase_post_over_and_above'] = ['required', Rule::in(BillSundry::PURCHASE_POST_OVER_AND_ABOVE)];
            $rules['purchase_post_over_and_above'] = ['required', 'boolean'];
        }

        if (
            ($this->input('sale_adjust_in_amount') == true) 
            xor 
            ($this->input('sale_adjust_in_party_amount') == true)
        ) {
            // $rules['sale_post_over_and_above'] = ['required', Rule::in(BillSundry::SALE_POST_OVER_AND_ABOVE)];
            $rules['sale_post_over_and_above'] = ['required', 'boolean'];
        }

        if ($this->input('purchase_adjust_in_party_amount') == false) {
            $rules['purchase_party_account_type'] = ['required', Rule::in(BillSundry::PURCHASE_PARTY_ACCOUNT_TYPE)];
        }

        if ($this->input('sale_adjust_in_party_amount') == false) {
            $rules['sale_party_account_type'] = ['required', Rule::in(BillSundry::SALE_PARTY_ACCOUNT_TYPE)];
        }
        

        if ($this->input('purchase_adjust_in_amount') == false && $this->input('purchase_account_type') === 'specify_account') {
            $rules['purchase_account_id'] = 'required|integer|exists:accounts,id';
        }

        if ($this->input('sale_adjust_in_amount') == false && $this->input('sale_account_type') === 'specify_account') {
            $rules['sale_account_id'] = 'required|integer|exists:accounts,id';
        }

        if ($this->input('purchase_adjust_in_amount') == false) {
            $rules['purchase_account_type'] = ['required', Rule::in(BillSundry::PURCHASE_ACCOUNT_TYPE)];
        }
    
        if ($this->input('sale_adjust_in_amount') == false) {
            $rules['sale_account_type'] = ['required', Rule::in(BillSundry::SALE_ACCOUNT_TYPE)];
        }
        
        if ($this->input('purchase_party_account_type') === 'specify_account') {
            $rules['purchase_party_account_id'] = 'required|integer|exists:accounts,id';
        }
    
        if ($this->input('sale_party_account_type') === 'specify_account') {
            $rules['sale_party_account_id'] = 'required|integer|exists:accounts,id';
        }
        
        return $rules;

    }

    public function messages(): array
    {
        return [
            'uuid.unique'  => 'Entry Already Exists.',
        ];
    }
}

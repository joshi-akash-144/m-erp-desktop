<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrokerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('broker.create');
    }

    public function rules(): array
    {
        return [
            'uuid'                     => ['required', 'uuid', 'unique:brokers,uuid'],
            'name'                     => ['required', 'string', 'max:255',
                Rule::unique('brokers')->where(fn($q) =>
                    $q->where('company_id', session('company_id'))->whereNull('deleted_at')
                ),
            ],
            'print_name'               => ['nullable', 'string', 'max:255'],
            'pan'                      => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'mobile_number'            => ['nullable', 'string', 'digits_between:10,15'],
            'email'                    => ['nullable', 'email'],
            'country_id'               => ['nullable', 'exists:countries,id'],
            'state_id'                 => ['nullable', 'exists:states,id'],
            'city'                     => ['nullable', 'string', 'max:255'],
            'postal_code'              => ['nullable', 'string', 'digits_between:5,10'],
            'address_one'              => ['nullable', 'string', 'max:255'],
            'address_two'              => ['nullable', 'string', 'max:255'],
            'sale_commission_rate'     => ['nullable', 'numeric', 'min:0'],
            'purchase_commission_rate' => ['nullable', 'numeric', 'min:0'],
            'bank_name'                => ['nullable', 'string', 'max:255'],
            'bank_branch_name'         => ['nullable', 'string', 'max:255'],
            'bank_account_number'      => ['nullable', 'string', 'digits_between:9,18'],
            'bank_ifsc'                => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];
    }
}

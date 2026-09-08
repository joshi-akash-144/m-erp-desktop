<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\Validation\AccountRules;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('account.create');
    }

    public function rules(): array
    {
        $rules =  array_merge(
            AccountRules::generalInfo(),
            AccountRules::openingBalance(),
            AccountRules::taxDetail(),
            AccountRules::transportDetail(),
            AccountRules::bankDetail(),
            AccountRules::preferenceDetail(),
            [
                'uuid' => ['required', 'uuid', 'unique:accounts,uuid'],
                'name' => ['required', 'string', 'max:255', 
                    Rule::unique('accounts')->where(function ($query) {
                        return $query
                            ->where('company_id', session('company_id'))
                            ->whereNull('deleted_at');
                    })
                ],
            ]
        );
        
      return $rules;
    }

    public function messages(): array
    {
        return [
            'uuid' => 'This entry has already been processed. Please refresh the page to create a new one',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\Validation\AccountRules;


class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('account.update');
    }

    public function rules(): array
    {
         $accountId = $this->route('account')->id ?? null;

        return array_merge(
            AccountRules::generalInfo(),
            AccountRules::openingBalance(),
            AccountRules::taxDetail(),
            AccountRules::transportDetail(),
            AccountRules::bankDetail(),
            AccountRules::preferenceDetail(),
            [
                // 'uuid' => ['required', 'uuid', Rule::unique('accounts', 'uuid')->ignore($accountId)],
                'name' => ['required', 'string', 'max:255',
                Rule::unique('accounts')->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                })->ignore($accountId),
                 
                ],
            ]
        );
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'UUID is required.',
            'uuid.uuid'     => 'UUID must be valid.',
            'account_group_id.required' => 'Account group is required.',
            'account_group_id.exists'   => 'Selected account group does not exist.',
        ];
    }
}

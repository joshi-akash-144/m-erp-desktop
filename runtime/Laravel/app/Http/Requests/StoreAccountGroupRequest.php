<?php

namespace App\Http\Requests;

use App\Models\AccountGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('account_group.create');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('account_groups')->where(function ($query) {
                    return $query
                        ->where('company_id', company_id())
                        ->whereNull('deleted_at');
                }),
            ],
            'uuid' => 'required|uuid|unique:account_groups,uuid',
            'type' => ['required', Rule::in(AccountGroup::TYPE)],
            'parent_id' => ['nullable'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The account group name is required.',
            'name.string'   => 'The account group name must be a valid string.',
            'name.max'      => 'The account group name may not exceed 255 characters.',
            'name.unique'   => 'An account group with this name already exists for this company.',

            'uuid.required' => 'A unique UUID is required for the account group.',
            'uuid.uuid'     => 'The UUID format is invalid.',
            'uuid.unique'   => 'The UUID has already been taken.',

            'type.required' => 'The account group type is required.',
            'type.in'       => 'The selected type must be one of: asset, liability, income, or expense.',

            'parent_id.nullable' => 'The parent group field can be left empty if not applicable.',

            'is_primary.boolean' => 'The primary status must be either true or false.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\AccountGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('account_group.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $accountGroup = $this->route('accountGroup');
            

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('account_groups', 'name')
                    ->where(
                        fn($q) => $q
                            ->where('company_id', session('company_id'))
                            ->whereNull('deleted_at')
                    )
                    ->ignoreModel($accountGroup),
            ],

            'type' => [
                'required',
                Rule::in(AccountGroup::TYPE),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:account_groups,id',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required'   => 'The account group name is required.',
            'name.unique'     => 'An account group with this name already exists in your company.',
            'name.max'        => 'The account group name may not be greater than 255 characters.',

            'type.required'   => 'The account group type is required.',
            'type.in'         => 'The selected account group type is invalid. It must be one of: asset, liability, income, or expense.',

            'parent_id.exists' => 'The selected parent account group is invalid.',
        ];
    }
}

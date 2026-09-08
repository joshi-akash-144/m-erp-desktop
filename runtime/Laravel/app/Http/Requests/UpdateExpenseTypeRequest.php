<?php

namespace App\Http\Requests;

use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateExpenseTypeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('expense_type.update');
    }

    public function rules(): array
    {
        
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_types', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($this->route('expenseType') ? $this->route('expenseType')->id : null),
            ],
            'expense_type_group_id' => [
                'required',
                'exists:account_groups,id',
            ],
            'remarks' => ['nullable', 'string', 'max:255'],
         ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'uuid.unique' => 'Entry Already Exists.',
            'name.required' => 'Expense Type is a required field',
            'name.unique'   => 'Expense Type already exists.',
        ];
    }
    
}
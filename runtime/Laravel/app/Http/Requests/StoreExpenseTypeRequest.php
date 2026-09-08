<?php

namespace App\Http\Requests;

use App\Models\ExpenseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreExpenseTypeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('expense_type.create');
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
            ],
            'expense_type_group_id' => [
                'required',
                'exists:account_groups,id',
            ],
            'remarks' => ['nullable', 'string', 'max:255'],
            'uuid' => 'required|uuid|unique:expense_types,uuid',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'uuid.unique' => 'Entry Already Exists.',
            'name.required' => 'Expense Type is a required field',
            'name.unique'   => 'Expense Type name already exists.',
            'remarks.required' => 'Remarks is a required field',
        ];
    }
    
}
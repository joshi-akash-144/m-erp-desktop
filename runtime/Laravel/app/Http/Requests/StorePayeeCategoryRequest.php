<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayeeCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('payee_category.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payee_category' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payee_categories')->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'uuid' => 'required|uuid|unique:units,uuid',           
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

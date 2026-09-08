<?php

namespace App\Http\Requests;

use App\Models\TaxCategory;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaxCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('tax_category.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_categories')->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'uuid' => 'required|uuid|unique:tax_categories,uuid',
            'type' => ['required', 'string', Rule::in(TaxCategory::TYPES)],
            'igst' => 'nullable|numeric|max:100',
            'cgst' => 'nullable|numeric|max:100',
            'sgst' => 'nullable|numeric|max:100',
            'zero_tax_type' => [
                Rule::requiredIf(function () {
                    return (
                        ($this->input('cgst') == 0 || $this->input('cgst') === null) &&
                        ($this->input('sgst') == 0 || $this->input('sgst') === null) &&
                        ($this->input('igst') == 0 || $this->input('igst') === null)
                    );
                }),
                'required',
                Rule::in(TaxCategory::ZERO_TAX_TYPE),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

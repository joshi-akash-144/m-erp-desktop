<?php

namespace App\Http\Requests;

use App\Models\TaxCategory;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('tax_category.update');
    }

    public function rules(): array
    {
        $taxCategory = $this->route('taxCategory');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_categories', 'name')
                    ->where(fn($query) => $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at') // ignore soft-deleted records
                    )
                    ->ignore($taxCategory->uuid, 'uuid'), // ignore current record by UUID
            ],
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
                'nullable',
                Rule::in(TaxCategory::ZERO_TAX_TYPE),
            ],
        ];
    }
}

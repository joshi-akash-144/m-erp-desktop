<?php

namespace App\Http\Requests;

use App\Models\SaleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sale_type.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $saleType = $this->route('saleType');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_types', 'name')
                ->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                })
                ->ignore($saleType->uuid, 'uuid'),
            ],
            'account_id' => [
                'required',
                'exists:accounts,id',
            ],
            'taxation_type' => ['required', Rule::in(SaleType::TAXABLE_TYPES)],
            'transaction_type' => ['required', Rule::in(SaleType::TRANSACTION_TYPES)],
            'region' => ['required', Rule::in(SaleType::REGIONS)],
            'cgst' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(function () {
                    return $this->input('region') === 'local';
                }),
            ],
            'sgst' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(function () {
                    return $this->input('region') === 'local';
                }),
            ],
            'igst' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(function () {
                    return $this->input('region') === 'interstate';
                }),
            ],
        ];
    }
}

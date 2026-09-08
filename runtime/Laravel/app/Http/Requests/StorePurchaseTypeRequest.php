<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\PurchaseType;

class StorePurchaseTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('purchase_type.create');
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
                Rule::unique('purchase_types')->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                })
            ],
            'uuid' => ['required', 'uuid', 'unique:items,uuid'],
            'account_id' => [
                'required',
                'exists:accounts,id',
            ],
            'taxation_type' => ['required', Rule::in(PurchaseType::TAXABLE_TYPES)],
            'transaction_type' => ['required', Rule::in(PurchaseType::TRANSACTION_TYPES)],
            'region' => ['required', Rule::in(PurchaseType::REGIONS)],
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



    public function messages(): array
    {
        return [
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

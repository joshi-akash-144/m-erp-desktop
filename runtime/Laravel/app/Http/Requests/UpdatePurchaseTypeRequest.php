<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\PurchaseType;

class UpdatePurchaseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase_type.update');
    }

    public function rules(): array
    {
        $purchaseType = $this->route('purchaseType');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_types', 'name')
                ->where(function ($query) {
                    return $query
                        ->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                })
                ->ignore($purchaseType->uuid, 'uuid'),
            ],
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
}

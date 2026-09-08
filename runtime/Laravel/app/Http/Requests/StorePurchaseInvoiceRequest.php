<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidFinancialYearDate;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase_invoice.create');
    }

    public function rules(): array
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        return [
            'uuid' => [
                'required',
                'uuid',
                Rule::unique('purchase_invoices', 'uuid')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],

            'invoice_date' => ['bail', 'required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'party_bill_date' => ['nullable', 'date', 'date_format:Y-m-d'],

            'grn_id' => [
                'nullable',
                'integer',
                Rule::exists('grns', 'id')
                    ->where('company_id', $companyId)
            ],

            'file_number' => ['required', 'string', 'max:255'],
            'sales_invoice_serial' => ['nullable', 'string', 'max:255'],
            'net_total' => ['required', 'numeric', 'gt:0'],
            // ACCOUNT
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('company_id', $companyId)
            ],

            // PURCHASE TYPE
            'purchase_type_id' => [
                'required',
                'integer',
                Rule::exists('purchase_types', 'id')
                    ->where('company_id', $companyId)
            ],

            // UNIQUE REFERENCE NUMBER (company + account + year)
            'reference_number' => [
                'required',
                'string',
                Rule::unique('purchase_invoices', 'reference_number')
                    ->where(
                        fn($q) =>
                        $q->where('company_id', $companyId)
                            ->where('account_id', $this->account_id)
                            ->where('financial_year_id', $financialYearId)
                            ->whereNull('deleted_at')
                    ),
            ],

            // BROKER (IF ANY)
            'broker_id' => [
                'nullable',
                'integer',
                Rule::exists('brokers', 'id')->where('company_id', $companyId)
            ],

            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:500'],

            // ITEMS
            'items' => ['required', 'array', 'min:1'],

            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId)
            ],

            'items.*.quantity' => ['nullable', 'numeric'],
            'items.*.party_quantity' => ['nullable', 'numeric'],
            'items.*.rate' => ['nullable', 'numeric'],
            'items.*.inclusive_rate' => ['nullable', 'numeric'],
            'items.*.amount' => ['required', 'numeric', 'gt:0'],

            'items.*.condition_id' => [
                'nullable',
                Rule::exists('conditions', 'id')->where('company_id', $companyId)
            ],

            'items.*.destination_id' => [
                'nullable',
                Rule::exists('destinations', 'id')->where('company_id', $companyId)
            ],

            'items.*.bag_count' => ['nullable', 'numeric'],

            'items.*.purchase_order_serial'    => ['nullable', 'numeric'],
            'items.*.purchase_order_id'        => ['nullable', 'numeric', 'exists:purchase_orders,id', 'distinct'],
            'items.*.purchase_order_item_id' => ['nullable', 'numeric', 'exists:purchase_order_items,id'],

            'bill_sundries' => ['nullable', 'array'],

            'bill_sundries.*.bill_sundry_id' => [
                'required_with:bill_sundries',
                'integer',
                Rule::exists('bill_sundries', 'id')->where('company_id', $companyId)
            ],

            'bill_sundries.*.bill_sundry_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'required_without:bill_sundries.*.bill_sundry_value'
            ],

            'bill_sundries.*.bill_sundry_value' => [
                'nullable',
                'numeric',
                'min:0',
                'required_without:bill_sundries.*.bill_sundry_percentage'
            ],
            
            'bill_sundries.*.bill_sundry_modal_dr_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId)
            ],

            'bill_sundries.*.bill_sundry_modal_cr_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId)
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('bill_sundries') && is_string($this->bill_sundries)) {
            $this->merge([
                'bill_sundries' => json_decode($this->bill_sundries, true)
            ]);
        }
    }

    public function messages()
    {
        return [
            'uuid.required' => 'UUID is required.',
            'uuid.uuid'     => 'UUID must be valid.',
            'uuid.unique'     => 'Entry Already Exists.',
            'items.*.amount.required' => 'Amount is required.',
        ];
    }
}

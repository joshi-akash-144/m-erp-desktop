<?php

namespace App\Http\Requests;

use App\Rules\ValidFinancialYearDate;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales_invoice.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $salesInvoice = $this->route('salesInvoice');

        return [
            'sales_invoice_id'  => ['required', 'exists:sales_invoices,id'],

            'invoice_serial'    => ['nullable', 'integer', Rule::unique('sales_invoices', 'invoice_serial')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)],

            'invoice_number'    => ['nullable', 'string', Rule::unique('sales_invoices', 'invoice_number')
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)],

            'invoice_date'      => ['required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate($financialYearId)],

            'delivery_challan_number'   => ['nullable', 'string', 'max:255'],

            'sales_order_id'    => ['nullable', 'integer', Rule::exists('sales_orders', 'id')
                ->where('company_id', $companyId)],

            'sales_order_serial' => ['nullable', 'integer', Rule::exists('sales_orders', 'order_serial')
                ->where('company_id', $companyId)],

            'grn_number'        => ['nullable', 'string', 'max:255'],
            'account_id'        => ['required', 'integer', Rule::exists('accounts', 'id')
                ->where('company_id', $companyId)],

            'last_invoice_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            // 'broker_id'         => ['required', 'integer', Rule::exists('accounts', 'id')
            //     ->where('company_id', $companyId)],

            'kms'               => ['nullable', 'numeric', 'min:0'],
            'vehicle_number'            => ['nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{1,4}$/'],
            'party_bill_date'   => ['nullable', 'date', 'date_format:Y-m-d'],

            'sale_type_id'      => ['required', 'integer', Rule::exists('sale_types', 'id')
                ->where('company_id', $companyId)],

            'delivery_date'     => ['bail', 'required', 'date', 'date_format:Y-m-d'],

            // 'ewaybill_number'   => ['nullable', 'numeric', Rule::unique('sales_invoices', 'ewaybill_number')
            //     ->where('company_id', $companyId)],

            'ewaybill_number' => [
                'nullable',
                'numeric',
                Rule::unique('sales_invoices', 'ewaybill_number')
                    ->where(fn($q) => $q->where('company_id', $companyId))
                    ->ignore($salesInvoice->id),
            ],

            'remarks'           => ['nullable', 'string', 'max:1000'],

            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', Rule::exists('items', 'id')
                ->where('company_id', $companyId)],

            'items.*.condition_id'      => ['nullable', Rule::exists('conditions', 'id')
                ->where('company_id', $companyId)],

            'items.*.destination_id'    => ['nullable', Rule::exists('destinations', 'id')
                ->where('company_id', $companyId)],

            'items.*.bag_count' => ['nullable', 'numeric'],
            'items.*.quantity'  => ['nullable', 'numeric'],
            'items.*.party_quantity'    => ['nullable', 'numeric'],
            'items.*.rate'      => ['nullable', 'numeric'],
            'items.*.inclusive_rate'    => ['nullable', 'numeric', 'min:0.01'],
            'items.*.amount'    => ['required', 'numeric', 'min:0.01'],

            'items.*.unit_name' => ['required', 'string', 'max:100'],

            'bill_sundries'     => ['nullable', 'array'],
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
                Rule::exists('accounts', 'id')
                    ->where('company_id', $companyId)
            ],

            'bill_sundries.*.bill_sundry_modal_cr_id' => [
                'nullable',
                Rule::exists('accounts', 'id')
                    ->where('company_id', $companyId)
            ],
        ];
    }

    public function messages()
    {
        return [
            'sales_invoice_id.required' => 'Invoice is required.',
            'vehicle_number.regex' => 'Enter a valid vehicle number (e.g., GJ01AB1234).',
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
}

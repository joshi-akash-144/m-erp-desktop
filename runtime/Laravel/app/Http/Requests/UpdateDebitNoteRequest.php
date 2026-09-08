<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidFinancialYearDate;

class UpdateDebitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('debit_note.update');
    }

    public function rules(): array
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        return [
            'debit_note_id'       => ['required', 'integer', Rule::exists('debit_notes', 'id')->where('company_id', $companyId)],
            'debit_note_date'     => ['required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate($financialYearId)],
            'reference_number'     => [
                'required', 'string', 'max:100',
                Rule::unique('debit_notes', 'reference_number')
                    ->where('company_id', $companyId)
                    ->where('account_id', $this->account_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->debit_note_id),
            ],
            'account_id'           => ['required', 'integer', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'purchase_type_id'         => ['required', 'integer', Rule::exists('purchase_types', 'id')->where('company_id', $companyId)],
            'purchase_invoice_id'     => ['nullable', 'integer', Rule::exists('purchase_invoices', 'id')->where('company_id', $companyId)],
            'purchase_invoice_serial' => ['nullable', 'string', 'max:50'],
            'remarks'              => ['nullable', 'string', 'max:1000'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', Rule::exists('items', 'id')->where('company_id', $companyId)],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.rate'           => ['required', 'numeric', 'min:0'],
            'items.*.amount'         => ['required', 'numeric', 'min:0.01'],
            'items.*.inclusive_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.bag_count'      => ['nullable', 'numeric', 'min:0'],
            'items.*.condition_id'   => ['nullable', Rule::exists('conditions', 'id')->where('company_id', $companyId)],
            'items.*.destination_id' => ['nullable', Rule::exists('destinations', 'id')->where('company_id', $companyId)],
            'items.*.unit_name'      => ['nullable', 'string', 'max:100'],

            'bill_sundries'                          => ['nullable', 'array'],
            'bill_sundries.*.bill_sundry_id'         => ['required_with:bill_sundries', 'integer', Rule::exists('bill_sundries', 'id')->where('company_id', $companyId)],
            'bill_sundries.*.bill_sundry_percentage' => ['nullable', 'numeric', 'min:0'],
            'bill_sundries.*.bill_sundry_value'      => ['nullable', 'numeric', 'min:0'],
            'bill_sundries.*.bill_sundry_modal_dr_id'=> ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'bill_sundries.*.bill_sundry_modal_cr_id'=> ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.unique' => 'This Ref No. is already used for this supplier. Please use a different Ref No.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('bill_sundries') && is_string($this->bill_sundries)) {
            $this->merge(['bill_sundries' => json_decode($this->bill_sundries, true)]);
        }
    }
}

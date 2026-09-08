<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesInvoiceReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales_invoice_receipt.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = company_id();

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('company_id', $companyId)
                    ->where('party_type', 'customer'),
            ],
            'sales_invoice_ids'   => ['required', 'array', 'min:1'],
            'sales_invoice_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('sales_invoices', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}

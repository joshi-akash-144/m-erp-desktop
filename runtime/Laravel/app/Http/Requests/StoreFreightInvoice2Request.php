<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFreightInvoice2Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id'          => ['required', 'integer'],
            'invoice_date'        => ['required', 'date_format:d-m-Y'],
            'total_amount'        => ['required', 'numeric', 'gt:0'],
            
            // Grid array fields
            'destination_id'      => ['required', 'array'],
            'destination_id.*'    => ['required', 'integer'],
            
            'vehicle_id'          => ['required', 'array'],
            'vehicle_id.*'        => ['required', 'integer'],
            
            'bill_date'           => ['required', 'array'],
            'bill_date.*'         => ['required', 'date_format:d-m-Y'],
            
            'km'                  => ['required', 'array'],
            'km.*'                => ['required', 'numeric', 'gt:0'],
            
            'rate'                => ['required', 'array'],
            'rate.*'              => ['required', 'numeric', 'gt:0'],
            
            'amount'              => ['nullable', 'array'],
            'amount.*'            => ['nullable', 'numeric', 'gt:0'],
            
            'code'                => ['nullable', 'array'],
            'code.*'              => ['nullable', 'string', 'max:255'],
            
            'route'               => ['nullable', 'array'],
            'route.*'             => ['nullable', 'string', 'max:255'],
            
            'vendor'              => ['nullable', 'array'],
            'vendor.*'            => ['nullable', 'string', 'max:255'],
            
            'bag'                 => ['nullable', 'array'],
            'bag.*'               => ['nullable'],
            
            'contractor_id'       => ['nullable', 'array'],
            'contractor_id.*'     => ['nullable', 'integer'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'account_id.required'          => 'The Bill To account is required.',
            'invoice_date.required'        => 'The Invoice Date is required.',
            'total_amount.required'        => 'The Total Amount is required.',
            'total_amount.gt'              => 'The Total Amount must be greater than 0.',
            'destination_id.required'      => 'At least one destination is required.',
            'destination_id.*.required'    => 'The Destination field is required in the grid.',
            'vehicle_id.*.required'        => 'The Vehicle No. field is required in the grid.',
            'bill_date.*.required'         => 'The Date field is required in the grid.',
            'km.*.required'                => 'The KM field is required in the grid.',
            'km.*.gt'                      => 'The KM field must be greater than 0 in the grid.',
            'rate.*.required'              => 'The Rate field is required in the grid.',
            'rate.*.gt'                    => 'The Rate field must be greater than 0 in the grid.',
            'amount.*.gt'                  => 'The Amount field must be greater than 0 in the grid.',
        ];
    }
}

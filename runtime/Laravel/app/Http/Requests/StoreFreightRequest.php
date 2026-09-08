<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFreightRequest extends FormRequest
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
            'uuid'                => ['required', 'string'],
            'account_id'          => ['required', 'integer'],
            'invoice_date'        => ['required', 'date_format:d-m-Y'],
            'grn_serial'          => ['nullable'],
            'lr_number'           => ['nullable', 'string', 'max:255'],
            'vehicle_id'          => ['required', 'integer'],
            'item_id'             => ['required', 'integer'],
            'consignor_id'        => ['required', 'integer'],
            'consignee_id'        => ['required', 'integer'],
            'from_destination_id' => ['required', 'integer'],
            'to_destination_id'   => ['required', 'integer'],
            'bag_type'            => ['nullable', 'string'],
            'bag_count'           => ['nullable', 'numeric'],
            'net_weight'          => ['nullable', 'numeric'],
            'kms'                 => ['nullable', 'numeric'],
            'freight_rate'        => ['nullable', 'numeric'],
            'total_amount'        => ['required', 'numeric', 'gt:0'],
            'remarks'             => ['nullable', 'string'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'account_id.required'          => 'The Bill To account is required.',
            'invoice_date.required'        => 'The Invoice Date is required.',
            'vehicle_id.required'          => 'The Vehicle is required.',
            'item_id.required'             => 'The Item Name is required.',
            'consignor_id.required'        => 'The Consignor is required.',
            'consignee_id.required'        => 'The Consignee is required.',
            'from_destination_id.required' => 'The From Destination is required.',
            'to_destination_id.required'   => 'The To Destination is required.',
            'total_amount.required'        => 'The Freight Total Amount is required.',
            'total_amount.gt'              => 'The Freight Total Amount must be greater than 0.',
        ];
    }
}

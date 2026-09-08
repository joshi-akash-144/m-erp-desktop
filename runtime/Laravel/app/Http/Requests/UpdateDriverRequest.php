<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'account_id' => 'nullable|integer',
            'account_group_id' => 'required|integer',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'date_of_joining' => 'nullable|date_format:Y-m-d',
            'license_number' => 'nullable|string|max:50',
            'adhara_number' => 'nullable|string|max:20',            
            'religion' => 'nullable|string|max:50',
            'qualification' => 'nullable|string|max:100',
            'marital_status' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'salary' => 'nullable|numeric',
            'status' => 'nullable|boolean',
            'license_category' => 'nullable|string|max:50',
            'license_issuing_authority' => 'nullable|string|max:100',
            'license_expiry_date_tr' => 'nullable|date_format:Y-m-d',
            'license_expiry_date_nt' => 'nullable|date_format:Y-m-d',
            'remarks' => 'nullable|string',
            // Bank detail fields (stored on the linked Account)
            'bank_name' => 'nullable|string|max:255',
            'bank_branch_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:11',
            'cheque_id' => 'nullable|integer',
            // Opening balance fields (stored on the linked Account)
            'opening_balance' => 'required|numeric',
            'opening_type' => 'required|in:D,C',
            // 'party_type' => 'nullable|string',
            // Contact info (stored on the linked Account)
            'mobile_number' => 'nullable|string|max:20',
            'pan' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'postal_code' => 'nullable|string|max:20',
            'address_one' => 'nullable|string|max:255',
        ];
    }
}

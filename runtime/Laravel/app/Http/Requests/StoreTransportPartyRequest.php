<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport_party.create');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transport_parties', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
            ],
            'state_id' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'mobile_number' => 'nullable|string|max:25',
            'gst_number' => 'nullable|string|max:15',
            'address_one' => 'nullable|string',
            'address_two' => 'nullable|string',
            'email' => 'nullable|email|max:255',
        ];
    }
}

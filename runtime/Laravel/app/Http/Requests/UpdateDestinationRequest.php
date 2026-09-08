<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDestinationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('destination.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $destination = $this->route('destination');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('destinations', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($destination->uuid, 'uuid'), // ignore current UUID
            ],
            'contact_person_name' => ['nullable','string', 'max:255'],
            'email' => ['nullable','string', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'kms' => ['nullable', 'numeric', 'min:0'],
            'address_one' => ['nullable', 'string', 'max:255'],
            'address_two' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'taluka' => ['nullable', 'string', 'max:255'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'postal_code' => ['nullable', 'digits:6', 'regex:/^[1-9][0-9]{5}$/'],
        ];
    }
}

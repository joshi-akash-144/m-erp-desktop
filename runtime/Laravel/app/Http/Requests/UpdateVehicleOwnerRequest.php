<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleOwnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle_owner.update');
    }

    /** 
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicleOwner = $this->route('vehicleOwner');
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vehicle_owners', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($vehicleOwner->uuid, 'uuid'), 
            ],
        ];
    }
}

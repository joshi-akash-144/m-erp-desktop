<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'vehicle_owner_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'mfg_year' => ['nullable', 'string', 'max:10'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'chassis_no' => ['nullable', 'string', 'max:255'],
            'engine_no' => ['nullable', 'string', 'max:255'],
            'fuel_type' => ['nullable', 'integer'],
            'power_cc' => ['nullable', 'string', 'max:255'],
            'fuel_tank_capacity' => ['nullable', 'string', 'max:255'],
            'gross_weight' => ['nullable', 'numeric'],
            'unladen_weight' => ['nullable', 'numeric'],
            'weight_capacity' => ['nullable', 'numeric'],
            'license_number' => ['nullable', 'regex:/^[A-Z]{2}-?[0-9]{2}[0-9]{4}[0-9]{7}$/i'],
            'renewal_date' => ['nullable', 'date'],
            'insurance_company_name' => ['nullable', 'string', 'max:255'],
            'insurance_policy_no' => ['nullable', 'regex:/^[A-Za-z0-9\/-]{5,30}$/'],
            'national_permit_due_date' => ['nullable', 'date'],
            'fitness_due_date' => ['nullable', 'date'],
            // 'agent_name' => ['nullable', 'string', 'max:255'],
            'policy_due_date' => ['nullable', 'date'],
            'passing_due_date' => ['nullable', 'date'],
            'tax_due_date' => ['nullable', 'date'],
            'permit_due_date' => ['nullable', 'date'],
            'puc_no' => ['nullable', 'string', 'max:255'],
            'puc_due_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreZoneRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('zone.create');
    }

    public function rules(): array
    {
        
        $rules = [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('zones', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
            ],
            'rate' => ['required', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'uuid' => 'required|uuid|unique:zones,uuid',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'uuid.unique' => 'Entry Already Exists.',
            'name.required' => 'Zone Name is a required field',
            'name.unique'   => 'Zone name already exists.',
            'rate.required' => 'Zone Rate is a required field',
            'remarks.required' => 'Zone Remarks is a required field',
        ];
    }
    
}
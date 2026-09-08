<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('unit_conversion.update');
    }

    public function rules(): array
    {
        return [
            'main_unit_id' => [
                'required',
                'exists:units,id',
                // ✅ Unique rule with ignore for update
                Rule::unique('unit_conversions')
                    ->where(fn($query) => $query->where('company_id', session('company_id'))
                                               ->where('sub_unit_id', $this->sub_unit_id))
                                               ->whereNull('deleted_at')
                    ->ignore($this->route('unitConversion'), 'uuid'), // ✅ ignores current record
            ],
            'sub_unit_id' => ['required', 'exists:units,id'],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->main_unit_id === $this->sub_unit_id) {
                $validator->errors()->add('sub_unit_id', 'Main Unit and Sub Unit cannot be the same.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'main_unit_id.unique' => 'This Main Unit and Sub Unit combination already exists.',
        ];
    }
}
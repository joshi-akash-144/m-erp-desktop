<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitConversionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('unit_conversion.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'main_unit_id' => [
                'required',
                'exists:units,id',
                Rule::unique('unit_conversions')
                    ->where(fn($query) => $query->where('company_id', session('company_id'))
                                                ->whereNull('deleted_at')
                                               ->where('sub_unit_id', $this->sub_unit_id)),
            ],
            'sub_unit_id' => ['required', 'exists:units,id'],
            'uuid' => 'required|uuid|unique:units,uuid',
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
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

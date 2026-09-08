<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDairyParameterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('dairy_parameter.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => 'required|uuid|unique:dairy_parameters,uuid',
            'condition_id' => 'nullable|numeric|max:100',
            'element_id' => 'nullable|numeric|max:100',
            'guarantee' => 'nullable|numeric|max:100',
            'from' => 'required',
            'to'=> 'required',
            'difference'  => 'nullable|array',
            'difference.*'=> 'nullable|numeric',
            'rebate' => 'required',
            'premium'=> 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

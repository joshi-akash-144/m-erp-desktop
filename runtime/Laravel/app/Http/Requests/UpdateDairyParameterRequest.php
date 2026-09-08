<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDairyParameterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('dairy_parameter.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'condition_id' => 'nullable|numeric|max:100',
            'element_id' => 'nullable|numeric|max:100',
            'guarantee' => 'nullable|numeric|max:100',
            'from' => 'required',
            'to'=> 'required',
            'difference' => 'required',
            'rebate' => 'required',
            'premium'=> 'required'
        ];
    }

}

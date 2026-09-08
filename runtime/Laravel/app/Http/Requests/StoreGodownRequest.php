<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGodownRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('godown.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'godown_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('godowns')->where(function ($query) {
                    return $query->where('company_id', session('company_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'uuid' => 'required|uuid|unique:godowns,uuid',
            'destination_id' => 'required|string|max:255',
            'remark' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.unique'  => __('messages.common.uuid'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('item_group.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid'=>'required|uuid|unique:item_groups,uuid',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('item_groups')->where(function ($query) {
                    return $query->where('company_id', session('company_id'));
                })
            ],
        ];
    }
}

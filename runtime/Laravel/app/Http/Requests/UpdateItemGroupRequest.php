<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('item_group.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemGroup = $this->route('itemGroup');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('item_groups', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->ignore($itemGroup->uuid, 'uuid'), // ignore current UUID
            ],
        ];
    }
}

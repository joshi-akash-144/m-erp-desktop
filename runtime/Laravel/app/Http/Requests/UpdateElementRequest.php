<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateElementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('element.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $element = $this->route('element');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('elements', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($element->uuid, 'uuid'), // ignore current UUID
            ],
            'print_name' => [
                'required',
                'string',
                'max:255',
            ],
            'range' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}

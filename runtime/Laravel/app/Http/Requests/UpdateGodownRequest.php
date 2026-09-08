<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGodownRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('godown.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $godown = $this->route('godown');
        return [
            'godown_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('godowns', 'godown_name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($godown->uuid, 'uuid'), // ignore current UUID
            ],
            'destination_id' => 'required|string|max:255',
            'remark' => 'nullable|string|max:500',            
        ];
    }
}

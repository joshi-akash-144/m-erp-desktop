<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\TdsCategory;

class UpdateTdsCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('tds_category.update');
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('section')) {
            $mergeData['section'] = strtoupper(trim($this->section));
        }

        if ($this->has('details')) {
            $mergeData['details'] = collect($this->details)->filter(function ($detail) {
                return !empty($detail['payee_category_id']);
            })->values()->toArray();
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tdsCategory = $this->route('tdsCategory');
        return [
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tds_categories', 'category_name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($tdsCategory->uuid, 'uuid'), // ignore current UUID
            ],
            'section' => [
                'required',
                'string',
                Rule::unique('tds_categories', 'section')
                    ->where('company_id', session('company_id'))
                    ->whereNull('deleted_at')
                    ->ignore($tdsCategory->id),
            ],
            'default_account_id' => 'nullable|exists:accounts,id',
            'details' => 'nullable|array',
            'details.*.payee_category_id' => 'required|exists:payee_categories,id|distinct',
            'details.*.threshold_limit' => 'nullable|numeric',
            'details.*.tds_with_pan' => 'nullable|numeric',
            'details.*.tds_without_pan' => 'nullable|numeric',
        ];
    }
}

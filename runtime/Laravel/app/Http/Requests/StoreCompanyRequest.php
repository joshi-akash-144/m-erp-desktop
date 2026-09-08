<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('company.create');
  }

  public function rules(): array
  {
    $rules = [
      'uuid' => ['required', 'uuid', 'unique:companies,uuid'],
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('companies')->where(function ($query) {
          return $query
            ->whereNull('deleted_at');
        }),
      ],
      'print_name' => 'required|string|max:255',
      'legal_name' => 'required|string|max:255',
      'country_id'        => ['required', 'exists:countries,id'],
      'state_id'          => ['required', 'exists:states,id'],
      'financial_year_start' => 'required|date|date_format:Y-m-d',
      'address_one' => 'nullable|string',
      'address_two' => 'nullable|string',
      'cin' => 'nullable|string|max:255',
      'pan' => 'nullable|string|max:255',
      'phone_no' => 'nullable|string|max:255',
      'email' => 'nullable|email|max:255',
      'gst_number' => 'nullable|string|max:15',
      'currency' => 'nullable|string|max:255',
      'type_of_dealer'       => ['nullable', Rule::in(Company::TYPE_OF_DEALER)],
      'tds_applicable'    => 'boolean',
    ];

    if (
      isset($data['type_of_dealer']) &&
      in_array($data['type_of_dealer'], array_diff(Company::TYPE_OF_DEALER, ['unregistered']), true)
    ) {
      $rules['gst_number'] = ['required', 'string', 'size:15'];
    }

    return $rules;
  }

  protected function prepareForValidation()
  {
      $this->merge([
          'tds_applicable' => $this->boolean('tds_applicable'),
      ]);
  }

  public function messages(): array
  {
    return [
      'uuid' => 'This entry has already been processed. Please refresh the page to create a new one',
    ];
  }
}

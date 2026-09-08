<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('company.update');
  }

  public function rules(): array
  {
    $company = $this->route('company');
    
    return [
      // 'uuid' => ['required', 'uuid', 'unique:companies,uuid'],
      'name' => [
          'nullable',
          'string',
          'max:255',
          Rule::unique('companies')->ignore($company, 'id')->where(function ($query) {
              return $query
                  ->whereNull('deleted_at');
          })
      ],
      'print_name' => 'nullable|string|max:255',
      'legal_name' => 'nullable|string|max:255',
      'country_id'        => ['nullable', 'exists:countries,id'],
      'state_id'          => ['nullable', 'exists:states,id'],
      'address_one' => 'nullable|string',
      'address_two' => 'nullable|string',
      'cin' => 'nullable|string|max:255',
      'pan' => 'nullable|string|max:255',
      'mobile_number' => 'nullable|string|max:255',
      'phone_no' => 'nullable|string|max:255',
      'email' => 'nullable|email|max:255',
      'gst_number' => 'nullable|string|max:255',
      'currency' => 'nullable|string|max:255',
      'type_of_dealer'       => ['nullable', Rule::in(Company::TYPE_OF_DEALER)],
      'tds_applicable'    => 'boolean',
    ];
  }

  protected function prepareForValidation()
  {
      $this->merge([
          'tds_applicable' => $this->boolean('tds_applicable'),
      ]);
  }
}

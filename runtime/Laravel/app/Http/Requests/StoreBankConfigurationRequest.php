<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBankConfigurationRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return $this->user()->can('bank_configuration.create');
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'bank_id' => [
        'required',
        'numeric',
        Rule::unique('bank_configurations')->where(function ($query) {
          return $query->where('company_id', session('company_id'));
        })
      ],
      'rtgs_id' => [
        'required',
        'numeric',
        Rule::unique('bank_configurations')->where(function ($query) {
          return $query->where('company_id', session('company_id'));
        })
      ],
      'cheque_id' => [
        'required',
        'numeric',
        Rule::unique('bank_configurations')->where(function ($query) {
          return $query->where('company_id', session('company_id'));
        })
      ],
    ];
  }
}

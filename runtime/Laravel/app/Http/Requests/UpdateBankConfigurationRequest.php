<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankConfigurationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('bank_configuration.update');
  }

  public function rules(): array
  {
    $bankConfiguration = $this->route('bankConfiguration');

    return [
      'bank_id' => [
        'required',
        'numeric',
      ],
      'rtgs_id' => [
        'required',
        'numeric',
      ],
      'cheque_id' => [
        'required',
        'numeric',
      ],
    ];
  }
}

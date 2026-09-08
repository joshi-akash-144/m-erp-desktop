<?php

namespace App\Http\Requests;

use App\Rules\ValidFinancialYearDate;
use Illuminate\Foundation\Http\FormRequest;

class LedgerReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('ledger_report.list');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date'      => ['bail', 'required', 'date', 'date_format:Y-m-d', 'before_or_equal:end_date', new ValidFinancialYearDate],
            'end_date'        => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date', new ValidFinancialYearDate],
            'ledger_by'       => 'in:single,full,other',
            'narration'       => 'required|boolean',
            'account_id'      => 'nullable|exists:accounts,id',
            'against_account_id' => 'nullable|exists:accounts,id',
            'voucher_type_id' => 'nullable|exists:voucher_types,id',
        ];
    }
}

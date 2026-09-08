<?php

namespace App\Http\Requests;

use App\Models\DeliveryChallan;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidFinancialYearDate;
use Illuminate\Validation\Rule;

class StoreDeliveryChallanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()->can('delivery_challan.create');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $challanId = $this->route('delivery_challan') ? $this->route('delivery_challan')->id : null;

        return [
            'uuid'              => ['required', 'uuid', Rule::unique('delivery_challans', 'uuid')->ignore($challanId)],
            'challan_date'      => ['bail', 'required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'challan_in_date'   => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:challan_date', new ValidFinancialYearDate],
            'challan_out_date'  => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:challan_in_date'],
            'contract_number'   => ['nullable', 'string', 'max:255'],
            'vehicle_number'    => ['nullable', 'string', 'max:255'],
            'reference_number' => [
                'nullable',
                'string',
                Rule::unique('delivery_challans', 'reference_number')
                    ->where(fn($q) =>
                        $q->where('company_id', $companyId)
                          ->where('account_id', $this->account_id)
                          ->where('financial_year_id', $financialYearId)
                          ->whereNull('deleted_at')
                    )->ignore($challanId),
            ],
            'account_id'        => ['required', 'exists:accounts,id'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
            'broker_id'         => ['nullable', 'exists:accounts,id'],
            'gross_weight'      => ['nullable', 'numeric', 'min:0'],
            'tare_weight'       => ['nullable', 'numeric', 'min:0'],
            'bag_count'         => ['nullable', 'numeric', 'min:0'],
            'bag_type'          => ['nullable', 'in:' . DeliveryChallan::BAG_PLASTIC . ',' . DeliveryChallan::BAG_GUNNY],

            'items'                          => ['required', 'array', 'min:1'],
            'items.*.item_id'                => ['required', 'exists:items,id'],
            'items.*.quantity'               => ['required', 'numeric', 'min:0.0001'],
            'items.*.party_quantity'         => ['nullable', 'numeric'],
            'items.*.rate'                   => ['required', 'numeric', 'min:0'],
            'items.*.inclusive_rate'         => ['nullable', 'numeric'],
            'items.*.amount'                 => ['required', 'numeric'],
            'items.*.condition_id'           => ['nullable', 'exists:conditions,id'],
            'items.*.destination_id'         => ['nullable', 'exists:destinations,id'],
            'items.*.bag_count'              => ['nullable', 'numeric'],
            'items.*.sales_order_id'         => ['nullable', 'exists:sales_orders,id'],
            'items.*.sales_order_item_id'    => ['nullable', 'exists:sales_order_items,id'],
            'items.*.sales_order_serial'     => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required'         => 'UUID is required.',
            'uuid.uuid'             => 'Invalid UUID format.',
            'uuid.unique'           => 'This entry already exists.',

            'challan_date.required'     => 'Challan date is required.',
            'challan_date.date'         => 'Challan date must be a valid date.',
            'challan_date.date_format'  => 'Challan date must be in Y-m-d format.',

            'challan_in_date.date'         => 'In date must be a valid date.',
            'challan_in_date.date_format'  => 'In date must be in Y-m-d format.',

            'challan_out_date.date'         => 'Out date must be a valid date.',
            'challan_out_date.date_format'  => 'Out date must be in Y-m-d format.',

            'account_id.required'   => 'Customer is required.',
            'account_id.exists'     => 'The selected customer is invalid.',

            'reference_number.unique' => 'This Party Bill No. has already been used for this customer.',

            'items.required'        => 'At least one item is required.',
            'items.*.item_id.required' => 'Item is required.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.rate.required'     => 'Rate is required.',
            'items.*.amount.required'   => 'Amount is required.',
        ];
    }
}

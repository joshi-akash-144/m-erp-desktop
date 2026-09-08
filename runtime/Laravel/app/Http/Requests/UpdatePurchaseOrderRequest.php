<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\Rules\ValidFinancialYearDate;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('purchase_order.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // $purchaseOrderId = $this->route('purchaseOrder')->id ?? null;

        return [
            // 'id'                        => ['required',  'exists:purchase_orders,id'],
            'order_date'                => ['bail', 'required', 'date', 'date_format:Y-m-d', 'before_or_equal:due_date', new ValidFinancialYearDate],
            'delivery_days'             => ['required','numeric', 'min:0'],
            'due_date'                  => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:order_date'],
            'broker_id'                 => ['required', 'exists:accounts,id'],
            'contract_number'           => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'contract_number')
                    ->ignore($this->route('purchaseOrder'))
                    ->where(function ($query) {
                        return $query->where('company_id', session('company_id') ?? company_id())
                            ->where('financial_year_id', session('financial_year_id') ?? financial_year_id())
                            ->where('broker_id', $this->broker_id)
                            ->whereNull('deleted_at');
                    })
            ],
            'account_id'                => ['required', 'exists:accounts,id'], 
            'destination_id'            => ['required', 'nullable', 'exists:destinations,id'],
            'remarks'                   => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.item_id'           => ['required', 'exists:items,id', 'distinct'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'              => ['required', 'numeric', 'min:0.01'],
            'items.*.inclusive_rate'    => ['nullable', 'numeric', 'min:0.01'],
            'items.*.amount'            => ['required', 'numeric', 'min:0.01'],
            // 'items.*.unit_name'         => ['required', 'string', 'max:100'],
            'items.*.condition_id'      => ['nullable', 'exists:conditions,id'],
        ];
    }



    public function messages(): array
    {
        return [
            'uuid.required'                => 'A unique identifier (UUID) is required.',
            'uuid.uuid'                    => 'The provided UUID is not valid.',
            'uuid.unique'                  => 'This purchase order already exists.',

            'order_date.required'          => 'Please enter the order date.',
            'order_date.date'              => 'The order date must be a valid date.',
            'order_date.date_format'       => 'The order date format must be YYYY-MM-DD.',
            'order_date.before_or_equal'   => 'The order date cannot be after the due date.',

            'broker_id.required'           => 'Please select a broker.',
            'broker_id.exists'             => 'The selected broker is invalid.',

            'account_id.required'          => 'Please select supplier.',
            'account_id.exists'            => 'The selected supplier is invalid.',

            'contract_number.string'       => 'The contract number must be a valid text.',
            'contract_number.max'          => 'The contract number may not exceed 255 characters.',
            'contract_number.unique'       => 'Duplicate Contract Number Found !!.',

            'due_date.date'                => 'The due date must be a valid date.',
            'due_date.date_format'         => 'The due date format must be YYYY-MM-DD.',
            'due_date.after_or_equal'      => 'The due date cannot be earlier than the order date.',

            
            'destination_id.required'      => 'Please select a delivery destination.',
            'destination_id.exists'        => 'The selected destination is invalid.',

            'delivery_days.numeric'        => 'Delivery days must be a valid number.',
            'delivery_days.min'            => 'Delivery days cannot be negative.',

            'remarks.string'               => 'Remarks must be a valid text.',
            'remarks.max'                  => 'Remarks may not exceed 1000 characters.',

            'items.required'               => 'Please add at least one item.',
            'items.array'                  => 'The items data format is invalid.',
            'items.min'                    => 'At least one item must be added to the order.',

            'items.*.item_id.required'     => 'Please select an item.',
            'items.*.item_id.exists'       => 'One or more selected items are invalid.',
            'items.*.item_id.distinct'     => 'Duplicate items are not allowed.',

            'items.*.quantity.required'    => 'Quantity is required for each item.',
            'items.*.quantity.numeric'     => 'Quantity must be a valid number.',
            'items.*.quantity.min'         => 'Quantity must be greater than zero.',

            'items.*.rate.required'        => 'Rate is required for each item.',
            'items.*.rate.numeric'         => 'Rate must be a valid number.',
            'items.*.rate.min'             => 'Rate must be greater than zero.',

            'items.*.inclusive_rate.numeric' => 'Inclusive rate must be a valid number.',
            'items.*.inclusive_rate.min'     => 'Inclusive rate must be greater than zero.',

            'items.*.amount.required'      => 'Amount is required for each item.',
            'items.*.amount.numeric'       => 'Amount must be a valid number.',
            'items.*.amount.min'           => 'Amount must be greater than zero.',

            'items.*.unit_name.required'   => 'Unit name is required for each item.',
            'items.*.unit_name.string'     => 'Unit name must be a valid text.',
            'items.*.unit_name.max'        => 'Unit name may not exceed 100 characters.',

            'items.*.condition_id.exists'  => 'The selected condition is invalid.',
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fyStart = session('financial_year_start');
            $fyEnd   = session('financial_year_end');
            if ($this->order_date < $fyStart || $this->order_date > $fyEnd) {
                $validator->errors()->add('order_date', 'Order date must be within financial year.');
            }
        });
    }
}

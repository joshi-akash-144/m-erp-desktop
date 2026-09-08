<?php

namespace App\Http\Requests;

use App\Models\Grn;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidFinancialYearDate;
use Illuminate\Validation\Rule;

class StoreGrnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()->can('grn.create');
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->has('entry_from')) {
            $entryFrom = Grn::ENTRY_FROM_OFFICE;
            
            // Check if the route is for mobile GRN
            if ($this->routeIs('*mobile-grns*')) {
                $entryFrom = Grn::ENTRY_FROM_MOBILE;
            }
            
            $this->merge(['entry_from' => $entryFrom]);
        }
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

        return [
            'uuid'              => ['required', 'uuid', 'unique:grns,uuid'],
            'grn_date'          => ['bail', 'required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'grn_in_date'       => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:grn_out_date', new ValidFinancialYearDate],
            'grn_out_date'      => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:grn_in_date'],
            'contract_number'   => ['nullable', 'string', 'max:255'],
            'vehicle_number'    => ['nullable', 'string', 'max:255'],
            'reference_number' => [
                'required',
                'string',
                Rule::unique('grns', 'reference_number')
                    ->where(fn($q) =>
                        $q->where('company_id', $companyId)
                          ->where('account_id', $this->account_id)
                          ->where('financial_year_id', $financialYearId)
                          ->whereNull('deleted_at')
                    ),
            ],
            'account_id'        => ['required', 'exists:accounts,id'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
            'broker_id'         => ['nullable','exists:accounts,id'],
            'gross_weight'      => ['nullable','numeric', 'min:0'],
            'tare_weight'       => ['nullable','numeric', 'min:0'],
            'bag_count'         => ['nullable','numeric', 'min:0'],
            'bag_type'          => ['required', 'in:' . Grn::BAG_PLASTIC . ',' . Grn::BAG_GUNNY],
            'party_bill_date'   => ['nullable', 'date', 'date_format:Y-m-d'],
            'url_path'          => ['nullable','file','mimes:jpg,jpeg,png,webp','max:10240'],
            'entry_from'        => ['required', 'in:' . Grn::ENTRY_FROM_GODOWN . ',' . Grn::ENTRY_FROM_MOBILE . ',' . Grn::ENTRY_FROM_OFFICE],

            'items'                            => ['required', 'array', 'min:1'],
            'items.*.item_id'                  => ['required', 'exists:items,id'],
            'items.*.quantity'                 => ['nullable', 'numeric'],
            'items.*.party_quantity'           => ['nullable' ,'numeric'],
            'items.*.rate'                     => ['numeric'],
            'items.*.inclusive_rate'           => ['nullable', 'numeric'],
            'items.*.amount'                   => ['nullable','numeric'],
            'items.*.condition_id'             => ['nullable', 'exists:conditions,id'],
            'items.*.destination_id'           => ['nullable', 'exists:destinations,id'],
            'items.*.bag_count'                => ['nullable','numeric'],
            'items.*.purchase_order_serial'    => ['nullable','numeric'],
            'items.*.purchase_order_id'        => ['nullable','numeric', 'exists:purchase_orders,id','distinct'],
            'items.*.purchase_order_item_id' => ['nullable','numeric', 'exists:purchase_order_items,id'],
        ];
    }

    public function messages(): array
    {
        return [

            'uuid.required'         => 'UUID is required.',
            'uuid.uuid'             => 'Invalid UUID format.',
            'uuid.unique'           => 'This Entry already exists.',

            'grn_date.required'     => 'GRN date is required.',
            'grn_date.date'         => 'GRN date must be a valid date.',
            'grn_date.date_format'  => 'GRN date must be in Y-m-d format.',

            'grn_in_date.date'         => 'In date must be a valid date.',
            'grn_in_date.date_format'  => 'In date must be in Y-m-d format.',

            'grn_out_date.date'         => 'Out date must be a valid date.',
            'grn_out_date.date_format'  => 'Out date must be in Y-m-d format.',

            'contract_number.string' => 'Contract number must be valid text.',
            'contract_number.max'    => 'Contract number cannot exceed 255 characters.',

            'account_id.required'   => 'Party is required.',
            'account_id.exists'     => 'The selected party is invalid.',

            'broker_id.required'    => 'Broker is required.',
            'broker_id.exists'      => 'The selected broker is invalid.',

            'reference_number.string' => 'Reference number must be valid text.',
            'reference_number.max'    => 'Reference number cannot exceed 255 characters.',

            'remarks.string'        => 'Remarks must be valid text.',
            'remarks.max'           => 'Remarks cannot exceed 1000 characters.',

            'gross_weight.numeric'  => 'Gross weight must be numeric.',
            

            'tare_weight.numeric'   => 'Tare weight must be numeric.',
            

            'bag_count.numeric'     => 'Bag count must be numeric.',
            

            'bag_type.in'           => 'Invalid bag type selected.',

            // Items Validation
            'items.required'        => 'At least one item is required.',
            'items.array'           => 'Items must be a valid array.',
            'items.min'             => 'At least one item is required.',

            'items.*.item_id.required' => 'Item is required.',
            'items.*.item_id.exists'   => 'Selected item is invalid.',
            

            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.numeric'  => 'Quantity must be numeric.',
            'items.*.quantity.min'      => 'Quantity must be at least 0.01.',

            'items.*.rate.numeric'      => 'Rate must be numeric.',
            'items.*.rate.min'          => 'Rate must be at least 0.01.',

            'items.*.inclusive_rate.numeric' => 'Inclusive rate must be numeric.',
            'items.*.inclusive_rate.min'     => 'Inclusive rate must be at least 0.01.',

            'items.*.amount.numeric'   => 'Amount must be numeric.',
            'items.*.amount.min'       => 'Amount must be at least 0.01.',

            'items.*.condition_id.exists'   => 'Invalid condition selected.',
            'items.*.destination_id.exists' => 'Invalid destination selected.',

            'items.*.bags.numeric'  => 'Bag count must be numeric.',
            'items.*.bags.min'      => 'Bag count must be at least 0.01.',

            'items.*.order_number.numeric' => 'Order number must be numeric.',
            'items.*.order_number.min'     => 'Order number must be at least 0.01.',

            'items.*.order_id.numeric'  => 'Order ID must be numeric.',
            'items.*.order_id.min'      => 'Order ID must be at least 0.01.',
            'items.*.order_id.exists'   => 'Selected order is invalid.',
            'items.*.order_id.distinct' => 'Duplicate Order are not allowed.',

            'items.*.detail_id.numeric' => 'Order detail ID must be numeric.',
            'items.*.detail_id.min'     => 'Order detail ID must be at least 0.01.',
            'items.*.detail_id.exists'  => 'Selected order detail is invalid.',
        ];
    }
}

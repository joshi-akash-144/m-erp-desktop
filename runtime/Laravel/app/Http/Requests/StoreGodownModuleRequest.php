<?php

namespace App\Http\Requests;

use App\Rules\ValidFinancialYearDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGodownModuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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

        return [
            'uuid'               => ['required', 'uuid', 'unique:godown_module,uuid'],
            
            // Status & Type Indicators
            'in_out_status'      => ['required', 'in:in,out'],
            'is_manual'          => ['required', 'boolean'],
            'is_crossing'        => ['nullable', 'boolean'],
            'is_cycle'           => ['nullable', 'in:open,close'],

            // GRN Reference
            'grn_id'             => ['nullable', 'exists:grns,id'],
            'grn_serial'         => ['nullable', 'integer'],
            'grn_date'           => ['nullable', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'gst_type'           => ['nullable', 'in:local,interstate'],

            // Core Dropdowns
            'destination_id'     => ['required', 'exists:destinations,id'],
            'godown_unit_location_id' => ['required', 'exists:godown_unit_locations,id'],
            'item_id'            => ['required', 'exists:items,id'],
            'account_id'         => ['required', 'exists:accounts,id'],
            'broker_id'          => ['nullable', 'exists:accounts,id'],
            'party_destination_id' => ['nullable', 'exists:destinations,id'],

            // Reference Numbers
            'reference_number'   => ['nullable', 'string', 'max:255'], // Party Bill No
            'vehicle_number'     => ['required', 'string', 'max:255'],
            'transporter_id'     => ['required', 'exists:transporters,id'],
            'lr_number'          => ['required', 'string', 'max:255'],
            'delivery_challan_id' => ['nullable', 'exists:delivery_challans,id'],
            'dc_serial'          => ['nullable', 'integer'],
            'dc_date'            => ['nullable', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'po_no'              => ['nullable', 'exists:purchase_orders,id'],
            'purchase_order_id'  => ['nullable', 'exists:purchase_orders,id'],
            'so_no'              => ['nullable', 'exists:sales_orders,id'],
            'sale_order_id'      => ['nullable', 'exists:sales_orders,id'],

            // Dates & Times
            'date_in'            => ['required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'time_in'            => ['nullable', 'date_format:H:i'],
            
            // Out fields: nullable for 'In' entry, but might be required for 'Out'
            'date_out'           => [
                Rule::requiredIf($this->in_out_status === 'out'),
                'nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_in'
            ],
            'time_out'           => ['nullable', 'date_format:H:i'],

            // Weights
            'challan_weight'     => ['nullable', 'numeric', 'min:0'],
            'gross_weight'       => ['nullable', 'numeric', 'min:0'],
            'tare_weight'        => ['nullable', 'numeric', 'min:0'],
            'net_weight'         => ['nullable', 'numeric'],
            
            // Quantity & Rates
            'p_qty'              => ['nullable', 'numeric', 'min:0'], // Party Qty
            'total_quantity'     => ['nullable', 'numeric', 'min:0'],
            'rate'               => ['nullable', 'numeric', 'min:0'],
            'sub_total'          => ['nullable', 'numeric', 'min:0'],

            // Bags
            'bag_type'           => ['nullable', 'in:plastic,gunny'],
            'bag_count'          => ['nullable', 'integer', 'min:0'],
            'challan_bags'       => ['nullable', 'numeric', 'min:0'],
            'net_weight_wt_bag'  => ['nullable', 'numeric'],

            'remarks'            => ['nullable', 'string', 'max:1000'],
            'godown_module_id' => ['nullable', 'integer', 'exists:godown_module,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'uuid.required'           => 'UUID is required.',
            'uuid.uuid'               => 'Invalid UUID format.',
            'uuid.unique'             => 'This entry already exists.',

            'in_out_status.required'  => 'Status (In/Out) is required.',
            'is_manual.required'      => 'Manual flag is required.',

            'grn_id.exists'           => 'The selected GRN is invalid.',
            'grn_date.date'           => 'GRN date must be a valid date.',
            'grn_date.date_format'    => 'GRN date must be in Y-m-d format.',

            'delivery_challan_id.exists' => 'The selected Delivery Challan is invalid.',
            'dc_date.date'           => 'The selected Delivery Challan date is invalid.',
            'dc_date.date_format'    => 'The selected Delivery Challan date must be in Y-m-d format.',

            'destination_id.required' => 'Godown Destination is required.',
            'destination_id.exists'   => 'The selected destination is invalid.',

            'item_id.required'        => 'Item is required.',
            'item_id.exists'          => 'The selected item is invalid.',

            'account_id.required'     => 'Party is required.',
            'account_id.exists'       => 'The selected party is invalid.',

            'broker_id.exists'        => 'The selected broker is invalid.',

            'reference_number.max'    => 'Reference number cannot exceed 255 characters.',
            'vehicle_number.required' => 'Vehicle Number is required.',
            'vehicle_number.max'      => 'Vehicle number cannot exceed 255 characters.',
            'transporter_id.required' => 'Transporter is required.',
            'transporter_id.exists'   => 'The selected transporter is invalid.',
            'lr_number.required'      => 'L.R. Number is required.',
            'lr_number.max'           => 'L.R. Number cannot exceed 255 characters.',

            'date_in.required'        => 'Date In is required.',
            'date_in.date'            => 'Date In must be a valid date.',
            'date_in.date_format'     => 'Date In must be in Y-m-d format.',

            'date_out.required_if'    => 'Date Out is required for Out status.',
            'date_out.after_or_equal' => 'Date Out must be after or equal to Date In.',

            'time_in.date_format'     => 'Time In must be in H:i format.',
            'time_out.date_format'    => 'Time Out must be in H:i format.',

            'gross_weight.numeric'    => 'Gross weight must be numeric.',
            'tare_weight.numeric'     => 'Tare weight must be numeric.',
            'net_weight.numeric'      => 'Net weight must be numeric.',
            
            'bag_count.integer'       => 'Bag count must be an integer.',
            'bag_type.in'             => 'Invalid bag type selected.',

            'remarks.max'             => 'Remarks cannot exceed 1000 characters.',
        ];
    }
}

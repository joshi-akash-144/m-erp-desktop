<?php

namespace App\Http\Requests;

use App\Rules\ValidFinancialYearDate;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('godown_module.product_out');
    }   

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'godown_id' => $this->godown_id == '0' ? null : $this->godown_id,
            'godown_unit_id' => $this->godown_unit_id == '0' ? null : $this->godown_unit_id,
        ]);
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
            'uuid'               => ['required', 'uuid', 'unique:godown_modules,uuid'],
            
            // Status & Type Indicators
            'in_out_status'      => ['required', 'in:out'],
            'is_manual'          => ['required', 'boolean'],
            'is_crossing'        => ['required', 'boolean'],

            'dc_in_date'        => ['required','date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'vehicle_number'     => ['required', 'string', 'max:255'],
            // 'broker_id'          => ['nullable', 'exists:accounts,id'],
            'item_id'            => ['required', 'exists:items,id'],
            'party_id'           => ['required', 'exists:accounts,id'],
            'reference_number'   => ['nullable', 'string', 'max:255', Rule::unique('delivery_challans', 'reference_number')->where('company_id', $companyId)->where('financial_year_id', $financialYearId)->where('account_id', $this->input('party_id'))], // Party Bill No unique with party_id and company id and financial year id
            'bag_type'          => ['required', 'in:plastic,gunny'],
            'bag_count'         => ['nullable', 'numeric', 'min:0'],
            'rate'              => ['nullable', 'numeric'],
            'challan_date'      => ['nullable', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'destination_id'    => ['nullable', 'exists:destinations,id'], // If they use party destination, keep this rule for future
            'party_destination_id'    => ['required', 'exists:destinations,id'],
            'godown_id'         => ['nullable', 'exists:destinations,id'],
            'godown_unit_id'    => ['nullable', 'exists:godowns,id'],
            // 'so_no'             => ['nullable', 'exists:sales_orders,id'],
            'dairy_po'          => ['nullable', 'string', 'max:255'],
            // 'godown_unit_location_id' => ['nullable','required_if:godown_id,!=' , 'exists:godown_unit_locations,id'],
            'transporter_id'    => ['required', 'exists:transporters,id'],
            'lr_number'         => ['nullable', 'string', 'max:255'],
            'challan_bags'      => ['nullable', 'integer'],
            'challan_weight'    => ['nullable', 'numeric'],
            'date_in'           => ['required','date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'date_out'          => ['nullable','date', 'date_format:Y-m-d', new ValidFinancialYearDate, function ($attribute, $value, $fail) {
                $dateIn = $this->input('date_in');
                if ($dateIn && $value) {
                    $dateInTime = Carbon::parse($dateIn)->startOfDay();
                    $dateOutTime = Carbon::parse($value)->startOfDay();
                    if ($dateOutTime->lt($dateInTime)) {
                        $fail('Date Out must be greater than or equal to Date In.');
                    }
                }
            }],
            'gross_weight'       => ['nullable', 'numeric', 'lte:tare_weight', 'min:0'], // gross weight must be greater than or equal to tare weight and must grater than 0
            'tare_weight'       => ['nullable', 'numeric', 'gte:gross_weight', 'gt:0'], // tare weight must be less than or equal to gross weight and must grater than 0
            'net_weight_wt_bag' => ['nullable', 'numeric'],
            'moisture'          => ['nullable', 'numeric', 'min:0'],
            'old_challan_weight'=> ['nullable', 'numeric'],
            'new_challan_weight'=> ['nullable', 'numeric'],
            'old_gross_weight'  => ['nullable', 'numeric'],
            'new_gross_weight'  => ['nullable', 'numeric'],
            'old_tare_weight'   => ['nullable', 'numeric'],
            'new_tare_weight'   => ['nullable', 'numeric'],
            'old_net_weight'    => ['nullable', 'numeric'],
            'new_net_weight'    => ['nullable', 'numeric'],
            'old_net_weight_wt_bag'=> ['nullable', 'numeric'],
            'new_net_weight_wt_bag'=> ['nullable', 'numeric'],
            'time_in'           => ['nullable'],
            'time_out'          => ['nullable'],
            'remarks'           => ['nullable', 'string', 'max:255'],
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

            'delivery_challan_id.exists' => 'The selected Delivery Challan is invalid.',
            'dc_in_date.date'           => 'The selected Delivery Challan date is invalid.',
            'dc_in_date.date_format'    => 'The selected Delivery Challan date must be in Y-m-d format.',

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
            
            'bag_count.numeric'       => 'Bag count should be numeric.',
            'bag_type.in'             => 'Invalid bag type selected.',

            'remarks.max'             => 'Remarks cannot exceed 1000 characters.',
        ];
    }
}

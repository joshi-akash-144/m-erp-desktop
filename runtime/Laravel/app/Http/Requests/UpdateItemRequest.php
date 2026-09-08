<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('item.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $item = $this->route('item');

        return [
           'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'name')
                    ->where(fn($query) => $query->where('company_id', session('company_id')))
                    ->whereNull('deleted_at')
                    ->ignore($item->uuid, 'uuid'), // ignore current UUID
            ],
            'print_name' => [
                'required',
                'string',
                'max:255',
            ],
            'tax_category_id' => [
                'required',
                'exists:tax_categories,id',
            ],
            'unit_id' => [
                'required',
                'exists:units,id',
            ],
            'item_group_id' => [
                'required',
                'exists:item_groups,id',
            ],
            'opening_qty' => [
                'nullable',
                'numeric',
            ],
            'hsn_sac_code' => [
                'nullable'
            ],
            'opening_value' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $openingQty = (float) $this->input('opening_qty', 0);
                    if ($openingQty > 0 && ($value === null || $value === '')) {
                        $fail('The opening value field is required when opening qty is greater than zero.');
                    }
                },
            ],
            'is_maintain_stock_balance'  => ['required', 'boolean'],
            'purchase_type_local_id' => ['nullable', 'exists:purchase_types,id'],
            'purchase_type_interstate_id' => ['nullable', 'exists:purchase_types,id'],
            'sale_type_local_id' => ['nullable', 'exists:sale_types,id'],
            'sale_type_interstate_id' => ['nullable', 'exists:sale_types,id'],
        ];
    }
}

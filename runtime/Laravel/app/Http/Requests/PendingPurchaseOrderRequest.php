<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PendingPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // allow access
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'broker_id'  => 'nullable|integer|exists:accounts,id',
            'item_id' => 'nullable|integer|exists:items,id',
        ];
    }

    public function messages(): array
    {
        return [
            // account_id
            'account_id.required' => 'Supplier Name is required.',
            'account_id.integer'  => 'Account must be a valid number.',
            'account_id.exists'   => 'Selected supplier does not exist.',

            // broker_id
            'broker_id.integer'   => 'Broker must be a valid number.',
            'broker_id.exists'    => 'Selected broker does not exist.',

            // item_id
            'item_id.integer'  => 'Item must be a valid number.',
            'item_id.exists'   => 'Selected item does not exist.',
        ];
    }
}

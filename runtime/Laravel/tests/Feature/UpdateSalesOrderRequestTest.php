<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateSalesOrderRequest;
use App\Rules\ValidFinancialYearDate;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateSalesOrderRequestTest extends TestCase
{
    use WithFaker;

    private function basePayload(): array
    {
        return [
            'purchase_order_number' => 'PO-123',
            'purchase_order_date' => '2024-04-10',
            'delivery_date' => '2024-04-15',
            'delivery_days' => 5,
            'due_date' => '2024-04-20',
            'account_id' => 1,
            'remarks' => 'note',
            'items' => [
                [
                    'item_id' => 1,
                    'quantity' => 10,
                    'rate' => 2.5,
                    'inclusive_rate' => 2.5,
                    'amount' => 25,
                    'unit_name' => 'KG',
                    'destination_id' => null,
                    'condition_id' => null,
                ]
            ],
        ];
    }

    private function makeValidator(array $data): ValidatorContract
    {
        // Simulate financial year in session and helpers used by rule and withValidator
        session([
            'financial_year_start' => '2024-04-01',
            'financial_year_end' => '2025-03-31',
        ]);

        // Build validator using the request's rules and messages
        $request = new UpdateSalesOrderRequest();
        $rules = $request->rules();
        $messages = $request->messages();

        return Validator::make($data, $rules, $messages);
    }

    public function test_valid_payload_passes_validation(): void
    {
        $validator = $this->makeValidator($this->basePayload());
        $this->assertTrue($validator->passes());
    }

    public function test_requires_at_least_one_item(): void
    {
        $data = $this->basePayload();
        $data['items'] = [];

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
    }

    public function test_delivery_date_must_be_before_or_equal_due_date(): void
    {
        $data = $this->basePayload();
        $data['delivery_date'] = '2024-04-21'; // after due_date

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('delivery_date', $validator->errors()->toArray());
    }

    public function test_due_date_must_not_be_before_delivery_date(): void
    {
        $data = $this->basePayload();
        $data['due_date'] = '2024-04-10'; // before delivery_date

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
    }

    public function test_delivery_date_must_be_within_financial_year(): void
    {
        $data = $this->basePayload();
        $data['delivery_date'] = '2026-01-01'; // outside FY

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('delivery_date', $validator->errors()->toArray());
    }

    public function test_purchase_order_date_must_be_within_financial_year_when_present(): void
    {
        $data = $this->basePayload();
        $data['purchase_order_date'] = '2026-01-01'; // outside FY

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('purchase_order_date', $validator->errors()->toArray());
    }

    public function test_item_quantity_and_rate_must_be_positive_numbers(): void
    {
        $data = $this->basePayload();
        $data['items'][0]['quantity'] = 0;
        $data['items'][0]['rate'] = 0;
        $data['items'][0]['amount'] = 0;

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('items.0.quantity', $errors);
        $this->assertArrayHasKey('items.0.rate', $errors);
        $this->assertArrayHasKey('items.0.amount', $errors);
    }

    public function test_items_must_have_distinct_item_ids(): void
    {
        $data = $this->basePayload();
        $data['items'][] = $data['items'][0]; // duplicate item_id

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.1.item_id', $validator->errors()->toArray());
    }

    public function test_account_id_is_required_and_must_exist(): void
    {
        $data = $this->basePayload();
        unset($data['account_id']);

        $validator = $this->makeValidator($data);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('account_id', $validator->errors()->toArray());
    }
}

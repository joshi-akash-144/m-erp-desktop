<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultChequeStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds `cheque_masters` (parent) and `cheque_properties` (child).
     * Uses insertGetId() to avoid hardcoded IDs — properties are linked
     * dynamically to each master's auto-generated primary key.
     * Safe to re-run: truncates both tables before re-seeding.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Truncate child first, then parent, to avoid FK issues on re-seed
        DB::table('cheque_properties')->truncate();
        DB::table('cheque_masters')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->seedAll();
    }

    /**
     * Define all cheque masters with their properties, then insert sequentially.
     * Each master's DB-assigned ID is used to link its properties.
     */
    private function seedAll(): void
    {
        $masters = [
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1000',
                    'formate_name'  => 'Default',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => true,
                    'status'        => true,
                    'company_id'    => 0,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.30', 'left' => '4.17', 'width' => '1.44', 'height' => '0.29', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.22', 'left' => '6.26', 'width' => '1.44', 'height' => '0.29', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.65', 'left' => '1.24', 'width' => '6.53', 'height' => '0.29', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.00', 'left' => '1.34', 'width' => '4.51', 'height' => '0.58', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.25', 'left' => '6.37', 'width' => '1.44', 'height' => '0.29', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1002',
                    'formate_name'  => 'ICICI Bank',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 8,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.30', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.68', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.20', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1003',
                    'formate_name'  => 'AXIS Bank - 7634',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 1,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.32', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.68', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1004',
                    'formate_name'  => 'BOB Bank - 15517',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 1,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.32', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.68', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1005',
                    'formate_name'  => 'Axis Bank - 7694',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 2,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.32', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.70', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.03', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1006',
                    'formate_name'  => 'Axis Bank - 9398',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 3,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.30', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.70', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1007',
                    'formate_name'  => 'Axis bank - 7702',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 4,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.32', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.70', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
            [
                'master' => [
                    'uuid'          => Str::uuid()->toString(),
                    'code'          => '1008',
                    'formate_name'  => 'Axis Bank - 0161',
                    'top_margin'    => '0',
                    'left_margin'   => '0',
                    'cheque_height' => '300',
                    'cheque_width'  => '1000',
                    'is_default'    => false,
                    'status'        => true,
                    'company_id'    => 7,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                'properties' => [
                    ['column_value' => 'ac_payee',        'top' => '0.31', 'left' => '4.34', 'width' => '1.5', 'height' => '0.3', 'font_size' => '14'],
                    ['column_value' => 'date',            'top' => '0.20', 'left' => '6.32', 'width' => '1.5', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'account_name',    'top' => '0.68', 'left' => '1.29', 'width' => '6.8', 'height' => '0.3', 'font_size' => '16'],
                    ['column_value' => 'amount_in_words', 'top' => '1.04', 'left' => '1.4',  'width' => '4.7', 'height' => '0.6', 'font_size' => '16'],
                    ['column_value' => 'amount',          'top' => '1.28', 'left' => '6.28', 'width' => '1.5', 'height' => '0.3', 'font_size' => '18'],
                ],
            ],
        ];

        // Common property defaults
        $propertyDefaults = [
            'align_text' => 'left',
            'font_name'  => 'Times New Roman',
            'font_style' => 'bold',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ($masters as $entry) {
            // Insert master and capture the auto-generated ID
            $masterId = DB::table('cheque_masters')->insertGetId($entry['master']);

            // Build and insert properties linked to this master's ID
            $properties = array_map(fn($prop) => array_merge($prop, $propertyDefaults, [
                'cheque_master_id' => $masterId,
            ]), $entry['properties']);

            DB::table('cheque_properties')->insert($properties);
        }
    }
}

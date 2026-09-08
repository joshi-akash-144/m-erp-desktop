<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('default_bill_sundries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('bill_sundry_type', ['additive', 'subtractive']);
            $table->enum('calculation_type', ['percentage', 'fixed']);
            $table->boolean('bill_sundry_amount_round_off')->default(false);
            $table->enum('bill_sundry_nature', ['tds', 'gst', 'other']);
            $table->enum('apply_on', ['basic', 'running_total', 'previous_row', 'grand_total']);
            // $table->boolean('affect_grand_total')->default(false);
            $table->decimal('default_value', 15, 3)->default(0);

            $table->string('code', 20);

            $table->enum('posting_mode', [
                'adjust_amount',   // affects totals only (no separate ledger)
                'ledger_only'      // pure accounting entry (CD, penalty, rebate)
            ])->default('adjust_amount')
                ->comment('Controls whether sundry adjusts totals or posts ledger entry');
            
            // PURCHASE SETTINGS
            $table->boolean('purchase_adjust_in_amount');

            $table->enum('purchase_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable();

            $table->integer('purchase_account_code')->nullable();

            $table->boolean('purchase_adjust_in_party_amount');

            $table->enum('purchase_party_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable();

            $table->integer('purchase_party_account_code')->nullable();

            $table->boolean('purchase_post_over_and_above')
                ->default(false);

            // SALE SETTINGS
            $table->boolean('sale_adjust_in_amount');

            $table->enum('sale_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable();

            $table->integer('sale_account_code')->nullable();

            $table->boolean('sale_adjust_in_party_amount');

            $table->enum('sale_party_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable();

            $table->integer('sale_party_account_code')->nullable();

            $table->boolean('sale_post_over_and_above')
                ->default(false);;
            $table->timestamps();

            $table->boolean('is_read_only')->default(false);

            $table->boolean('preload_in_sales')->default(false);
            $table->integer('sales_preload_order')->default(0);
            $table->boolean('preload_in_purchases')->default(false);
            $table->integer('purchases_preload_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_bill_sundries');
    }
};

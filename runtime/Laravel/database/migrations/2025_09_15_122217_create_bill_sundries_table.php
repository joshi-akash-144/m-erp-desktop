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

        Schema::create('bill_sundries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
        
            
            $table->string('name');
            $table->string('print_name');

            $table->string('code', 20);

            $table->enum('bill_sundry_type', ['additive', 'subtractive'])
                ->comment('Whether bill sundry adds or subtracts amount');

            $table->enum('calculation_type', ['percentage', 'fixed'])
                ->default('fixed')
                ->comment('Calculation method: percentage or fixed');

            $table->enum('apply_on', ['basic', 'running_total', 'previous_row', 'grand_total'])
                ->default('basic')
                ->comment('Where the calculation should be applied');

            $table->boolean('affect_cost_of_goods_in_purchase')
            ->default(false)
            ->comment('True = affect, False = do not affect');

            $table->boolean('affect_cost_of_goods_in_sales')
            ->default(false)
            ->comment('True = affect, False = do not affect');

            // $table->boolean('affect_grand_total')
            //     ->default(false)
            //     ->comment('True = affect grand total, False = do not affect');

            $table->decimal('default_value', 15, 3)->default(0);

            $table->enum('bill_sundry_nature', ['tds', 'gst', 'other'])
                ->comment('Type of bill sundry');

            $table->boolean('bill_sundry_amount_round_off')
                ->default(false)
                ->comment('True = round off bill sundry amount, False = no round off');

            // PURCHASE SETTINGS
            $table->boolean('purchase_adjust_in_amount')
                ->comment('True = adjust in purchase amount, False = no');

            $table->enum('purchase_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable()
                ->comment('Purchase account selection type');

            $table->foreignId('purchase_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->boolean('purchase_adjust_in_party_amount')
                ->comment('True = adjust in purchase party amount, False = no');

            $table->enum('purchase_party_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable()
                ->comment('Purchase party account selection type');

            $table->foreignId('purchase_party_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->boolean('purchase_post_over_and_above')
                ->default(false)
                ->comment('True = post over & above purchase, False = no');

            // SALE SETTINGS
            $table->boolean('sale_adjust_in_amount')
                ->comment('True = adjust in sale amount, False = no');

            $table->enum('sale_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable()
                ->comment('Sale account selection type');

            $table->foreignId('sale_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->boolean('sale_adjust_in_party_amount')
                ->comment('True = adjust in sale party amount, False = no');

            $table->enum('sale_party_account_type', ['specify_account', 'specify_account_in_voucher'])
                ->nullable()
                ->comment('Sale party account selection type');

            $table->foreignId('sale_party_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->boolean('sale_post_over_and_above')
                ->default(false)
                ->comment('True = post over & above sale, False = no');

            $table->boolean('preload_in_sales')->default(false);
            $table->integer('sales_preload_order')->default(0);
            $table->boolean('preload_in_purchases')->default(false);
            $table->integer('purchases_preload_order')->default(0);

            

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_system')->default(false);
            
            $table->boolean('is_read_only')->default(false);

            $table->boolean('status')
                ->default(true)
                ->comment('True = active, False = inactive');

            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'name', 'is_active'], 'unique_bill_sundry');
            $table->unique(['company_id', 'code', 'is_active'], 'unique_bill_sundry_code');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_sundries');
    }
};

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
        Schema::create('sales_invoice_sundries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sundry_id')->nullable()->constrained('bill_sundries')->nullOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->enum('bill_sundry_type', ['additive', 'subtractive']);

            // Calculation type
            $table->enum('calculation_type', ['percentage', 'fixed'])->default('fixed');
            $table->enum('apply_on', ['basic', 'running_total', 'previous_row', 'grand_total'])
                ->default('basic')
                ->comment('Where the calculation should be applied');
            $table->foreignId('bill_sundry_modal_dr_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('bill_sundry_modal_cr_id')->nullable()->constrained('accounts')->nullOnDelete();
            // Basis amount (like taxable amount / subtotal)
            $table->decimal('base_amount', 15, 2)->default(0);
            // Percentage (if used)
            $table->decimal('rate_percent', 10, 2)->default(0);
            // Flat value / calculated amount
            $table->decimal('value', 15, 2)->default(0);
            // Final amount applied (+ or -)
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->string('remarks')->nullable();

            $table->boolean('affect_net_total')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_sundries');
    }
};

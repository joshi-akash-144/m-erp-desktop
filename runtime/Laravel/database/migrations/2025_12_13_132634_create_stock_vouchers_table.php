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
        Schema::create('stock_vouchers', function (Blueprint $table) {
             $table->id();

            // MAIN LINK
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained()->onDelete('cascade');

            $table->string('voucher_number');
            $table->string('voucher_serial');
            $table->string('reference_number')->nullable();
            $table->date('voucher_date');

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index(['voucher_date']);
            $table->index(['voucher_type_id']);
            $table->index(['voucher_number']);


            $table->unique(
                ['company_id', 'financial_year_id', 'voucher_type_id', 'voucher_number'],
                'uniq_stock_voucher_no'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_vouchers');
    }
};

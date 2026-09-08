<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_entries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');

            $table->unsignedBigInteger('voucher_id');
            $table->unsignedBigInteger('voucher_transaction_id')->nullable();

            // Party Ledger
            $table->unsignedBigInteger('account_id');

            // TDS Section Master
            $table->unsignedBigInteger('tds_category_id')->nullable();

            // Report Fields
            $table->string('reference_no')->nullable();

            $table->string('deductee_name', 255);
            $table->string('pan_no', 20)->nullable();

            $table->decimal('payment_amount', 18, 2)->default(0);

            $table->date('payment_date')->nullable();

            $table->decimal('tds_rate', 8, 4)->default(0);

            $table->decimal('tds_amount', 18, 2)->default(0);

            $table->decimal('total_deducted', 18, 2)->default(0);

            $table->date('tax_deducted_on')->nullable();

            // Additional TDS Information
            $table->string('section_code', 20)->nullable();

            $table->unsignedBigInteger('voucher_type_id');

            $table->boolean('is_lower_deduction')->default(false);

            $table->string('certificate_no')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_entries');
    }
};
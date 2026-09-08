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
        Schema::create('debit_note_vouchers', function (Blueprint $table) {
            $table->id();

            // Voucher link
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
        
            // Company context
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->enum('gst_nature', ['gst_applicable','gst_not_applicable','gst_tax_adjustment','register_exp_b2b'])->default('gst_not_applicable');
            $table->enum('entry_from', ['voucher'])->default('voucher');
            $table->date('bill_date')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_note_vouchers');
    }
};

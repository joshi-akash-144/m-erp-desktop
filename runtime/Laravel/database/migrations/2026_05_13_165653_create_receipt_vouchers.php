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

        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();

            // Voucher link
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
        
            // Company context
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
        
            // Allocation amount (VERY IMPORTANT)
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->enum('mode', ['cheque', 'cash', 'online', 'rtgs','neft', 'imps','other'])
                  ->default('cheque');
            // Bank used for voucher entry
            // $table->foreignId('bank_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->enum('entry_from', ['receipt_voucher', 'receipt_receivable'])->default('receipt_receivable');

            // Account details 
            // $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->boolean('is_received')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_hold')->default(false);
        
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_vouchers');
    }
};

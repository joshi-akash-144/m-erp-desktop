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

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
        
            $table->date('payment_date')->nullable();
        
            // Payment mode
            $table->enum('mode', ['cheque', 'cash', 'online', 'rtgs','neft', 'imps','other'])
                  ->default('cheque');
            
            $table->enum('ac_pay', ['Y', 'N'])->default('N'); // A/c Payee details
            $table->enum('rtgs', ['Y', 'N'])->default('N'); // RTGS details
        
            // Main amount (universal)
            $table->decimal('amount', 15, 2)->default(0);
        
            // Bank used
            $table->foreignId('bank_id')->nullable()->constrained('accounts')->nullOnDelete();
        
            // Cheque details
            $table->string('cheque_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_time')->nullable();
        
            // Online details
            $table->string('utr_number')->nullable();
        
            // Status
            $table->enum('cheque_status', ['pending', 'approved', 'processed', 'cleared', 'bounced','cancelled'])
                  ->default('pending');
            
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();

            // Voucher link
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
        
            // Payment (one payment can have multiple vouchers)
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
        
            // Company context
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
        
            // Allocation amount (VERY IMPORTANT)
            $table->decimal('paid_amount', 15, 2)->default(0);
        
            // Bank used for voucher entry
            $table->foreignId('bank_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->enum('entry_from', ['payment_voucher', 'payment_payable'])->default('payment_payable');

            // Account details 
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->boolean('is_paid')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_hold')->default(false);
            $table->boolean('is_pass_to_rtgs')->default(false);

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
        
            // Optional metadata
            $table->string('file_number')->nullable();

            $table->boolean('is_transport_payment')->default(false);
            $table->unsignedBigInteger('transport_payment_release_id')->nullable();
        
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
        Schema::dropIfExists('payments');
    }
};

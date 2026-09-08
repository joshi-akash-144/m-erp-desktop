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
        Schema::create('account_bank_details', function (Blueprint $table) {
            $table->id();

            $table->string('bank_beneficiary_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->boolean('is_default_bank')->default(false);
            $table->integer('rtgs_form_view_id')->nullable();
            $table->integer('cheque_master_id')->nullable();
            $table->enum('bank_account_type', [
                'saving',        // Savings Account
                'current',       // Current Account
                'od',            // Overdraft Account
                'fd',            // Fixed Deposit Account
                'rd',            // Recurring Deposit Account
                'cc',            // Cash Credit Account
                'nro',           // Non-Resident Ordinary
                'nre',           // Non-Resident External
                'fcnr',          // Foreign Currency Non-Resident
                'ppf',           // Public Provident Fund
                'escrow',        // Escrow Account
                'loan',          // Loan Account
                'salary',        // Salary Account
                'demat',         // Demat Account
            ])->nullable();
            
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_bank_details');
    }
};

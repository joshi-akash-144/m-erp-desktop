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
        Schema::create('reference_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');

            $table->unsignedBigInteger('voucher_id');
            // IMPORTANT — attach original document
            $table->enum('source_type', [
                'purchase_invoice',
                'sales_invoice',
                'payment',
                'receipt',
                'journal',
                'debit_note',
                'credit_note',
                'sales_return',
                'purchase_return',
                'freight_invoice'
            ])->nullable();
            $table->unsignedBigInteger('source_id')->nullable(); // ID of purchase invoice or voucher
            // $table->unsignedBigInteger('voucher_transaction_id');

            // make it FK
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->unsignedBigInteger('reference_id'); 
            $table->foreign('reference_id')->references('id')->on('references')->onDelete('cascade');

            $table->string('reference_number')->nullable();

            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('allocation_type', [
                'new_ref',
                'against_ref',
                'advance',
                'on_account'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_allocations');
    }
};

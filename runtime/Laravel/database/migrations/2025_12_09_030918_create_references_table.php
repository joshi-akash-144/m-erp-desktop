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
        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('reference_number');           // PI-00034 / ADV-001
            $table->date('reference_date')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('file_number')->nullable();
            
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
            // $table->foreignId('parent_voucher_id')->constrained('vouchers')->onDelete('cascade');
            

            $table->enum('reference_type', ['new_ref', 'advance'])->default('new_ref');
            $table->enum('direction', ['debit', 'credit']);

            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('settled_amount', 15, 2)->default(0);
            $table->decimal('pending_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('purchase_order_number')->nullable();

            $table->boolean('is_hold')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->date('closed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('status')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};


// source_type	direction
// purchase_invoice	credit
// sales_invoice	debit
// payment	debit
// receipt	credit
// debit_note	debit
// credit_note	credit
// journal (DR line)	debit
// journal (CR line)	credit
// rebate (negative)	debit
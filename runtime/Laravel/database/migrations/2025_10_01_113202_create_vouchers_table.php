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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('voucher_type_id')->constrained()->onDelete('cascade');

            $table->bigInteger('voucher_serial'); // numeric, auto-increment logic
            $table->string('voucher_number'); // formatted like INV-2025-0001

            $table->date('voucher_date');
            $table->string('reference_number')->nullable(); // like Party bill number
            // $table->decimal('total_amount', 15, 2)->nullable();

            // WHERE FROM: Traceability to source document (optional)
            $table->string('source_type')->nullable();
            // Values: 'purchase_invoice', 'sales_invoice', 'expense_bill', 'salary_sheet', etc.

            $table->unsignedBigInteger('source_id')->nullable();
            
            $table->text('narration')->nullable();
            $table->string('secondary_narration')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->boolean('is_opening')->default(false);
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();

            // $table->unique(
            //     ['company_id', 'voucher_number','is_active'], 
            //     'uniq_company_voucher_number',''voucher_type_id',,
            // );
            
            // $table->unique(
            //     ['company_id', 'voucher_type_id', 'voucher_serial', 'is_active'], 
            //     'uniq_company_voucher_type_serial'
            // );
            // $table->unique(
            //     ['company_id', 'voucher_number','voucher_type_id','financial_year_id','is_active'], 
            //     'uniq_company_voucher_number',
            // );
            
            $table->unique(
                ['company_id', 'voucher_type_id', 'financial_year_id','voucher_serial', 'is_active'], 
                'uniq_company_voucher_type_serial'
            );
            
            $table->index('company_id');
            $table->index('financial_year_id');
            $table->index('voucher_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');

            $table->string('credit_note_serial')->nullable();
            $table->string('credit_note_number')->nullable();
            $table->date('credit_note_date');

            // Reference to original sales invoice (optional)
            $table->unsignedBigInteger('sales_invoice_id')->nullable();
            $table->string('sales_invoice_serial')->nullable();

            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('sale_type_id')->nullable();
            $table->string('gst_type')->nullable();   // local / interstate
            $table->string('reference_number')->nullable();

            // Totals
            $table->decimal('total_quantity',  15, 3)->default(0);
            $table->decimal('taxable_amount',  15, 2)->default(0);
            $table->decimal('tax_amount',      15, 2)->default(0);
            $table->decimal('net_amount',      15, 2)->default(0);
            $table->decimal('grand_total',     15, 2)->default(0);

            // Linked voucher
            $table->unsignedBigInteger('voucher_id')->nullable();

            $table->string('remarks')->nullable();
            $table->string('status')->default('open');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};

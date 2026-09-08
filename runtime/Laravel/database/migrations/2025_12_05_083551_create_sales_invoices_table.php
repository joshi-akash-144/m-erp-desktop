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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');

            $table->bigInteger('invoice_serial');
            $table->string('invoice_number');
            $table->date('invoice_date');

            $table->string('delivery_challan_number')->nullable();

            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('sales_order_serial')->nullable();
            $table->string('reference_number')->nullable();

            $table->string('grn_number')->nullable();

            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->date('last_invoice_date')->nullable()->default(null);
            $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->integer('kms')->default(0);
            $table->string('vehicle_number')->nullable();
            // party_bill_date
            $table->date('party_bill_date')->nullable()->default(null);
            $table->foreignId('sale_type_id')->constrained();

            $table->date('delivery_date')->nullable()->default(null);

            $table->decimal('total_quantity', 15, 4)->default(0);

            $table->decimal('net_amount', 15, 2)->default(0);     // after bill sundry
            $table->decimal('total_amount', 15, 2)->default(0);   // taxable + tax
            $table->decimal('received_amount', 15, 2)->default(0);

            $table->decimal('taxable_amount', 15, 2)->default(0); // qty * rate
            $table->decimal('tax_amount', 15, 2)->default(0);     // GST total

            $table->decimal('grand_total', 15, 2)->default(0);

            $table->bigInteger('ewaybill_number')->nullable(); // Changed to string as EWB numbers can be alphanumeric
            $table->enum('irn_status', ['pending', 'generated', 'failed', 'cancelled'])->default('pending');
            $table->enum('ewb_status', ['pending', 'generated', 'failed', 'cancelled'])->default('pending');
            
            $table->foreignId('voucher_id')->nullable()->constrained();

            $table->text('remarks')->nullable();

            $table->enum('payment_received_status', ['unpaid', 'partially_paid', 'fully_paid', 'overpaid'])->default('unpaid');

            // GST info
            $table->enum('gst_type', ['local', 'interstate'])->nullable();
            // Other
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year_id', 'invoice_number', 'deleted_at'], 'unique_invoice_number');
            $table->index(['company_id', 'financial_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};

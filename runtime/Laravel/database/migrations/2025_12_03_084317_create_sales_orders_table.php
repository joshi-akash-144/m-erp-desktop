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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');
            $table->bigInteger('order_serial');

            $table->string('order_number');

            $table->string('purchase_order_number')->nullable();
            $table->date('purchase_order_date')->nullable()->default(null);

            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->date('delivery_date')->nullable()->default(null);
            $table->integer('delivery_days')->nullable();
            $table->date('due_date')->nullable();
            // Totals
            $table->decimal('total_quantity', 15, 4)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('order_status', ['open', 'close', 'cancel', 'hold'])->default('open');
            $table->text('remarks')->nullable();
            // GST info
            $table->enum('gst_type', ['local', 'interstate'])->nullable();
            $table->boolean('is_skip_serial_generation')->default(false);
            // Other
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year_id', 'order_serial', 'deleted_at'], 'unique_order_serial');
            $table->unique(['company_id', 'financial_year_id', 'order_number', 'deleted_at'], 'unique_order_number');
            $table->index(['company_id', 'financial_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};

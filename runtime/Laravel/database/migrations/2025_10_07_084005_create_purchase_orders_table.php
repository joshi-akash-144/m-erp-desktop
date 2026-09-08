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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');

            $table->bigInteger('order_serial');
            $table->string('order_number');

            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete(); // supplier
            // $table->string('account_name')->nullable(); // supplier
            // $table->string('account_city')->nullable(); // supplier city
            $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();
            // $table->string('broker_name')->nullable(); // broker
            // $table->string('broker_city')->nullable(); // broker city
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->nullOnDelete();
            // $table->string('destination_name')->nullable(); // broker
            $table->string('contract_number')->nullable();
            $table->integer('delivery_days')->nullable();
            $table->date('order_date')->nullable()->default(null);
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

            $table->boolean('is_skip_serial_generation')->default(false);

            // GST info
            $table->enum('gst_type', ['local', 'interstate'])->nullable();

            // Other
            $table->boolean('status')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year_id', 'order_serial','deleted_at'], 'unique_order_serial');
            $table->unique(['company_id', 'financial_year_id','order_number', 'deleted_at'], 'unique_order_number');
            $table->index(['company_id', 'financial_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};

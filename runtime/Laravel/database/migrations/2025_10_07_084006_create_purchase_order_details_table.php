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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            
            // Item information
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            // $table->string('item_name')->nullable();
            // $table->string('unit_id')->nullable();
            // $table->string('unit_name')->nullable();
            // $table->string('hsn_sac_code', 20)->nullable();

            $table->foreignId('condition_id')->nullable()->constrained()->onDelete('cascade');
            // $table->string('condition_name')->nullable();

            // Quantity
            $table->decimal('ordered_qty', 15, 4)->default(0);
            $table->decimal('received_qty', 15, 4)->default(0);
            // $table->decimal('remaining_qty', 15, 4)->default(0);

            // Pricing
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('inclusive_rate', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);

            // Taxable & GST rates
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);

            // Totals
            $table->decimal('amount', 15, 2)->default(0); // before tax
            $table->decimal('net_amount', 15, 2)->default(0); // after tax

            $table->boolean('is_closed')->default(false);

            $table->string('remarks')->nullable();

            // $table->softDeletes();
            $table->timestamps();
            $table->unique(['purchase_order_id', 'item_id'], 'unique_order_item');
            $table->index('purchase_order_id');
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};

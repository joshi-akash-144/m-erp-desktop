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
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->onDelete('cascade');

            // Item
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
        
            // Unit / Condition / Destination
            // $table->string('unit_name')->nullable();
            $table->foreignId('condition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
        
            // Purchase Order relations
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
        
            // PO item serial no (NOT foreign key)
            $table->integer('purchase_order_serial')->nullable();
        
            // Quantity
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('party_quantity', 15, 4)->default(0);

            $table->integer('bag_count')->default(0);
            
            // Pricing
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('inclusive_rate', 15, 2)->default(0);
        
            // Taxable & GST
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
        
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
        
            // Totals
            $table->decimal('amount', 15, 2)->default(0);      // before tax
            $table->decimal('net_amount', 15, 2)->default(0);  // after tax

            $table->decimal('grand_total', 15, 2)->default(0);
        
            $table->string('remarks')->nullable();
        
            $table->timestamps();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_details');
    }
};

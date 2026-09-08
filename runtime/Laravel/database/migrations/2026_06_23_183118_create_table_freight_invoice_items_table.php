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
        Schema::create('freight_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_id')->constrained('freights')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->bigInteger('quantity')->nullable();
            $table->date('import_date')->nullable();
            $table->date('billing_date')->nullable();
            $table->string('customer_po_no')->nullable();
            $table->string('sold_to_party')->nullable();
            $table->bigInteger('to')->nullable();
            $table->bigInteger('from')->nullable();
            $table->bigInteger('vehicle_id')->nullable();
            $table->bigInteger('zone_id')->nullable();
            $table->string('lr_number')->nullable();
            $table->string('dc_number')->nullable();
            $table->decimal('rate')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_invoice_items');
    }
};

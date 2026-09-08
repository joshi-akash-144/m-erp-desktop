<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debit_note_id');

            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity',      15, 3)->default(0);
            $table->decimal('rate',          15, 2)->default(0);
            $table->decimal('inclusive_rate',15, 2)->default(0);
            $table->decimal('amount',        15, 2)->default(0);
            $table->decimal('net_amount',    15, 2)->default(0);
            $table->decimal('tax_amount',    15, 2)->default(0);
            $table->decimal('taxable_amount',15, 2)->default(0);

            $table->decimal('cgst_rate',   5, 2)->default(0);
            $table->decimal('sgst_rate',   5, 2)->default(0);
            $table->decimal('igst_rate',   5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);

            $table->unsignedBigInteger('condition_id')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->decimal('bag_count', 10, 2)->default(0);

            $table->timestamps();

            $table->foreign('debit_note_id')->references('id')->on('debit_notes')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_items');
    }
};

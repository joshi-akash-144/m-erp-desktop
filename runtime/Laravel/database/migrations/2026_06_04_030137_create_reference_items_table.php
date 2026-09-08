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
        Schema::create('reference_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id')->constrained('references')->onDelete('cascade');
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('rebate', 15, 2)->default(0);
            $table->decimal('cd_percentage', 15, 2)->default(0);
            $table->decimal('cd_amount', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('tds', 15, 2)->default(0);
            $table->decimal('freight', 15, 2)->default(0);
            $table->decimal('gst', 15, 2)->default(0);
            $table->decimal('penalty', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_items');
    }
};

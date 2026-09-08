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
        Schema::create('freight_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('freight_id')->constrained('freights')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->cascadeOnDelete();

            $table->enum('bag_type', ['gunny', 'plastic'])->default('gunny');
            $table->bigInteger('bag_count')->default(0);

            $table->decimal('net_weight', 15, 4)->nullable();
            $table->decimal('kms', 10, 4)->nullable();
            $table->decimal('rate', 15, 2)->nullable();

            $table->decimal('quantity', 15, 4)->nullable();
            $table->decimal('amount', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_items');
    }
};

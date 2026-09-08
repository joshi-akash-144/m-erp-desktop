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
        Schema::create('bag_challan_labour_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bag_challan_labour_id')->constrained('bag_challan_labours')->onDelete('cascade');

            $table->foreignId('grn_id')->constrained('grns')->onDelete('cascade');

            $table->decimal('bags')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_challan_labour_items');
    }
};

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
        Schema::create('dairy_analysis_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dairy_analysis_id')->constrained('dairy_analyses')->onDelete('cascade');
            $table->foreignId('element_id')->constrained('elements')->onDelete('cascade');
            
            $table->decimal('guarantee', 8, 2)->default(0);
            $table->decimal('actual', 8, 2)->default(0);
            $table->decimal('diff', 8, 2)->default(0);
            
            $table->decimal('sales_rebate', 15, 2)->default(0);
            $table->decimal('purchase_rebate', 15, 2)->default(0);
            $table->decimal('sales_premium', 15, 2)->default(0);
            $table->decimal('purchase_premium', 15, 2)->default(0);

            $table->decimal('rebate_percentage', 10, 4)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dairy_analysis_items');
    }
};

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
        Schema::create('godown_analysis_items', function (Blueprint $table) {
            $table->id();

            //ForeignKey
            $table->foreignId('godown_analysis_id')->constrained('godown_analyses')->cascadeOnDelete();
            $table->foreignId('element_id')->constrained('elements')->cascadeOnDelete();

            //Amount
            $table->decimal('guarantee', 8, 2)->default(0);
            $table->decimal('actual', 8, 4)->default(0);
            $table->decimal('difference', 8, 4)->default(0);

            $table->decimal('rebate', 15, 4)->default(0);
            $table->decimal('rebate_percentage', 4, 2)->default(0);
            $table->decimal('premium', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('godown_analysis_items');
    }
};

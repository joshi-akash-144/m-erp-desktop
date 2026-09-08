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
        Schema::create('tds_category_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tds_category_id')->constrained('tds_categories')->onDelete('cascade');
            $table->foreignId('payee_category_id')->constrained('payee_categories')->onDelete('cascade');
            $table->decimal('threshold_limit', 15, 2)->default(0);
            $table->decimal('tds_with_pan', 5, 2)->default(0);
            $table->decimal('tds_without_pan', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tds_category_details');
    }
};

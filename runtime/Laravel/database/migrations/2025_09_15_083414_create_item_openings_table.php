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
        Schema::create('item_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('cascade');
            
        
            $table->decimal('opening_qty', 15, 4)->default(0);
            $table->decimal('opening_rate', 15, 2)->default(0);
            $table->decimal('opening_value', 15, 2)->default(0);
        
            // $table->decimal('current_qty', 15, 3)->default(0);
            // $table->decimal('current_value', 15, 2)->default(0);
        
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id','item_id','financial_year_id','deleted_at'], 'unique_item_year_balance');
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_openings');
    }
};

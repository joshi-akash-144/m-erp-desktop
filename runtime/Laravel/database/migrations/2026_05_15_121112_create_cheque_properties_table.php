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
        Schema::create('cheque_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_master_id')->nullable()->constrained('cheque_masters')->cascadeOnDelete();

            $table->enum('column_value',['ac_payee','date','account_name','amount_in_words','amount']);
            $table->string('top');
            $table->string('left');
            $table->string('width');
            $table->string('height');
            $table->string('align_text');
            $table->string('font_name');
            $table->string('font_style');
            $table->string('font_size');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheque_properties');
    }
};
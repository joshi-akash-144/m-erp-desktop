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
        // change this table name spelling this file and database table
        Schema::create('default_tds_categories', function (Blueprint $table) {
            $table->id();
            $table->string('section')->unique();            
            $table->string('category_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->enum('type', ['TDS', 'TCS', 'HIGHER'])->default('TDS');
            $table->string('applicable_to')->nullable();
            $table->bigInteger('code');
            $table->boolean('status')->default(true); 
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');           
            $table->string('created_by', 1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_tds_categories');
    }
};

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
        Schema::create('default_sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('region');
            $table->string('taxation_type');
            $table->string('transaction_type');
            $table->decimal('cgst',10,2)->default(0);
            $table->decimal('sgst',10,2)->default(0);
            $table->decimal('igst',10,2)->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_sale_types');
    }
};

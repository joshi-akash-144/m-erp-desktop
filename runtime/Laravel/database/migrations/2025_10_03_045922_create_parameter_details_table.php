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
        Schema::create('dairy_parameter_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('dairy_parameters')->onDelete('cascade');
            $table->decimal('from',8,2)->default(0.00);
            $table->decimal('to',8,2)->default(0.00);
            $table->decimal('difference',8,2)->default(0.00);
            $table->decimal('rebate',8,2)->default(0.00);
            $table->decimal('premium',8,2)->default(0.00);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dairy_parameter_details');
    }
};

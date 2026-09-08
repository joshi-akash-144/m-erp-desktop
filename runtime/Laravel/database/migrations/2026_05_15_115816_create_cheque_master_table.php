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
        Schema::create('cheque_masters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
        
            $table->string('code')->unique(); 
            $table->string('formate_name');
            $table->string('top_margin');
            $table->string('left_margin');
            $table->string('cheque_height');
            $table->string('cheque_width');

            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
           
            $table->bigInteger('company_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheque_masters');
    }
};

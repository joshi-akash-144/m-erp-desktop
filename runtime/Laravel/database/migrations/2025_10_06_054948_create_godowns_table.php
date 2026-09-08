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
        Schema::create('godowns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->onDelete('cascade');
            
            $table->string('godown_name', 255);
            $table->string('remark', 255)->nullable();

            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();

            
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->unique(['company_id', 'godown_name', 'is_active'], 'unique_godown_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('godowns');
    }
};

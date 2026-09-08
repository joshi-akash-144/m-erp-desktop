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
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
        
            $table->string('name');
            $table->string('code');
            $table->enum('type', ['asset', 'liability', 'income', 'expense']);
            $table->string('f_v')->comment('Field Validation');
        
            $table->boolean('is_system')->default(false);
            
            $table->boolean('is_party_group')->default(false);
        
            // Foreign keys using modern method
            $table->foreignId('parent_id')->nullable()->constrained('account_groups')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();
        
            // Unique & index
            $table->unique(['company_id', 'code','is_active']);
            $table->unique(['company_id', 'name','is_active']);
            $table->index(['company_id', 'parent_id']);
        });
        
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_groups');
    }
};

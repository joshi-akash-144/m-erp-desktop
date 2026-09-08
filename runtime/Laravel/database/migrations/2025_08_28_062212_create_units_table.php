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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
        
            $table->string('print_name');
            $table->string('uqc')->nullable()->comment('Indicates GST e-Return Code');
            $table->string('code', 10)->nullable();
            $table->boolean('is_system')->default(false)->comment('Indicates if the unit is a system default and not editable');
            
            
            // Foreign keys using modern method (auto creates column)
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable ()->constrained('users')->nullOnDelete();
            
            $table->boolean('status')->default(true)->comment('Indicates if the unit is active or inactive');
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();
        
            // Unique constraints
            $table->unique(['company_id', 'name','is_active']);
            $table->unique(['company_id', 'code','is_active']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};

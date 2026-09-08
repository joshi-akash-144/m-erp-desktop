<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            $table->string('name');
            $table->enum('type', ['services', 'goods'])->default('goods');
            $table->enum('zero_tax_type', ['exempt', 'zero_rated', 'non_gst', 'nil_rated'])->nullable();
            $table->unsignedBigInteger('code');
        
            $table->decimal('cgst', 5, 2)->default(0);
            $table->decimal('sgst', 5, 2)->default(0);
            $table->decimal('igst', 5, 2)->default(0);

            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            
            $table->boolean('is_system')->default(false)->comment('Indicates if the tax category is a system default');
            
            $table->boolean('status')->default(true)->comment('Indicates if the tax category is active or inactive');
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();
        
            $table->unique(['company_id', 'name','is_active']);
            $table->unique(['company_id', 'code','is_active']);
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_categories');
    }
};

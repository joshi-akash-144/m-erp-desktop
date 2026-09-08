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
        Schema::create('payee_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();            
            $table->string('payee_category');
            $table->integer('code')->nullable();

            // Core Info
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();

            // Unique constraints
            $table->unique(['company_id', 'payee_category', 'is_active'], 'uniq_company_payee_category');            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payee_categories');
    }
};

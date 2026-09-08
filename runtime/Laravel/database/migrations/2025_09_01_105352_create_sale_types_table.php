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
        Schema::create('sale_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->string('name');
            // Foreign keys
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->enum('taxation_type',['taxable','exempt','nil_rated','zero_rated','non_gst']);
            $table->enum('transaction_type',['domestic', 'export']);
            $table->enum('region',['local','interstate']);
            $table->decimal('cgst',10,2)->default(0);
            $table->decimal('sgst',10,2)->default(0);
            $table->decimal('igst',10,2)->default(0);
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_system')->default(false);
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();


            $table->unique(['company_id', 'name','is_active']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_types');
    }
};

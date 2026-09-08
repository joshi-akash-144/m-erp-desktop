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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('code');
            $table->string('name');
            $table->string('print_name')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();  
            $table->text('address_one')->nullable();
            $table->text('address_two')->nullable();
            $table->string('mobile_number', 25)->nullable();

            $table->enum('gst_type', ['local','interstate'])->comment('Indicates if the GST is local or interstate');
            
            $table->string('whatsapp_number', 25)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_billwise')->default(false)->comment('true = billwise, false = not billwise');
            $table->enum('party_type', ['account','supplier','customer','broker'])->default('account');
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('cascade');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade');

            // Basic Flags
            $table->boolean('is_system')->default(false);
            $table->boolean('is_hidden')->default(false);

            $table->bigInteger('cheque_master_id')->nullable();
            $table->bigInteger('rtgs_form_id')->nullable();

            // Core Info
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('account_group_id')->constrained('account_groups')->onDelete('restrict');
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');


            $table->softDeletes();
            $table->timestamps();
        
            // Unique constraints
            $table->unique(['company_id', 'name','is_active']);
            $table->unique(['company_id', 'code','is_active']);
            $table->index(['company_id', 'is_hidden', 'name'], 'idx_company_hidden_name');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

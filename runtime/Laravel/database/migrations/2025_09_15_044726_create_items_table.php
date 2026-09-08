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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            
            $table->string('name');
            $table->string('sku');
            $table->string('print_name');
            $table->foreignId('item_group_id')->nullable()->constrained('item_groups')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('tax_category_id')->nullable()->constrained('tax_categories')->nullOnDelete();
            $table->string('hsn_sac_code')->nullable();
            $table->foreignId('sale_type_local_id')->nullable()->constrained('sale_types')->nullOnDelete();
            $table->foreignId('sale_type_interstate_id')->nullable()->constrained('sale_types')->nullOnDelete();
            $table->foreignId('purchase_type_local_id')->nullable()->constrained('purchase_types')->nullOnDelete();
            $table->foreignId('purchase_type_interstate_id')->nullable()->constrained('purchase_types')->nullOnDelete();
            // $table->enum('is_maintain_stock_balance', ['yes','no']);
            $table->boolean('is_maintain_stock_balance')->default(true);
            
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('status')->default(true)->comment('Indicates active record or not');
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');
            $table->softDeletes();
            $table->timestamps();
            
            $table->unique(['company_id', 'name', 'is_active']);
            $table->unique(['company_id', 'sku', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

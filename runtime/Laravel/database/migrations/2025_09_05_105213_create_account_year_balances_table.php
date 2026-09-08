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
        Schema::create('account_year_balances', function (Blueprint $table) {
            $table->id();
    
            // Opening & closing balances
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('opening_type', ['D', 'C'])->default('D');
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->enum('closing_type', ['D', 'C'])->default('D');

            // Foreign keys
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('cascade');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
    
            $table->softDeletes();
            $table->timestamps();
    
            
            $table->unique(
                ['company_id', 'account_id', 'financial_year_id'], 
                'uniq_company_account_year'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_year_balances');
    }
};

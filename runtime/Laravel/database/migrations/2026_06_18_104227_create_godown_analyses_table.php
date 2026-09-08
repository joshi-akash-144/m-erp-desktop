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
        Schema::create('godown_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            //Company and Financial
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();

            //Grn ID
            $table->foreignId('grn_id')->constrained('grns')->cascadeOnDelete();

            //Amount
            $table->decimal('rebate_total', 15, 2)->default(0);
            $table->decimal('rebate_percentage', 5, 2)->default(0);
            $table->decimal('premium_total', 15, 2)->default(0);

            //Status
            $table->boolean('status')->default(true);

            //Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('godown_analyses');
    }
};

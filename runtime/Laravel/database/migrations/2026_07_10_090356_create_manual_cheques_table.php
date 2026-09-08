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
        Schema::create('manual_cheques', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // $table->bigInteger('company_id');            
            $table->bigInteger('account_id')->nullable();
            $table->bigInteger('cheque_format_id')->nullable();
            
            $table->string('name')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('cheque_date')->nullable();
            
            $table->boolean('account_payee')->default(false);           
            $table->boolean('rtgs')->default(false);
            $table->boolean('is_approved')->default(false);
            
            $table->bigInteger('approved_by')->nullable(); 
           
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_cheques');
    }
};

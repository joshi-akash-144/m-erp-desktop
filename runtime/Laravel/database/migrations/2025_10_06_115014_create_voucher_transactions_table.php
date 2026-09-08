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
        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            
            $table->foreignId('against_account_id')
            ->nullable()
            ->constrained('accounts')
            ->nullOnDelete();

            // Separate columns for DR and CR
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            
            
            $table->string('narration')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->integer('line_no')->nullable();
            $table->boolean('is_party_account')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('voucher_id');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_transactions');
    }
};

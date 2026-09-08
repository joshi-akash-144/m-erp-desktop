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
        Schema::create('account_code_sequences', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('last_code')->default(0);
            
            $table->foreignId('account_group_id')->constrained('account_groups')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'account_group_id','deleted_at'], 'unique_account_code_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_account_code_sequences');
    }
};

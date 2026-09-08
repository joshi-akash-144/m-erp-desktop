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
        Schema::create('party_master_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('party_master_id')
                ->constrained('party_masters')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id');

            $table->timestamps();

            // Prevent duplicate mapping
            $table->unique(
                ['party_master_id', 'company_id', 'account_id'],
                'party_account_mapping_unique'
            );
            $table->index('company_id');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_master_details');
    }
};

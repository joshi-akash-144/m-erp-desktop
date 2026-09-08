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
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_account_id');
            $table->unsignedBigInteger('old_account_id');
            $table->unsignedBigInteger('old_account_group_id');
            $table->unsignedBigInteger('new_account_group_id');
            $table->string('account_type');
            $table->integer('company_id');
            $table->string('old_account_name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};

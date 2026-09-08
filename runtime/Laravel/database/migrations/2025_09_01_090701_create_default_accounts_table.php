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
        Schema::create('default_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('party_type')->default('account');
            $table->string('slug')->unique();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedBigInteger('group_code')->nullable();
            $table->boolean('status')->default(true);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_accounts');
    }
};

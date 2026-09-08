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
        Schema::create('transport_journal_mappings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id');
            $table->string('old_voucher_no');
            $table->string('new_voucher_no');
            $table->bigInteger('old_table_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_journal_mappings');
    }
};

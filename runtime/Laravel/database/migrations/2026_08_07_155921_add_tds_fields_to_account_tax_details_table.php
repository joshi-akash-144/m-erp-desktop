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
        Schema::table('account_tax_details', function (Blueprint $table) {
            $table->foreignId('tds_category_id')->nullable()->constrained('tds_categories')->nullOnDelete();
            $table->foreignId('payee_category_id')->nullable()->constrained('payee_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_tax_details', function (Blueprint $table) {
            $table->dropForeign(['tds_category_id']);
            $table->dropForeign(['payee_category_id']);
            $table->dropColumn(['tds_category_id', 'payee_category_id']);
        });
    }
};

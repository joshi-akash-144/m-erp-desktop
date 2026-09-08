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
        Schema::table('manual_cheques', function (Blueprint $table) {
            if (!Schema::hasColumn('manual_cheques', 'company_id')) {
                $table->bigInteger('company_id')->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('manual_cheques', 'financial_year_id')) {
                $table->bigInteger('financial_year_id')->nullable()->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_cheques', function (Blueprint $table) {
            if (Schema::hasColumn('manual_cheques', 'company_id')) {
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('manual_cheques', 'financial_year_id')) {
                $table->dropColumn('financial_year_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_sundries', function (Blueprint $table) {
            $table->unsignedBigInteger('tds_category_id')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('bill_sundries', function (Blueprint $table) {
            $table->dropColumn('tds_category_id');
        });
    }
};

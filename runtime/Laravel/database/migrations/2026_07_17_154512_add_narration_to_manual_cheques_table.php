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
            $table->text('narration')->nullable()->after('cheque_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_cheques', function (Blueprint $table) {
            $table->dropColumn('narration');
        });
    }
};

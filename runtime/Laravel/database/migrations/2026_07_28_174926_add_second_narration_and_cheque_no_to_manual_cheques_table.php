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
            $table->string('cheque_no')->nullable()->after('cheque_date');
            $table->text('second_narration')->nullable()->after('narration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_cheques', function (Blueprint $table) {
            $table->dropColumn(['cheque_no', 'second_narration']);
        });
    }
};

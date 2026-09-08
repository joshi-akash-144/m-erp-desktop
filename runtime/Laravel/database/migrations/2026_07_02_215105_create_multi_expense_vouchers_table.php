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
        Schema::create('multi_expense_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            // $table->unsignedBigInteger('voucher_id')->nullable();
            $table->date('voucher_date');
            $table->string('day_for')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('expense_account_id');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('narration')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_expense_vouchers');
    }
};

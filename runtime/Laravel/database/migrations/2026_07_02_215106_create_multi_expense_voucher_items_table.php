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
        Schema::create('multi_expense_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('multi_expense_voucher_id');
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->foreign('multi_expense_voucher_id')->references('id')->on('multi_expense_vouchers')->cascadeOnDelete();
            // $table->index('multi_expense_voucher_id');
            // $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_expense_voucher_items');
    }
};

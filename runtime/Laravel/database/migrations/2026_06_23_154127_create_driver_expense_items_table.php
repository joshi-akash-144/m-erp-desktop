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
        Schema::create('driver_expense_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_expense_id');
            $table->date('billing_date')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('from_destination_id')->nullable();
            $table->unsignedBigInteger('to_destination_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('dc_lr')->nullable();
            $table->decimal('bags', 12, 2)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('weight', 12, 2)->default(0);
            $table->unsignedInteger('trips')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->foreign('driver_expense_id')->references('id')->on('driver_expenses')->cascadeOnDelete();
            $table->index('driver_expense_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_expense_items');
    }
};

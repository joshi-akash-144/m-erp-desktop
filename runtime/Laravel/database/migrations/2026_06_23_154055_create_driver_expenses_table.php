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
        Schema::create('driver_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->date('voucher_date');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('driver_silak_balance', 12, 2)->default(0);
            $table->text('narration')->nullable();
            $table->decimal('start_kms', 12, 2)->default(0);
            $table->decimal('end_kms', 12, 2)->default(0);
            $table->decimal('total_kms', 12, 2)->default(0);
            $table->decimal('start_diesel', 12, 2)->default(0);
            $table->decimal('end_diesel', 12, 2)->default(0);
            $table->decimal('diesel_average', 8, 2)->default(0);
            $table->unsignedInteger('idle_days')->default(0);
            $table->decimal('idle_day_wage', 12, 2)->default(0);
            $table->decimal('idle_day_wage_amount', 12, 2)->default(0);
            $table->decimal('expense_total', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_expenses');
    }
};

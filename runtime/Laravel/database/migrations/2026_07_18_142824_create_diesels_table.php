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
        Schema::create('diesels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('cascade');
            $table->date('voucher_date');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');

            $table->decimal('diesel_rate', 15, 2)->default(0);
            $table->decimal('no_of_vehicles', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('narration', 500)->nullable();

            // Flags and tracking
            // $table->boolean('is_closed')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diesels');
    }
};

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
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('status')->default(true)->comment('Indicates if the Financial Year is active or inactive');

            $table->boolean('is_current')->default(true)
            ->comment('true = current open FY, false = closed FY');

            // $table->boolean('is_closed')->default(false)->comment('Books closed for this year');
            $table->timestamp('closed_at')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'name']);

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_years');
    }
};

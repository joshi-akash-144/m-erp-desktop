<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_note_sundries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('sundry_id')->nullable();

            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('bill_sundry_type')->nullable();    // additive / subtractive
            $table->string('calculation_type')->nullable();    // percentage / fixed
            $table->string('apply_on')->nullable();

            $table->unsignedBigInteger('bill_sundry_modal_dr_id')->nullable();
            $table->unsignedBigInteger('bill_sundry_modal_cr_id')->nullable();

            $table->decimal('base_amount',  15, 2)->default(0);
            $table->decimal('rate_percent',  8, 4)->default(0);
            $table->decimal('value',        15, 2)->default(0);
            $table->decimal('amount',       15, 2)->default(0);

            $table->integer('sort_order')->default(0);
            $table->string('remarks')->nullable();
            $table->boolean('affect_net_total')->default(false);

            $table->timestamps();

            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_sundries');
    }
};

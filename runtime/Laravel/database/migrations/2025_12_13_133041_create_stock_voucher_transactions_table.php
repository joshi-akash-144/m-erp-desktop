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
        Schema::create('stock_voucher_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_voucher_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_id')->constrained();
            // $table->foreignId('godown_id')->nullable()->constrained();

            $table->decimal('in_qty', 15, 4)->default(0);
            $table->decimal('out_qty', 15, 4)->default(0);

            $table->decimal('rate', 15, 4);
            $table->decimal('amount', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_voucher_transactions');
    }
};

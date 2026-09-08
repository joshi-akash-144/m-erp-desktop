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
        Schema::create('diesel_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diesel_id')->constrained('diesels')->onDelete('cascade');
            $table->string('challan_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->date('last_date')->nullable();
            $table->date('today_date')->nullable();
            $table->decimal('diesel', 15, 2)->default(0);
            $table->decimal('old_km', 15, 2)->default(0);
            $table->decimal('new_km', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('diff', 15, 2)->default(0);
            $table->decimal('average', 15, 2)->default(0);
            $table->string('remark')->nullable();
            
            // Flags for cross-checking in Multi Expense Voucher
            $table->boolean('is_closed')->default(false);
            $table->unsignedBigInteger('closed_by_voucher_id')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diesel_items');
    }
};

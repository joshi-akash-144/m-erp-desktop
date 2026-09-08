<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('freight_contractor_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_id')->constrained('freights')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete()->comment('society');
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('code')->nullable();
            $table->string('route')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('bag_count', 10)->default(0);
            $table->decimal('kms', 10, 4)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_contractor_items');
    }
};

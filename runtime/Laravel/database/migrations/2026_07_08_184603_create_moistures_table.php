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
        Schema::create('moistures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');

            $table->foreignId('grn_id')->constrained()->onDelete('cascade');
            $table->foreignId('godown_module_id')->constrained()->onDelete('cascade');

            $table->decimal('challan_weight', 10, 4)->default(0);
            $table->decimal('new_challan_weight', 10, 4)->default(0);

            $table->decimal('gross_weight', 10, 4)->default(0);
            $table->decimal('new_gross_weight', 10, 4)->default(0);

            $table->decimal('tare_weight', 10, 4)->default(0);
            $table->decimal('new_tare_weight', 10, 4)->default(0);

            $table->decimal('net_weight', 10, 4)->default(0);
            $table->decimal('new_net_weight', 10, 4)->default(0);


            $table->decimal('net_weight_wt_bag', 10, 4)->default(0);
            $table->decimal('new_net_weight_wt_bag', 10, 4)->default(0);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moisture');
    }
};

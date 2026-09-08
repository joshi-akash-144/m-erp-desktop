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
        Schema::create('godown_modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-Company + Multi-FY
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');

            $table->foreignId('godown_id')->nullable()->constrained('destinations')->onDelete('cascade');
            $table->foreignId('godown_unit_id')->nullable()->constrained('godowns')->onDelete('cascade');
            $table->foreignId('party_destination_id')->nullable()->constrained('destinations')->onDelete('cascade');

            $table->foreignId('grn_id')->nullable()->constrained('grns')->onDelete('cascade');
            $table->foreignId('delivery_challan_id')->nullable()->constrained('delivery_challans')->onDelete('cascade');

            $table->string('dairy_po')->nullable();

            $table->date('in_date')->nullable()->comment('grn_in_date or challan_in_date');
            $table->date('out_date')->nullable()->comment('grn_out_date or challan_out_date');

            $table->time('in_time')->nullable()->comment('grn_in_time or challan_in_time');
            $table->time('out_time')->nullable()->comment('grn_out_time or challan_out_time');

            $table->enum('is_cycle', ['open', 'close'])->default('open');

            $table->enum('in_out_status', ['in', 'out'])->comment('in = Product In, out = Product Out');

            $table->foreignId('transporter_id')->nullable()->constrained('transporters')->nullOnDelete();
            $table->string('lr_number')->nullable();

            $table->date('challan_date')->nullable();
            $table->string('challan_weight')->nullable();
            $table->string('challan_bags')->nullable();
            
            $table->boolean('is_manual')->default(false);
            $table->boolean('is_crossing')->default(false);

            // Audit Users
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('godown_modules');
    }
};



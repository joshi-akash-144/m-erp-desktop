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
        Schema::create('freights', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();

            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete(); // To Bill (customer)
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();

            $table->string('prefix')->nullable();
            $table->unsignedInteger('invoice_serial')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('grn_serial')->nullable();
            $table->string('lr_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('reference_number')->nullable();
        
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->unsignedBigInteger('consignor_id')->nullable();
            $table->unsignedBigInteger('consignee_id')->nullable();
            $table->foreignId('from_destination_id')->nullable()->constrained('destinations')->nullOnDelete();
            $table->foreignId('to_destination_id')->nullable()->constrained('destinations')->nullOnDelete();

            $table->decimal('total_amount', 15, 2)->default(0);

            // Entry From
            $table->string('entry_from')->default('voucher'); // voucher / invoice
           
            $table->boolean('status')->default(true);
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freights');
    }
};

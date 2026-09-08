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
        Schema::create('account_tax_details', function (Blueprint $table) {
            $table->id();
        
            // Tax details
            $table->enum('type_of_dealer', ['registered','unregistered','composition','uni_holder'])->nullable();
            $table->enum('filing_frequency', ['not_known','monthly','quarterly'])->nullable();
            $table->enum('tax_type', ['igst','cgst','sgst','professional_tax'])->nullable();
            $table->enum('gst_type', ['gst_applicable','gst_not_applicable','non_gst'])->nullable();
            $table->string('gst_number', 25)->nullable();
            $table->string('tin', 15)->nullable();
            $table->string('pan',20)->nullable();
            $table->string('hsn_sac_code')->nullable();
            $table->enum('itc_eligibility', ['input_goods','input_service','capital_goods','none'])->nullable();
            $table->enum('rcm_nature', ['compulsory','service_import','based_on_daily_limit','not_applicable'])->nullable();;
           
             // Foreign keys using modern method
             $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
           
             $table->foreignId('tax_category_id')->nullable()->constrained('tax_categories')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_tax_details');
    }
};

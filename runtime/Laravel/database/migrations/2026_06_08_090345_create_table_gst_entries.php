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
        Schema::create('gst_entries', function (Blueprint $table) {

            $table->id();

            // Company & FY
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');

            // Voucher References
            $table->unsignedBigInteger('voucher_id');
            $table->unsignedBigInteger('voucher_transaction_id')->nullable();

            // Party
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('gstin', 20)->nullable();

            // Document
            $table->unsignedBigInteger('voucher_type_id')->nullable();
            $table->unsignedTinyInteger('document_type')->default(1)->comment('1=INV (Tax Invoice), 2=CRN (Credit Note), 3=DBN (Debit Note)');

            $table->string('invoice_no', 100);
            $table->date('invoice_date');

            // Amendment Tracking
            $table->boolean('is_amendment')->default(false);
            $table->string('original_invoice_no')->nullable();
            $table->date('original_invoice_date')->nullable();

            // Item Details
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name')->nullable();

            $table->string('hsn_code', 20)->nullable();
            $table->string('uqc', 20)->nullable();

            $table->decimal('qty', 18, 3)->default(0);

            // GST Classification
            $table->unsignedTinyInteger('supply_type')->nullable()->comment('1=B2B, 2=B2CL, 3=B2CS, 4=EXPORT, 5=SEZ, 6=DEEMED_EXPORT, 7=IMPORT_GOODS, 8=IMPORT_SERVICES');

            $table->string('report_category', 50)->nullable();

            $table->string('place_of_supply', 100)->nullable();

            // Amounts
            $table->decimal('taxable_amount', 18, 2)->default(0);

            $table->decimal('cgst_rate', 8, 2)->default(0);
            $table->decimal('cgst_amount', 18, 2)->default(0);

            $table->decimal('sgst_rate', 8, 2)->default(0);
            $table->decimal('sgst_amount', 18, 2)->default(0);

            $table->decimal('igst_rate', 8, 2)->default(0);
            $table->decimal('igst_amount', 18, 2)->default(0);

            $table->decimal('cess_rate', 8, 2)->default(0);
            $table->decimal('cess_amount', 18, 2)->default(0);

            $table->decimal('total_tax_amount', 18, 2)->default(0);
            $table->decimal('invoice_value', 18, 2)->default(0);

            // GST Flags
            $table->boolean('reverse_charge')->default(false);
            $table->boolean('is_ecommerce')->default(false);

            // Ecommerce
            $table->string('ecommerce_gstin', 20)->nullable();

            // ITC
            $table->unsignedTinyInteger('itc_eligibility')->nullable()->comment('1=Inputs, 2=Capital Goods, 3=Input Services, 4=Ineligible');
            $table->unsignedTinyInteger('itc_type')->nullable()->comment('1=ITC Available, 2=ITC Reversed, 3=Ineligible ITC');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_entries');
    }
};
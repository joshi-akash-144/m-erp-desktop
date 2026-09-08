<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_way_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('sales_invoice_id')->nullable()->index();
            // Populated when EWB is generated via E-Invoice IRN
            $table->unsignedBigInteger('e_invoice_id')->nullable()->index();

            $table->string('ewb_no', 30)->index();
            $table->string('ewb_date', 30)->nullable();
            $table->string('valid_upto', 30)->nullable();

            // Transport details
            $table->enum('trans_mode', ['1', '2', '3', '4'])->nullable()->comment('1=Road,2=Rail,3=Air,4=Ship');
            $table->string('transporter_name', 100)->nullable();
            $table->string('transporter_id', 15)->nullable();
            $table->string('trans_doc_no', 15)->nullable();
            $table->string('trans_doc_date', 12)->nullable();
            $table->string('vehicle_no', 15)->nullable();
            $table->enum('vehicle_type', ['R', 'O'])->nullable()->comment('R=Regular,O=ODC');

            $table->enum('status', ['active', 'cancelled'])->default('active')->index();
            $table->string('cancel_reason_code', 5)->nullable();
            $table->string('cancel_remark', 300)->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->enum('environment', ['sandbox', 'production'])->default('production');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('sales_invoice_id')
                  ->references('id')->on('sales_invoices')
                  ->onDelete('set null');

            $table->foreign('e_invoice_id')
                  ->references('id')->on('e_invoices')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_way_bills');
    }
};

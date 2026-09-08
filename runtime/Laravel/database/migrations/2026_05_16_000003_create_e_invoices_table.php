<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('sales_invoice_id')->nullable()->index();

            $table->string('irn', 64)->unique();
            $table->string('ack_no', 30)->nullable();
            $table->string('ack_dt', 30)->nullable();

            // INV = Sales Invoice, CRN = Credit Note (sales side only — DBN not used)
            $table->enum('doc_type', ['INV', 'CRN']);
            $table->string('doc_no', 16);
            $table->string('doc_date', 12);

            $table->text('signed_qr_code')->nullable();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoices');
    }
};

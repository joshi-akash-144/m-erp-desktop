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
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');
            $table->enum('action', [
                'create',
                'update',
                'delete',
                'restore',
                'approve',
                'cancel',
                'post',
                'unpost',
                'print',
                'export',
                'login',
                'logout'
            ]);
            $table->string('module'); // INVOICE, PAYMENT, JOURNAL, LEDGER
            $table->tinyInteger('record_type')->default(2); // 1 = Master, 2 = Voucher
            $table->string('model_name');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('version')->default(1);

            $table->string('user_name');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();

            $table->string('request_url')->nullable();
            $table->tinyInteger('http_method')->nullable();
            $table->decimal('org_amount', 15, 2)->nullable();
            $table->decimal('final_amount', 15, 2)->nullable();

            $table->tinyInteger('status')->default(1); // 1 = success, 2 = failed
            $table->timestamp('performed_at');
            $table->timestamps();


            // $table->index(['model_name', 'record_id']);
            // $table->index('user_id');
            // $table->index('action');
            // $table->index('company_id', 'financial_year_id');
            // $table->index('module');
            // $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};

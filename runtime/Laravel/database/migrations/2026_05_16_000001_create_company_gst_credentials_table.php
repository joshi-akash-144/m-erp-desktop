<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_gst_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('type', ['eway_bill', 'e_invoice']);

            // Sandbox credentials
            $table->string('sandbox_client_id')->nullable();
            $table->string('sandbox_secret_id')->nullable();
            $table->string('sandbox_gstin', 20)->nullable();
            $table->string('sandbox_email')->nullable();
            $table->string('sandbox_username')->nullable();
            $table->text('sandbox_password')->nullable();

            // Production credentials
            $table->string('production_client_id')->nullable();
            $table->string('production_secret_id')->nullable();
            $table->string('production_gstin', 20)->nullable();
            $table->string('production_email')->nullable();
            $table->string('production_username')->nullable();
            $table->text('production_password')->nullable();
            $table->string('sandbox_base_url')->nullable();
            $table->string('production_base_url')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'type']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_gst_credentials');
    }
};

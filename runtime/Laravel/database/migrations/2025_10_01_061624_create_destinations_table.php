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
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 255);
            $table->string('contact_person_name', 255)->nullable();
            $table->string('email', 255)->nullable(); // Email is made nullable as a Destination may not require one.
            $table->string('mobile_number', 20)->nullable(); // Accommodates international formats
            $table->string('phone_number', 20)->nullable(); // Accommodates international formats

            $table->integer('kms')->default(0);

            $table->string('address_one', 255)->nullable();
            $table->string('address_two', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('district', 255)->nullable();
            $table->string('taluka', 255)->nullable();

            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('postal_code', 20)->nullable();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();


            
            $table->boolean('status')->default(true);
            $table->boolean('is_active')->default(true)->comment('Indicates active row; helps manage uniqueness with soft deletes');

            $table->unique(['company_id', 'name', 'is_active'], 'unique_destination_name');
            // Audit fields for tracking user activity
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
        Schema::dropIfExists('destinations');
    }
};

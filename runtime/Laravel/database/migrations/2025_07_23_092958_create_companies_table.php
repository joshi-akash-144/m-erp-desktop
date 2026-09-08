<?php

use App\Models\Company;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('code', 20);

            $table->string('print_name')->nullable();
            $table->string('legal_name')->nullable();
        
            $table->foreignId('country_id')->constrained('countries')->nullable();
            $table->foreignId('state_id')->constrained('states')->nullable();
            // Location
            $table->text('address_one')->nullable();
            $table->text('address_two')->nullable();
            $table->date('financial_year_start');
        
            // Legal & Tax
            $table->string('cin', 21)->nullable(); // Company Identification Number
            $table->string('pan', 15)->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('tan', 10)->nullable();
    

        
            // Contact
            $table->string('phone_number', 15)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('postal_code')->nullable();
        
            // Finance
            $table->char('currency', 3)->default('INR');
            $table->enum('type_of_dealer', [
               'registered', 'unregistered', 'composition', 'uni_holder'
            ])->nullable();
        

            $table->boolean('status')->default(true)->comment('Indicates if the Company is active or inactive');

            $table->enum('company_type', [
                Company::TRADING,
                Company::TRANSPORT,
                Company::MANUFACTURING,
            ])->default(Company::TRADING);
        
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name','code','deleted_at']);            
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

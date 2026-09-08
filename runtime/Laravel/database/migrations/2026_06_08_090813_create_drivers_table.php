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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            // $table->uuid('uuid')->unique();  
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');        
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');            
            // $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('cascade');            
            $table->date('date_of_joining')->nullable();
            $table->string('license_number')->nullable();
            $table->string('adhara_number')->nullable();
            $table->string('religion')->nullable();
            $table->string('qualification')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('blood_group')->nullable();        
        

            $table->decimal('salary', 15, 2)->default(0)->nullable();            
            $table->tinyInteger('status')->default(1);            

            $table->string("license_category")->nullable();
            $table->string("license_issuing_authority")->nullable();
            $table->date("license_expiry_date_tr")->nullable();
            $table->date('license_expiry_date_nt')->nullable();
               
            $table->longText('remarks')->nullable();

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
        Schema::dropIfExists('drivers');
    }
};

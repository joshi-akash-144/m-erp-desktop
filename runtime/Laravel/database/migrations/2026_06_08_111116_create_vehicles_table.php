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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            // $table->string('license_number')->nullable();
            $table->date('renewal_date')->nullable();                        

            $table->string('model')->nullable(); // Vehicle Model
            $table->string('mfg_year')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('chassis_no')->nullable();
            $table->string('engine_no')->nullable();            
            $table->unsignedTinyInteger('fuel_type')->nullable();
            $table->string('fuel_tank_capacity')->nullable();
            $table->decimal('gross_weight', 15, 2)->nullable();
            $table->decimal('unladen_weight', 15, 2)->nullable();  
            $table->decimal('weight_capacity',15,3)->nullable();                

            $table->string('power_cc')->nullable();
            
            $table->string('insurance_company_name')->nullable();
            $table->string('insurance_policy_no')->nullable();
            
            $table->text('remarks')->nullable();
            
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->foreignId('vehicle_owner_id')->nullable()->constrained('vehicle_owners')->onDelete('cascade');
            // $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('cascade');

            $table->tinyInteger('status')->default(1);

          
            $table->date('national_permit_due_date')->nullable();
            $table->date('fitness_due_date')->nullable();

            // $table->string('agent_name')->nullable();
            $table->date('policy_due_date')->nullable();
            $table->date('passing_due_date')->nullable();
            $table->date('tax_due_date')->nullable();
            $table->date('permit_due_date')->nullable();
            $table->string('puc_no')->nullable();
            $table->date('puc_due_date')->nullable(); 

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
        Schema::dropIfExists('vehicles');
    }
};

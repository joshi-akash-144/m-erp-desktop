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
        Schema::create('account_preferences', function (Blueprint $table) {
            $table->id();
        
            // Fields
            $table->enum('transport_mode', ['road','air','rail','ship'])->default('road');
            $table->integer('distance')->default(0);
            $table->string('station')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('transport')->nullable();

            $table->decimal('purchase_commission_rate',10,2)->default(0);
            $table->decimal('sale_commission_rate',10,2)->default(0);
            

            // Foreign keys
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('sale_type_id')->nullable()->constrained('sale_types')->onDelete('set null');
            $table->foreignId('purchase_type_id')->nullable()->constrained('purchase_types')->onDelete('set null');
            $table->foreignId('sale_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units')->onDelete('set null');
            
            $table->softDeletes();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_preferences');
    }
};

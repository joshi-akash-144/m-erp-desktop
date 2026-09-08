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
        Schema::create('multi_grns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();            
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();


            $table->date('multi_grn_import_date');
            $table->string("inward_no")->nullable();
            $table->string("material_doc_no")->nullable();
            $table->date("truck_inward_date")->nullable();
            $table->string("p_o_no")->nullable();
            $table->string("truck_no")->nullable();
            $table->string("material_desc")->nullable();
            $table->string("vendor_name")->nullable();
            $table->string("gross_wt")->nullable();
            $table->string("tare_wt")->nullable();
            $table->string("nt_wt_with_bag")->nullable();
            $table->string("nt_wt_wo_bag")->nullable();
            $table->string("no_of_bag")->nullable();
            $table->string("av_wt_bag")->nullable();
            $table->string("plant")->nullable();

            

            $table->tinyInteger('status')->default(0)->comment('cancel or deactivate:1, active:0');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_grns');
    }
};

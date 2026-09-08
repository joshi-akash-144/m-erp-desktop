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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique(); 
            $table->integer('gst_code')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Then you can create the states table with a foreign key
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2);
            $table->integer('gst_code');
            $table->unsignedBigInteger('country_id')->default(1);
            $table->boolean('is_union_territory')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->string('name_with_gst_code')
            ->virtualAs("CONCAT(name, ' (code : ', gst_code, ')')");


            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};

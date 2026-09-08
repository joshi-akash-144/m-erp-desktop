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
        Schema::create('grns', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-Company + Multi-FY
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained()->onDelete('cascade');

            // GRN Info
            $table->bigInteger('grn_serial');
            $table->string('grn_number');

            // Supplier / Broker
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('party_type', ['supplier', 'broker', 'customer','account'])->nullable();
            $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Optional Linking with Purchase Bill
            // $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();

            // GRN Dates
            $table->string('contract_number')->nullable();
            $table->date('grn_date');
            $table->date('grn_in_date')->nullable();
            $table->date('grn_out_date')->nullable();

            // Tax
            $table->enum('gst_type', ['local', 'interstate']);

            // Vehicle & Reference
            $table->string('reference_number')->nullable();
            $table->string('vehicle_number')->nullable();

            // Weight Information
            $table->decimal('gross_weight', 15, 4)->default(0);
            $table->decimal('tare_weight', 15, 4)->default(0);
            $table->decimal('net_weight', 15, 4)->default(0);

            // Bags
            $table->enum('bag_type', ['plastic', 'gunny'])->nullable();
            $table->integer('bag_count')->default(0);
            $table->decimal('net_weight_wt_bag', 15, 4)->default(0);

            // Quantity & Totals
            $table->decimal('total_quantity', 15, 4)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            
            // Party Bill Date & URL Path
            $table->date('party_bill_date')->nullable();
            $table->string('url_path')->nullable();
            
            $table->boolean('is_bag_entry')->default(true);
            
            // QC (optional)
            $table->boolean('is_qc_required')->default(false);
            $table->enum('qc_status', ['pending', 'passed', 'failed'])
                ->default('pending');

            // Remarks
            $table->text('remarks')->nullable();

            // Entry From
            $table->string('entry_from')->default('office');
            
            // GRN Status
            $table->enum('grn_status', [
                'open',        // GRN created
                'hold',        // Waiting for QC / approval
                'close',       // Finalized but not billed
                'billed',      // Purchase Bill created
                'cancel',      // Cancelled
                'rejected'     // QC rejected
            ])->default('open');

            // Active / Inactive
            $table->boolean('status')->default(true);

            $table->boolean('is_skip_serial_generation')->default(false);

            // Audit Users
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // Unique Constraints (With soft delete)
            $table->unique(['company_id', 'financial_year_id', 'grn_serial', 'deleted_at'], 'unique_grn_serial');
            $table->unique(['company_id', 'financial_year_id', 'grn_number', 'deleted_at'], 'unique_grn_number');
            $table->unique(['company_id', 'financial_year_id', 'reference_number', 'deleted_at'], 'unique_reference_number');

            // Indexes
            $table->index(['company_id', 'financial_year_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};

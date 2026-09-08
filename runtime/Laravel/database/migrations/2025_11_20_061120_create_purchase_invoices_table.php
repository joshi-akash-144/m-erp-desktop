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
        // Schema::create('purchase_invoices', function (Blueprint $table) {
        //     $table->id();
        //     $table->uuid('uuid')->unique();
        //     $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        //     $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();

        //     $table->bigInteger('invoice_serial'); // numeric, auto-increment logic
        //     $table->string('invoice_number');

        //     $table->date('invoice_date');
        //     $table->date('due_date')->nullable();
        //     $table->date('show_date')->nullable(); // when mail to party this date will be used
        //     $table->date('party_bill_date')->nullable();

        //     $table->foreignId('grn_id')->nullable()->constrained()->cascadeOnDelete();
        //     $table->string('grn_number')->nullable();
        //     $table->unsignedBigInteger('grn_serial')->nullable();

        //     $table->foreignId('purchase_type_id')->constrained();

        //     $table->string('file_number')->nullable();  
        //     $table->string('sales_invoice_serial')->nullable();
        //     $table->foreignId('account_id')->constrained()->cascadeOnDelete();
        //     $table->enum('tax_type',['local', 'interstate']);
        //     $table->string('reference_number')->nullable();
        //     $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();

        //     $table->string('vehicle_number')->nullable();
        //     // $table->foreignId('purchase_invoice_type_id');

        //     $table->text('remarks')->nullable();

        //     // $table->decimal('subtotal', 15, 2); // before tax
        //     $table->decimal('tax_amount', 15, 2)->default(0); // before discount
        //     $table->decimal('total_amount', 15, 2)->default(0); // after discount
        //     $table->decimal('total_tax', 15, 2)->default(0);
        //     $table->decimal('sub_total', 15, 2)->default(0);
        //     $table->decimal('total_quantity', 15, 4)->default(0);
            
        //     // Payment tracking
        //     $table->decimal('paid_amount', 15, 2)->default(0);
        //     $table->decimal('net_amount', 15, 2)->default(0);
        //     $table->decimal('net_total', 15, 2)->default(0);
            
        //     $table->decimal('grand_total', 15, 2)->default(0);
            
        //     $table->enum('payment_status', ['unpaid', 'partially_paid', 'fully_paid', 'overpaid'])->default('unpaid');
            
        //     $table->enum('invoice_status', ['draft', 'approved', 'cancelled'])->default('approved');

        //     $table->foreignId('voucher_id')->nullable()->constrained(); // voucher table id

        //     // Audit Users
        //     $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        //     $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        //     $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            
        //     $table->timestamps();
        //     $table->softDeletes();


        //     $table->unique(['invoice_number', 'company_id'], 'unique_invoice_number');
        // });
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
        
            /*
            |--------------------------------------------------------------------------
            | Company + Financial Year
            |--------------------------------------------------------------------------
            */
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
        
            /*
            |--------------------------------------------------------------------------
            | Invoice Details
            |--------------------------------------------------------------------------
            */
            $table->bigInteger('invoice_serial');                // Auto-managed serial per FY
            $table->string('invoice_number');                    // User-facing invoice no.
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('show_date')->nullable();               // Date shown on mail/PDF
            $table->date('party_bill_date')->nullable();
        
            /*
            |--------------------------------------------------------------------------
            | GRN Details (Optional)
            |--------------------------------------------------------------------------
            */
            $table->foreignId('grn_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('grn_number')->nullable();
            $table->unsignedBigInteger('grn_serial')->nullable();
        
            /*
            |--------------------------------------------------------------------------
            | Purchase Type + Party + Broker
            |--------------------------------------------------------------------------
            */
            $table->foreignId('purchase_type_id')->constrained();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();     // Supplier
            $table->foreignId('broker_id')->nullable()->constrained('accounts')->nullOnDelete();
        
            /*
            |--------------------------------------------------------------------------
            | Additional Invoice Info
            |--------------------------------------------------------------------------
            */
            $table->enum('gst_type', ['local', 'interstate']);
            $table->string('file_number')->nullable();
            $table->string('sales_invoice_serial')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->text('remarks')->nullable();
        
            /*
            |--------------------------------------------------------------------------
            | Amounts + Summary
            |--------------------------------------------------------------------------
            */
            $table->decimal('total_quantity', 15, 4)->default(0);

            $table->decimal('taxable_amount', 15, 2)->default(0); // qty * rate
            $table->decimal('tax_amount', 15, 2)->default(0);     // GST total
            
            // $table->decimal('total_amount', 15, 2)->default(0);   // taxable + tax
            
            $table->decimal('net_amount', 15, 2)->default(0);     // after bill sundry
            $table->decimal('grand_total', 15, 2)->default(0);    // net + sundry(not including in net)
        
            
            /*
            |--------------------------------------------------------------------------
            | Payment Tracking
            |--------------------------------------------------------------------------
            */
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', [
                'unpaid',
                'partially_paid',
                'fully_paid',
                'overpaid'
            ])->default('unpaid');
            
            /*
            |--------------------------------------------------------------------------
            | Status + Voucher
            |--------------------------------------------------------------------------
            */
            $table->enum('invoice_status', ['draft', 'approved', 'cancelled'])
                ->default('approved');
        
            $table->foreignId('voucher_id')->nullable()->constrained();

            $table->boolean('status')->default(true);
            $table->boolean('rebate_from_analysis')->default(false);
        
            /*
            |--------------------------------------------------------------------------
            | Audit / Soft Delete
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        
            $table->timestamps();
            $table->softDeletes();
        
            /*
            |--------------------------------------------------------------------------
            | Unique Constraints
            |--------------------------------------------------------------------------
            */
            $table->unique(['invoice_number', 'company_id'], 'unique_invoice_number');

            // TODO is_dairy_rebate boolen type column add later.
            
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};

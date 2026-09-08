<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// php artisan db:seed --class=PermissionSeeder
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Root User Role
        $superAdminRole = Role::updateOrCreate(['name' => 'Root'], ['guard_name' => 'web']);

        $modules = [
            'role' => ['list', 'view', 'create', 'update', 'delete'],
            'user' => ['list', 'view', 'create', 'update'],
            'tax_category' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'account_group' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'account' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'unit' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'unit_conversion' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete'],
            'item' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'bill_sundry' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'sale_type' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'purchase_type' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'bank_configuration' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'item_group' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'company' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'broker' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],


            'condition' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'element' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'dairy_parameter' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'purchase_order' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'destination' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'godown' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'payee_category' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'tds_category' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'state' => ['list', 'view', 'create', 'update', 'delete'],

            'grn' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'sales_order' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'sales_invoice' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'sales_invoice_receipt' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'credit_note' => ['list', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete', 'export', 'print'],

            'purchase_invoice' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'ledger_report' => ['list', 'print', 'export'],

            'payment_payable' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print', 'hold'],

            'payment_receivable' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            
            'payment_approval' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'payment_hold' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'stock_status' => ['list', 'print', 'export'],

            'journal_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','edit','print-voucher'],
            'credit_note_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','edit','print-voucher'],
            'debit_note_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','edit','print-voucher'],
            'trial_balance' => ['list', 'print', 'export'],

            'purchase_order_with_grn' =>['list','print','export'],
    
            'dairy_analysis' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print', 'index', 'edit'],
            'godown_module' =>['list','product_in','product_out','product_in_manual','product_out_manual', 'export', 'print','transporter_list','transporter_print','transporter_export','delete'],
            'weight_location' => ['list', 'create', 'update'],
            'godown_unit_location' => ['list', 'create', 'update'],

            'dairy_analysis_report' => ['register', 'rebate_pending_report', 'register_export', 'rebate_pending_export', 'register_print', 'rebate_pending_print'],

            'delivery_challan' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','print-letter'],
            'delivery_challan_mobile' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],

            'payments' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'payment_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','print-voucher', 'delete'],
            'receipt_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','print-voucher'],
            'penalty' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'cheque' => ['list', 'create', 'update', 'delete', 'print'],
            'payment_online_rtgs' => ['list', 'create', 'update', 'delete', 'print'],
            'gst_credential' => ['list', 'update'],
            'eway_bill'      => ['manage'],
            'e_invoice'      => ['manage'],

            'transporter' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],  
            'multi_grn' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
             'daybook_report' => ['list', 'print', 'export'],
            'gstr1_report'  => ['list'],
            'gstr2_report'  => ['list'],
            'gstr3b_report' => ['list'],
            'tds_entry'     => ['list', 'export', 'print'],
            'profit_loss'   => ['list', 'print', 'export'],

             'mail_config'     => ['list', 'update'],
             'email_template'  => ['list', 'create', 'update', 'delete'],
            'payment_register' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'receipt_register' => ['list', 'view', 'delete', 'print'],
            'companies' => ['index', 'edit', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'login_user' => ['list', 'force_logout'],

            'mobile_grn' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'self' => ['product_in','product_out','update'],
            'database_backup' => ['list', 'download', 'delete','generate'],
            'vehicle_owner'=>['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'vehicle' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'driver' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'zone' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'expense_type' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'bags_rate' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            // 'mandali' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'transport_party' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'party_master' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'dairy_outstanding' => ['list', 'print','create'],
            'godown_analysis' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'freight' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'dairy_file_import' => ['index', 'day_to_day_list', 'dairy_file_list'],
            'driver_expense' => ['index', 'create','exportSummary'],
            'vehicle_expenditure' => ['list', 'export'],
            'bag_challan_labour'=>['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'], 
            'freight_invoice' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print','printZone','exportZone','reorder'],
            'payment_advice' => ['list','cheque-print','register-print','payment-advice-print','rtgs-print','bank-mail','payment-advice-email'],
            'transport_ref_payment' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'multi_expense_voucher' => ['list', 'view', 'create', 'update', 'delete', 'forceDelete', 'export', 'print'],
            'expense_register' => ['list', 'view', 'delete', 'print','export'],
            'moisture' =>['list', ],
            'manual_cheque' => ['list', 'view',  'create', 'update', 'delete', 'export', 'print'],
            'diesel' => ['list', 'view',  'create', 'update', 'delete', 'export', 'print'],
            'salary' => ['list', 'view',  'create', 'update', 'delete', 'export', 'print'],
            'contractor' => ['list', 'view',  'create', 'update', 'delete', 'export', 'print'],
            'vehicle_income_report' => ['list', 'export', 'print'],
            'freight_invoice2'=> ['list', 'view', 'create', 'update', 'delete', 'export', 'print'],
        ];

        $allPermissions = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::updateOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module]
                );
                $allPermissions[] = $permission->id;
            }
        }

        // Assign all permissions to Root role
        try {
            $superAdminRole->syncPermissions($allPermissions);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Ignore race condition error from concurrent docker containers seeding at the exact same time
        }
    }
}

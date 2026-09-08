<?php

use App\Http\Controllers\AccountBalanceController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BagChallanLabourController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\PaymentOnlineRtgsController;
use App\Http\Controllers\PaymentAdviceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\CompanyModuleController;
use App\Http\Controllers\DairyAnalysisController;
use App\Http\Controllers\DairyAnalysisExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\DaybookReportController;
use App\Http\Controllers\DaybookReportExportController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\DeliveryChallanController;
use App\Http\Controllers\DeliveryChallanExportController;
use App\Http\Controllers\DairyAnalysisRebatePendingExportController;
use App\Http\Controllers\DairyOutstandingController;
use App\Http\Controllers\FetchWeightLocationController;
use App\Http\Controllers\FreightController;
use App\Http\Controllers\FreightExportController;
use App\Http\Controllers\GodownModuleController;
use App\Http\Controllers\MoistureController;
use App\Http\Controllers\GodownModuleExportController;
use App\Http\Controllers\GodownAnalysisController;
use App\Http\Controllers\GodownAnalysisExportController;
use App\Http\Controllers\GrnController;
use App\Http\Controllers\GrnExportController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\CreditNoteVoucherController;
use App\Http\Controllers\DebitNoteVoucherController;
use App\Http\Controllers\PaymentVoucherController;
use App\Http\Controllers\LedgerReportController;
use App\Http\Controllers\LedgerModalController;
use App\Http\Controllers\LockScreenController;
use App\Http\Controllers\MultiGrnController;
use App\Http\Controllers\MobileGrnController;
use App\Http\Controllers\ManualChequeController;
use App\Http\Controllers\ONACPaymentPayableController;
use App\Http\Controllers\PaymentApprovalController;
use App\Http\Controllers\PaymentHoldController;
use App\Http\Controllers\PaymentPayableController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseInvoiceExportController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderExportController;
use App\Http\Controllers\PurchaseOrderWithGrnExportController;
use App\Http\Controllers\PaymentReceivableController;
use App\Http\Controllers\PaymentRegisterController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\ReferenceSettlementController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReceiptRegisterController;
use App\Http\Controllers\ReceiptVoucherController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesInvoiceExportController;
use App\Http\Controllers\SalesInvoiceReceiptController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesOrderExportController;
use App\Http\Controllers\SalesOrderWithBillExportController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\WeightLocationController;
use App\Http\Controllers\GstCredentialController;
use App\Http\Controllers\MailConfigController;
use App\Http\Controllers\BankMailConfigController;
use App\Http\Controllers\GstPortalController;
use App\Http\Controllers\EwayBillController;
use App\Http\Controllers\EInvoiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockExportController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginUserController;
use App\Http\Controllers\Gstr1ReportController;
use App\Http\Controllers\Gstr2ReportController;
use App\Http\Controllers\Gstr3bReportController;
use App\Http\Controllers\PartyMasterController;
use App\Http\Controllers\TdsEntryController;
use App\Http\Controllers\TdsEntryExportController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\MailLogController;
use App\Http\Controllers\PaymentBillDetailController;

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return Auth::check() ? redirect('/dashboard') : redirect('/login');
    });

    /*
    |--------------------------------------------------------------------------
    | Lock Screen — auth only (no screen_lock check, these ARE the lock routes)
    |--------------------------------------------------------------------------
    */
    Route::get('/lock', [LockScreenController::class, 'showLock'])->name('lock.screen');
    Route::post('/unlock', [LockScreenController::class, 'unlock'])->name('unlock.screen');
    Route::get('/lock-status', function () {
        return response()->json(['locked' => session('locked') ?? false]);
    })->name('lock.status');

    Route::middleware('screen_lock')->group(function () {

        /*
        |----------------------------------------------------------------------
        | Company-Independent Routes
        | (auth + screen_lock — no company selection required)
        |----------------------------------------------------------------------
        */

        //Create an route for all settings
        Route::prefix('setting')->as('setting.')->group(function () {
            Route::post('{module}/{key}', [SettingController::class, 'store'])->name('store');
            Route::get('{module}/{key}', [SettingController::class, 'show'])->name('show');
        });

        Route::prefix('company-selection')->as('company-selection.')->group(function () {
            Route::get('/', [CompanySwitchController::class, 'index'])->name('index');
            Route::get('/list', [CompanySwitchController::class, 'show'])->name('show');
            Route::post('/', [CompanySwitchController::class, 'store'])->name('store');
            Route::get('/exit', [CompanySwitchController::class, 'exit'])->name('exit');
        });

        Route::prefix('companies')->as('companies.')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('/create', [CompanyController::class, 'create'])->name('create');
            Route::post('/', [CompanyController::class, 'store'])->name('store');
            Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
            Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
            // Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
            // Route::get('/{company}/show', [CompanyController::class, 'show'])->name('show');
            // Route::post('/{company}/restore', [CompanyController::class, 'restore'])->name('restore');
            // Route::get('/list', [CompanyController::class, 'list'])->name('list');
        });

        Route::prefix('email-templates')->as('email-templates.')->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
            Route::get('/list', [EmailTemplateController::class, 'list'])->name('list');
            Route::get('/create', [EmailTemplateController::class, 'create'])->name('create');
            Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
            Route::get('/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
            Route::put('/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('update');
            Route::delete('/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('weight-locations')->as('weight_location.')->group(function () {
            Route::get('/', [WeightLocationController::class, 'index'])->name('index');
            Route::get('/create', [WeightLocationController::class, 'create'])->name('create');
            Route::post('/', [WeightLocationController::class, 'store'])->name('store');
            Route::get('/fetch-weight/{godownLocationId}', [FetchWeightLocationController::class, 'getWeightLocationData'])->name('fetch-weight');
            Route::get('/{id}', [WeightLocationController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [WeightLocationController::class, 'edit'])->name('edit');
            Route::put('/update/{id}', [WeightLocationController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [WeightLocationController::class, 'destroy'])->name('destroy');
        });


        Route::prefix('roles')->as('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{roles}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{roles}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{roles}', [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/{roles}/show', [RoleController::class, 'show'])->name('show');
            Route::post('/{roles}/restore', [RoleController::class, 'restore'])->name('restore');
            Route::get('/list', [RoleController::class, 'list'])->name('list');
        });
        
        Route::prefix('permissions')->as('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::get('/create', [PermissionController::class, 'create'])->name('create');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::get('/{permission}', [PermissionController::class, 'show'])->name('show');
            Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
            Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('users')->as('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('login-users')->as('login-users.')->group(function () {
            Route::get('/', [LoginUserController::class, 'index'])->name('index');
            Route::get('/list', [LoginUserController::class, 'list'])->name('list');
            Route::post('/{id}/force-logout', [LoginUserController::class, 'forceLogout'])->name('force-logout');
        });

        Route::prefix('database-backup')->as('database-backup.')->group(function () {
            Route::get('/', [DatabaseBackupController::class, 'index'])->name('index');
            Route::get('/list', [DatabaseBackupController::class, 'list'])->name('list');
            Route::post('/store', [DatabaseBackupController::class, 'store'])->name('store');
            Route::get('/download/{file}', [DatabaseBackupController::class, 'download'])->name('download');
            Route::delete('/{file}', [DatabaseBackupController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('company-users')->as('company-users.')->group(function () {
            Route::get('/', [CompanyUserController::class, 'index'])->name('index');
            Route::get('/create', [CompanyUserController::class, 'create'])->name('create');
            Route::post('/', [CompanyUserController::class, 'store'])->name('store');
            Route::patch('/status', [CompanyUserController::class, 'update'])->name('update');
            Route::delete('/', [CompanyUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('company-modules')->as('company-modules.')->group(function () {
            Route::get('/', [CompanyModuleController::class, 'index'])->name('index');
            Route::get('/create', [CompanyModuleController::class, 'create'])->name('create');
            Route::post('/', [CompanyModuleController::class, 'store'])->name('store');
            Route::put('/status', [CompanyModuleController::class, 'update'])->name('update');
            Route::delete('/', [CompanyModuleController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('profiles')->as('profiles.')->group(function () {
            Route::get('/', [UserController::class, 'profile'])->name('profile');
            Route::put('/', [UserController::class, 'updateprofile'])->name('profile-update');
            Route::get('/change-password', [UserController::class, 'changepassword'])->name('changepassword');
            Route::put('/update-password', [UserController::class, 'updatePassword'])->name('updatepassword');

            // Two-Factor Authentication management
            Route::prefix('two-factor-auth')->as('two-factor.')->group(function () {
                Route::get('/', [TwoFactorController::class, 'index'])->name('index');
                Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable');
                Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
                Route::delete('/disable', [TwoFactorController::class, 'disable'])->name('disable');
                Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
            });
        });

        Route::prefix('states')->as('states.')->group(function () {
            Route::get('/', [StateController::class, 'index'])->name('index');
            Route::get('/create', [StateController::class, 'create'])->name('create');
            Route::post('/', [StateController::class, 'store'])->name('store');
            Route::get('/{state}', [StateController::class, 'show'])->name('show');
            Route::get('/{state}/edit', [StateController::class, 'edit'])->name('edit');
            Route::put('/{state}', [StateController::class, 'update'])->name('update');
            Route::delete('/{state}', [StateController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('party-masters')->as('party-masters.')->group(function () {
            Route::get('/', [PartyMasterController::class, 'index'])->name('index');
            Route::get('/list', [PartyMasterController::class, 'list'])->name('list');
            Route::get('/create', [PartyMasterController::class, 'create'])->name('create');
            Route::post('/', [PartyMasterController::class, 'store'])->name('store');
            Route::delete('/{id}', [PartyMasterController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('dairy-outstanding')->as('dairy-outstanding.')->group(function () {
            Route::get('/', [DairyOutstandingController::class, 'index'])->name('index');
            Route::get('/print', [DairyOutstandingController::class, 'print'])->name('print');
        });



        /*
        |----------------------------------------------------------------------
        | Company-Dependent Routes
        | (auth + screen_lock + check.company.year — company must be selected)
        |----------------------------------------------------------------------
        */

        Route::middleware(['check.company.year', 'read.session'])->group(function () {

            Route::get('/back', [NavigationController::class, 'back'])->name('back.to.previous');

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/filters', [DashboardController::class, 'filters'])->name('dashboard.filters');
            Route::get('/dashboard/recent-invoices', [DashboardController::class, 'recentInvoices'])->name('dashboard.recent-invoices');
            Route::get('/dashboard/top-products', [DashboardController::class, 'topSellingProducts'])->name('dashboard.top-products');
            Route::get('/dashboard/godown-details', [DashboardController::class, 'godownDetails'])->name('dashboard.godown-details');
            Route::get('/dashboard/location-grn', [DashboardController::class, 'locationWiseGrn'])->name('dashboard.location-grn');
            Route::get('/dashboard/location-grn-details', [DashboardController::class, 'locationWiseGrnDetails'])->name('dashboard.location-grn-details');
            Route::get('/dashboard/creditors', [DashboardController::class, 'creditors'])->name('dashboard.creditors');
            Route::get('/dashboard/debtors', [DashboardController::class, 'debtors'])->name('dashboard.debtors');

            require __DIR__ . '/lookup.php';

            Route::prefix('masters')->group(function () {
                require __DIR__ . '/master.php';
            });

            Route::prefix('transports')->group(function () {
                require __DIR__ . '/transport.php';
            });

            Route::prefix('vouchers')->group(function () {

                Route::prefix('purchase-orders')->as('purchase-orders.')->group(function () {
                    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
                    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
                    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
                    Route::get('/edit/{purchaseOrder?}', [PurchaseOrderController::class, 'edit'])->name('edit');
                    Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
                    Route::get('/check-contract-unique', [PurchaseOrderController::class, 'checkContractUnique'])->name('check_contract_unique');
                    Route::get('/{purchaseOrder}/show', [PurchaseOrderController::class, 'show'])->name('show');
                    Route::get('/listPurchaseOrderGrn', [PurchaseOrderController::class, 'PurchaseOrderDetailWithGrn'])->name('purchase_order_with_grn');
                    Route::post('/close-multiple', [PurchaseOrderController::class, 'closeMultiple'])->name('close-multiple');
                    Route::get('/print', [PurchaseOrderExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [PurchaseOrderExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('purchase-order-with-grn')->as('purchase-order-with-grn.')->group(function () {
                    Route::get('/print', [PurchaseOrderWithGrnExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [PurchaseOrderWithGrnExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('grns')->as('grns.')->group(function () {
                    Route::get('/', [GrnController::class, 'index'])->name('index');
                    Route::get('/create', [GrnController::class, 'create'])->name('create');
                    Route::post('/', [GrnController::class, 'store'])->name('store');
                    Route::get('/edit/{grn?}', [GrnController::class, 'edit'])->name('edit');
                    Route::put('/{grn}', [GrnController::class, 'update'])->name('update');
                    Route::delete('/delete-selected', [GrnController::class, 'deleteSelected'])->name('delete_selected');
                    Route::delete('/{grn}', [GrnController::class, 'destroy'])->name('destroy');
                    Route::get('/{grn}/show', [GrnController::class, 'show'])->name('show');
                    Route::get('/check-duplicate', [GrnController::class, 'checkDuplicateReference'])->name('check_duplicate_reference');
                    Route::get('/pending-purchase-orders', [GrnController::class, 'fetchPendingPurchaseOrders'])->name('pending_purchase_orders');
                    Route::get('/last-id', [GrnController::class, 'getLastGrnId'])->name('last_id');
                    Route::get('/print', [GrnExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [GrnExportController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/grns-print/{grnId}', [GrnExportController::class, 'grnPrint'])->name('grnPrint');
                });

                Route::prefix('mobile-grns')->as('mobile-grns.')->group(function () {
                    Route::get('/', [MobileGrnController::class, 'index'])->name('index');
                    Route::get('/create', [MobileGrnController::class, 'create'])->name('create');
                    Route::post('/', [MobileGrnController::class, 'store'])->name('store');
                    Route::get('/edit/{grn?}', [MobileGrnController::class, 'edit'])->name('edit');
                    Route::put('/{grn}', [MobileGrnController::class, 'update'])->name('update');
                });

                Route::prefix('purchase-invoices')->as('purchase-invoices.')->group(function () {
                    Route::get('/', [PurchaseInvoiceController::class, 'index'])->name('index');
                    Route::get('/create', [PurchaseInvoiceController::class, 'create'])->name('create');
                    Route::post('/', [PurchaseInvoiceController::class, 'store'])->name('store');
                    Route::get('/edit/{purchaseInvoice?}', [PurchaseInvoiceController::class, 'edit'])->name('edit');
                    Route::put('/{purchaseInvoice}', [PurchaseInvoiceController::class, 'update'])->name('update');
                    Route::get('/{purchaseInvoice}/show', [PurchaseInvoiceController::class, 'show'])->name('show');
                    Route::get('/check-duplicate', [PurchaseInvoiceController::class, 'checkDuplicateReference'])->name('check_duplicate_reference');
                    Route::get('/get-grn-details', [PurchaseInvoiceController::class, 'getGrnDetails'])->name('get_grn_details');
                    Route::get('/print', [PurchaseInvoiceExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [PurchaseInvoiceExportController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/get-supplier-turn-over', [PurchaseInvoiceController::class, 'getSupplierTurnOver'])->name('get_supplier_turn_over');
                });

                Route::prefix('sales-orders')->as('sales-orders.')->group(function () {
                    Route::get('/', [SalesOrderController::class, 'index'])->name('index');
                    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
                    Route::post('/', [SalesOrderController::class, 'store'])->name('store');
                    Route::get('/edit/{salesOrder?}', [SalesOrderController::class, 'edit'])->name('edit');
                    Route::put('/{salesOrder}', [SalesOrderController::class, 'update'])->name('update');
                    Route::get('/{salesOrder}/show', [SalesOrderController::class, 'show'])->name('show');
                    Route::get('/listSalesOrderBill', [SalesOrderController::class, 'SalesOrderDetailWithBill'])->name('sales_order_with_bill');
                    Route::post('/close-multiple', [SalesOrderController::class, 'closeMultiple'])->name('close-multiple');
                    Route::get('/print', [SalesOrderExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [SalesOrderExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('sales-order-with-bill')->as('sales-order-with-bill.')->group(function () {
                    Route::get('/print', [SalesOrderWithBillExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [SalesOrderWithBillExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('sales-invoices')->as('sales-invoices.')->group(function () {
                    Route::get('/', [SalesInvoiceController::class, 'index'])->name('index');
                    Route::get('/create', [SalesInvoiceController::class, 'create'])->name('create');
                    Route::post('/', [SalesInvoiceController::class, 'store'])->name('store');
                    Route::get('/edit/{salesInvoice?}', [SalesInvoiceController::class, 'edit'])->name('edit');
                    Route::put('/{salesInvoice}', [SalesInvoiceController::class, 'update'])->name('update');
                    Route::get('/{salesInvoice}/show', [SalesInvoiceController::class, 'show'])->name('show');
                    Route::get('/validate-grn', [SalesInvoiceController::class, 'validateGrn'])->name('validate_grn');
                    Route::get('/all-sales-orders', [SalesInvoiceController::class, 'allSalesOrders'])->name('allSalesOrders');
                    Route::get('/sales_order/{id}/details', [SalesInvoiceController::class, 'getSalesOrderDetails'])->name('salesOrder.details');
                    Route::get('/print', [SalesInvoiceExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [SalesInvoiceExportController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/sales-print-report/{salesInvoice?}', [SalesInvoiceExportController::class, 'salesPrintReport'])->name('sales-print-report');
                });

                Route::prefix('sales-invoice-receipts')->as('sales-invoice-receipts.')->group(function () {
                    Route::get('/', [SalesInvoiceReceiptController::class, 'index'])->name('index');
                    Route::get('/pending-invoices', [SalesInvoiceReceiptController::class, 'getPendingInvoices'])->name('pending');
                    Route::post('/', [SalesInvoiceReceiptController::class, 'store'])->name('store');
                    Route::get('/report', [SalesInvoiceReceiptController::class, 'report'])->name('report');
                    Route::get('/report-data', [SalesInvoiceReceiptController::class, 'getReceiptReport'])->name('report.data');
                    Route::get('/report-print', [SalesInvoiceReceiptController::class, 'printReceiptReport'])->name('report.print');
                });

                Route::prefix('credit-notes')->as('credit-notes.')->group(function () {
                    Route::get('/',                     [CreditNoteController::class, 'index'])->name('index');
                    Route::get('/create',               [CreditNoteController::class, 'create'])->name('create');
                    Route::post('/',                    [CreditNoteController::class, 'store'])->name('store');
                    Route::get('/edit/{creditNote?}',   [CreditNoteController::class, 'edit'])->name('edit');
                    Route::put('/{creditNote}',         [CreditNoteController::class, 'update'])->name('update');
                    Route::get('/{creditNote}/show',    [CreditNoteController::class, 'show'])->name('show');
                    Route::delete('/{creditNote}',      [CreditNoteController::class, 'destroy'])->name('destroy');
                    Route::get('/sales-invoices',       [CreditNoteController::class, 'getSalesInvoices'])->name('salesInvoices');
                });

                Route::prefix('debit-notes')->as('debit-notes.')->group(function () {
                    Route::get('/',                     [DebitNoteController::class, 'index'])->name('index');
                    Route::get('/create',               [DebitNoteController::class, 'create'])->name('create');
                    Route::post('/',                    [DebitNoteController::class, 'store'])->name('store');
                    Route::get('/edit/{debitNote?}',    [DebitNoteController::class, 'edit'])->name('edit');
                    Route::put('/{debitNote}',          [DebitNoteController::class, 'update'])->name('update');
                    Route::get('/{debitNote}/show',     [DebitNoteController::class, 'show'])->name('show');
                    Route::delete('/{debitNote}',       [DebitNoteController::class, 'destroy'])->name('destroy');
                    Route::get('/purchase-invoices',    [DebitNoteController::class, 'getPurchaseInvoices'])->name('purchaseInvoices');
                });

                Route::prefix('delivery-challans')->as('delivery-challans.')->group(function () {
                    Route::get('/', [DeliveryChallanController::class, 'index'])->name('index');
                    Route::get('/create', [DeliveryChallanController::class, 'create'])->name('create');
                    Route::post('/', [DeliveryChallanController::class, 'store'])->name('store');
                    Route::get('/edit/{deliveryChallan?}', [DeliveryChallanController::class, 'edit'])->name('edit');
                    Route::put('/{deliveryChallan}', [DeliveryChallanController::class, 'update'])->name('update');
                    Route::get('/{id}/show', [DeliveryChallanController::class, 'show'])->name('show');
                    Route::get('/pending-sales-orders', [DeliveryChallanController::class, 'fetchPendingSalesOrders'])->name('pending_sales_orders');
                    Route::get('/check-duplicate-reference', [DeliveryChallanController::class, 'checkDuplicateReference'])->name('check_duplicate_reference');
                    Route::get('/print', [DeliveryChallanExportController::class, 'print'])->name('print');
                    Route::get('/print-letter', [DeliveryChallanExportController::class, 'printLetter'])->name('print-letter');
                    Route::get('/export-excel', [DeliveryChallanExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('delivery-challans-mobile')->as('delivery-challans-mobile.')->group(function () {
                    Route::get('/create', function () {
                        return view('company.pages.delivery-challan-mobile.create');
                    })->name('create');
                });

                Route::prefix('multi-grns')->as('multi-grns.')->group(function () {
                    Route::get('/create', [MultiGrnController::class, 'create'])->name('create');
                    Route::post('/multi-grn-import-process', [MultiGrnController::class, 'multiGrnImport'])->name('import.process');
                });

                Route::prefix('stock-status')->as('stock-status.')->group(function () {
                    Route::get('/', [StockController::class, 'index'])->name('index');
                    Route::get('/print-item-wise', [StockExportController::class, 'printItemWise'])->name('print-item-wise');
                    Route::get('/export-item-wise', [StockExportController::class, 'exportItemWise'])->name('export.item-wise');
                    Route::post('/month-wise-select', [StockController::class, 'selectItemForMonthWise'])->name('month-wise-select');
                    Route::get('/month-wise', [StockController::class, 'monthWiseStockStatus'])->name('month-wise');
                    Route::get('/export-month-wise', [StockExportController::class, 'exportMonthWise'])->name('export.month-wise');
                    Route::get('/print-month-wise', [StockExportController::class, 'printMonthWise'])->name('print-month-wise');
                    Route::post('/date-wise-select', [StockController::class, 'selectItemForDateWise'])->name('date-wise-select');
                    Route::get('/date-wise', [StockController::class, 'dateWiseStockStatus'])->name('date-wise');
                    Route::get('/export-date-wise', [StockExportController::class, 'exportDateWise'])->name('export.date-wise');
                    Route::get('/print-date-wise', [StockExportController::class, 'printDateWise'])->name('print-date-wise');
                });

                Route::prefix('journal-vouchers')->as('journal-vouchers.')->group(function () {
                    Route::get('/', [JournalVoucherController::class, 'index'])->name('index');
                    Route::get('/create', [JournalVoucherController::class, 'create'])->name('create');
                    Route::post('/', [JournalVoucherController::class, 'store'])->name('store');
                    Route::get('/edit', [JournalVoucherController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [JournalVoucherController::class, 'update'])->name('update');
                    Route::get('/voucher-reference/{journalVoucher?}', [JournalVoucherController::class, 'details'])->name('voucher_reference');
                    Route::get('/print', [JournalVoucherController::class, 'print'])->name('print');
                    Route::get('/export-excel', [JournalVoucherController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/print-voucher/{id}', [JournalVoucherController::class, 'printVoucher'])->name('print-voucher');
                    Route::get('/view/{id}', [JournalVoucherController::class, 'details'])->name('view');
                    Route::get('/print-journal/{id}', [JournalVoucherController::class, 'printJournal'])->name('print-journal');
                    Route::delete('/{id}', [JournalVoucherController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('credit-note-vouchers')->as('credit-note-vouchers.')->group(function () {
                    Route::get('/', [CreditNoteVoucherController::class, 'index'])->name('index');
                    Route::get('/create', [CreditNoteVoucherController::class, 'create'])->name('create');
                    Route::post('/', [CreditNoteVoucherController::class, 'store'])->name('store');
                    Route::get('/edit', [CreditNoteVoucherController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [CreditNoteVoucherController::class, 'update'])->name('update');
                    Route::get('/voucher-reference/{creditNoteVoucher?}', [CreditNoteVoucherController::class, 'details'])->name('voucher_reference');
                    Route::get('/print', [CreditNoteVoucherController::class, 'print'])->name('print');
                    Route::get('/export-excel', [CreditNoteVoucherController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/print-voucher/{id}', [CreditNoteVoucherController::class, 'printVoucher'])->name('print-voucher');
                    Route::get('/view/{id}', [CreditNoteVoucherController::class, 'details'])->name('view');
                    Route::get('/print-journal/{id}', [CreditNoteVoucherController::class, 'printJournal'])->name('print-journal');
                    Route::delete('/{id}', [CreditNoteVoucherController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('debit-note-vouchers')->as('debit-note-vouchers.')->group(function () {
                    Route::get('/', [DebitNoteVoucherController::class, 'index'])->name('index');
                    Route::get('/create', [DebitNoteVoucherController::class, 'create'])->name('create');
                    Route::post('/', [DebitNoteVoucherController::class, 'store'])->name('store');
                    Route::get('/edit', [DebitNoteVoucherController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [DebitNoteVoucherController::class, 'update'])->name('update');
                    Route::get('/voucher-reference/{debitNoteVoucher?}', [DebitNoteVoucherController::class, 'details'])->name('voucher_reference');
                    Route::get('/print', [DebitNoteVoucherController::class, 'print'])->name('print');
                    Route::get('/export-excel', [DebitNoteVoucherController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/print-voucher/{id}', [DebitNoteVoucherController::class, 'printVoucher'])->name('print-voucher');
                    Route::get('/view/{id}', [DebitNoteVoucherController::class, 'details'])->name('view');
                    Route::get('/print-journal/{id}', [DebitNoteVoucherController::class, 'printJournal'])->name('print-journal');
                    Route::delete('/{id}', [DebitNoteVoucherController::class, 'destroy'])->name('destroy');
                });
                Route::prefix('payment-vouchers')->as('payment-vouchers.')->group(function () {
                    Route::get('/', [PaymentVoucherController::class, 'index'])->name('index');
                    Route::get('/create', [PaymentVoucherController::class, 'create'])->name('create');
                    Route::post('/', [PaymentVoucherController::class, 'store'])->name('store');
                    Route::get('/edit', [PaymentVoucherController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [PaymentVoucherController::class, 'update'])->name('update');
                    Route::delete('/{id}', [PaymentVoucherController::class, 'destroy'])->name('destroy');
                    Route::get('/voucher-reference/{paymentVoucher?}', [PaymentVoucherController::class, 'details'])->name('voucher_reference');
                    Route::get('/view/{id}', [PaymentVoucherController::class, 'details'])->name('view');
                    Route::get('/print-voucher/{id}', [PaymentVoucherController::class, 'printVoucher'])->name('print-voucher');
                    Route::get('/print', [PaymentVoucherController::class, 'print'])->name('print');
                    Route::get('/export-excel', [PaymentVoucherController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/cheque-print', [PaymentVoucherController::class, 'chequePrint'])->name('cheque.print');
                    Route::get('/pending-purchase-orders', [PaymentVoucherController::class, 'fetchPendingPurchaseOrders'])->name('pending_purchase_orders');
                    Route::get('/payment-advice-print', [PaymentVoucherController::class, 'printPaymentAdvice'])->name('payment_advice_print');
                });

                Route::prefix('receipt-vouchers')->as('receipt-vouchers.')->group(function () {
                    Route::get('/', [ReceiptVoucherController::class, 'index'])->name('index');
                    Route::get('/create', [ReceiptVoucherController::class, 'create'])->name('create');
                    Route::post('/', [ReceiptVoucherController::class, 'store'])->name('store');
                    Route::get('/edit', [ReceiptVoucherController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [ReceiptVoucherController::class, 'update'])->name('update');
                    Route::delete('/{id}', [ReceiptVoucherController::class, 'destroy'])->name('destroy');
                    Route::get('/voucher-reference/{receiptVoucher?}', [ReceiptVoucherController::class, 'details'])->name('voucher_reference');
                    Route::get('/print-voucher/{id}', [ReceiptVoucherController::class, 'printVoucher'])->name('print-voucher');
                    Route::get('/print-receipt/{id}', [ReceiptVoucherController::class, 'printReceipt'])->name('print-receipt');
                    Route::get('/print', [ReceiptVoucherController::class, 'print'])->name('print');
                    Route::get('/export-excel', [ReceiptVoucherController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/view/{id?}', [ReceiptVoucherController::class, 'details'])->name('view');
                });
            }); // end vouchers

            Route::prefix('reference')->as('references.')->group(function () {});

            Route::prefix('reference-settlement')->as('references.')->group(function () {
                Route::get('/payment-payables', [ReferenceSettlementController::class, 'payables'])->name('payment_payables');
                Route::get('/payment-receivables', [ReferenceSettlementController::class, 'receivables'])->name('payment_receivables');
                Route::get('/pending', [ReferenceSettlementController::class, 'pending'])->name('pending');
            });

            Route::prefix('analysis')->group(function () {
                Route::prefix('dairy-analysis')->as('dairy-analysis.')->group(function () {
                    Route::get('/', [DairyAnalysisController::class, 'index'])->name('index');
                    Route::get('/create', [DairyAnalysisController::class, 'create'])->name('create');
                    Route::get('/edit', [DairyAnalysisController::class, 'edit'])->name('edit');
                    Route::post('/', [DairyAnalysisController::class, 'store'])->name('store');
                    Route::put('/{dairy_analysis}', [DairyAnalysisController::class, 'update'])->name('update');
                    Route::get('/get-sales-inv', [DairyAnalysisController::class, 'getSalesInv'])->name('get_sales_inv');
                    Route::get('/get-analysis-by-id', [DairyAnalysisController::class, 'getAnalysisById'])->name('get_analysis_by_id');
                    Route::get('/print-analysis-report/{dairyAnalysis}', [DairyAnalysisController::class, 'printAnalysisReport'])->name('print-analysis-report');
                    Route::get('/list', [DairyAnalysisController::class, 'register'])->name('list');
                    Route::get('/print', [DairyAnalysisExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [DairyAnalysisExportController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/rebate-pending', [DairyAnalysisController::class, 'dairyRebatePending'])->name('rebate-pending.index');

                    // dairy_analysis_rebate_pending print and export
                    Route::get('/rebate-pending-print', [DairyAnalysisRebatePendingExportController::class, 'print'])->name('rebate-pending.print');
                    Route::get('/rebate-pending-export-excel', [DairyAnalysisRebatePendingExportController::class, 'exportExcel'])->name('rebate-pending.export.excel');
                });
                
                Route::prefix('sales-purchase-analysis')->as('sales-purchase-analysis.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\SalesPurchaseAnalysisController::class, 'index'])->name('index');
                    Route::get('/dashboard', [\App\Http\Controllers\SalesPurchaseAnalysisController::class, 'dashboardData'])->name('dashboard');
                    Route::get('/export-excel', [\App\Http\Controllers\SalesPurchaseAnalysisExportController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/export-detailed-excel', [\App\Http\Controllers\SalesPurchaseAnalysisExportController::class, 'exportDetailedExcel'])->name('export.detailed.excel');
                });
            });
Route::prefix('moisture')->as('moisture.')->group(function () {
    Route::get('/', [MoistureController::class, 'index'])->name('index');
});

            Route::prefix('godown-module')->as('godown-module.')->group(function () {
                Route::get('/index', [GodownModuleController::class, 'index'])->name('index'); //listing
                Route::get('/product-in', [GodownModuleController::class, 'productIn'])->name('product-in'); //add
                Route::post('/store-product-in', [GodownModuleController::class, 'storeProductIn'])->name('store-product-in'); //store and update
                Route::put('/update-product-in/{godownModule?}', [GodownModuleController::class, 'updateProductIn'])->name('update-product-in'); //store and update

                Route::get('/product-out', [GodownModuleController::class, 'productOut'])->name('product-out');
                Route::post('/store-product-out', [GodownModuleController::class, 'storeProductOut'])->name('store-product-out'); //store and update
                Route::put('/update-product-out/{godownModule?}', [GodownModuleController::class, 'updateProductOut'])->name('update-product-out'); //store and update

                // -----------------------------------------------------------------------------------
                Route::get('/product-in-manual', [GodownModuleController::class, 'productInManual'])->name('product-in-manual');
                // Route::get('/product-out', [GodownModuleController::class, 'productOut'])->name('product-out');
                Route::get('/product-out-manual', [GodownModuleController::class, 'productOutManual'])->name('product-out-manual');
                Route::get('/get-godowns', [GodownModuleController::class, 'getGodowns'])->name('get-godowns');
                Route::get('/fetch-grn-details', [GodownModuleController::class, 'fetchGrnDetails'])->name('fetch-grn-details');
                Route::get('/fetch-challan-details', [GodownModuleController::class, 'fetchDeliveryChallanDetails'])->name('fetch-challan-details');
                Route::get('/check-duplicate', [GodownModuleController::class, 'checkDuplicateReference'])->name('check_duplicate_reference');
                Route::get('/check-duplicate-lr', [GodownModuleController::class, 'checkDuplicateLrNumber'])->name('check_duplicate_lr');
                Route::get('/check-duplicate-lr-no', [GodownModuleController::class, 'checkDuplicateLrNo'])->name('check_duplicate_lr_no');
                Route::get('/transporter-list', [GodownModuleController::class, 'transporterList'])->name('transporter-list');
                Route::post('/store', [GodownModuleController::class, 'store'])->name('godown-store');
                Route::delete('/{godown}', [GodownModuleController::class, 'destroy'])->name('destroy');
                Route::get('/print', [GodownModuleExportController::class, 'print'])->name('print');
                Route::get('/print-ticket', [GodownModuleExportController::class, 'printTicket'])->name('print-ticket');
                Route::get('/print-letter', [GodownModuleExportController::class, 'printLetter'])->name('print-letter');
                Route::get('/print-gatepass', [GodownModuleExportController::class, 'printGatepass'])->name('print-gatepass');
                Route::get('/print-grn', [GodownModuleExportController::class, 'printGRN'])->name('print-grn');
                Route::get('/export-excel', [GodownModuleExportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/print-transporter', [GodownModuleExportController::class, 'printTransporter'])->name('print-transporter.print');
                Route::get('/export-transporter-excel', [GodownModuleExportController::class, 'exportTransporterExcel'])->name('print-transporter.export.excel');
                Route::get('/product-in-self', [GodownModuleController::class, 'productInSelf'])->name('product-in-self');
                Route::put('/update-product-in-self/{godownModule?}', [GodownModuleController::class, 'updateProductInSelf'])->name('update-product-in-self'); // update for product-in-self
                Route::get('/product-out-self', [GodownModuleController::class, 'productOutSelf'])->name('product-out-self');
                Route::put('/update-product-out-self/{godownModule?}', [GodownModuleController::class, 'updateProductOutSelf'])->name('update-product-out-self'); // update for product-out-self

            });

            Route::prefix('payments')->as('payments.')->group(function () {
                Route::prefix('payable')->as('payable.')->group(function () {
                    Route::get('/', [PaymentPayableController::class, 'create'])->name('create');
                    Route::post('/', [PaymentPayableController::class, 'store'])->name('store');
                    Route::get('/print-pending-payment', [PaymentPayableController::class, 'pendingPayment'])->name('print-pending-payment');
                    Route::post('/hold', [PaymentPayableController::class, 'holdBill'])->name('hold');
                });

                Route::prefix('receivable')->as('receivable.')->group(function () {
                    Route::get('/', [PaymentReceivableController::class, 'create'])->name('create');
                    Route::post('/', [PaymentReceivableController::class, 'store'])->name('store');
                });

                Route::prefix('approved')->as('approved.')->group(function () {
                    Route::get('/', [PaymentApprovalController::class, 'index'])->name('index');
                    Route::post('/approve', [PaymentApprovalController::class, 'approve'])->name('approve');
                    Route::delete('/{id}', [PaymentApprovalController::class, 'destroy'])->name('destroy');
                });
                Route::prefix('hold')->as('hold.')->group(function () {
                    Route::get('/', [PaymentHoldController::class, 'index'])->name('index');
                    Route::post('/hold', [PaymentHoldController::class, 'hold'])->name('hold');
                });

                Route::prefix('bill-detail')->as('bill-detail.')->group(function () {
                    Route::get('/{id}', [PaymentBillDetailController::class, 'show'])->name('show');
                });
            });

            // Route::prefix('payment-voucher-register')->as('payment-voucher-register.')->group(function () {
            //     Route::get('/', [PaymentVoucherRegisterController::class, 'index'])->name('index');
            // });


            Route::prefix('gstr1-report')->as('gstr1-report.')->group(function () {
                Route::get('/', [Gstr1ReportController::class, 'index'])->name('index');
                Route::get('/summary', [Gstr1ReportController::class, 'summary'])->name('summary');
                Route::get('/detail', [Gstr1ReportController::class, 'detail'])->name('detail');
                Route::get('/export/excel', [Gstr1ReportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/export-detail/excel', [Gstr1ReportController::class, 'exportDetailExcel'])->name('export.detail.excel');
                Route::get('/export-offline/excel', [Gstr1ReportController::class, 'exportOfflineExcel'])->name('export.offline.excel');
            });

            Route::prefix('gstr2-report')->as('gstr2-report.')->group(function () {
                Route::get('/', [Gstr2ReportController::class, 'index'])->name('index');
                Route::get('/summary', [Gstr2ReportController::class, 'summary'])->name('summary');
                Route::get('/detail', [Gstr2ReportController::class, 'detail'])->name('detail');
                Route::get('/export/excel', [Gstr2ReportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/export-detail/excel', [Gstr2ReportController::class, 'exportDetailExcel'])->name('export.detail.excel');
            });

            Route::prefix('gstr3b-report')->as('gstr3b-report.')->group(function () {
                Route::get('/', [Gstr3bReportController::class, 'index'])->name('index');
                Route::get('/summary', [Gstr3bReportController::class, 'summary'])->name('summary');
                Route::get('/detail', [Gstr3bReportController::class, 'detail'])->name('detail');
                Route::get('/export/excel', [Gstr3bReportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/export-detail/excel', [Gstr3bReportController::class, 'exportDetailExcel'])->name('export.detail.excel');
            });

            Route::prefix('fas')->as('fas.')->group(function () {
                Route::prefix('tds-entries')->as('tds-entries.')->group(function () {
                    Route::get('/', [TdsEntryController::class, 'index'])->name('index');
                    Route::get('/list', [TdsEntryController::class, 'list'])->name('list');
                    Route::post('/check-threshold', [TdsEntryController::class, 'checkThreshold'])->name('check-threshold');
                    Route::get('/print', [TdsEntryExportController::class, 'print'])->name('print');
                    Route::get('/export-excel', [TdsEntryExportController::class, 'exportExcel'])->name('export.excel');
                });

                Route::prefix('audit-trails')->as('audit-trails.')->group(function () {
                    Route::get('/debug-db', function() {
                        return \App\Models\AuditTrail::all();
                    });
                    Route::get('/', [AuditTrailController::class, 'index'])->name('index');
                    Route::get('/list-summary', [AuditTrailController::class, 'listSummary'])->name('list-summary');
                    Route::get('/list', [AuditTrailController::class, 'list'])->name('list');
                    Route::get('/{id}', [AuditTrailController::class, 'show'])->name('show');
                });
            });

            Route::prefix('ledger-report')->as('ledger-report.')->group(function () {
                Route::get('/', [LedgerReportController::class, 'index'])->name('index');
                Route::get('/list', [LedgerReportController::class, 'list'])->name('list');
                Route::get('/print', [LedgerReportController::class, 'print'])->name('print');
                Route::get('/export-excel', [LedgerReportController::class, 'exportExcel'])->name('export.excel');
                Route::get('/voucher-modal/{id}', [LedgerModalController::class, 'voucherModalHtml'])->name('voucher-modal');
            });

            Route::prefix('daybook-report')->as('daybook-report.')->group(function () {
                Route::get('/', [DaybookReportController::class, 'index'])->name('index');
                Route::get('/list', [DaybookReportController::class, 'list'])->name('list');
                Route::get('/print', [DaybookReportExportController::class, 'print'])->name('print');
                Route::get('/export-excel', [DaybookReportExportController::class, 'exportExcel'])->name('export.excel');
            });

            Route::prefix('trial-balance')->as('trial-balance.')->group(function () {
                Route::get('/', [TrialBalanceController::class, 'index'])->name('index');
                Route::get('/opening-list', [TrialBalanceController::class, 'getOpeningTrialBalance'])->name('opening-list');
                Route::post('/select-group-row', [TrialBalanceController::class, 'selectGroupRow'])->name('select-group-row');
                Route::get('/account-month-wise-summary', [TrialBalanceController::class, 'getAccountMonthWiseSummary'])->name('account-month-wise-summary');
                Route::get('/account-month-wise-summary/print', [TrialBalanceController::class, 'printAccountMonthWise'])->name('account-month-wise.print');
                Route::get('/account-month-wise-summary/export-excel', [TrialBalanceController::class, 'exportExcelAccountMonthWise'])->name('account-month-wise.export.excel');
                Route::get('/account-ledger', [TrialBalanceController::class, 'getAccountLedger'])->name('account-ledger');
                Route::get('/account-ledger/print', [TrialBalanceController::class, 'printAccountLedger'])->name('account-ledger.print');
                Route::get('/account-ledger/export-excel', [TrialBalanceController::class, 'exportExcelAccountLedger'])->name('account-ledger.export.excel');
                Route::get('/group-wise-list', [TrialBalanceController::class, 'getGroupWiseTrialBalance'])->name('group-wise-list');
                Route::get('/group-wise-balance', [TrialBalanceController::class, 'getGroupWiseTrialBalanceBalance'])->name('group-wise-balance');
                Route::get('/group-wise-detail', [TrialBalanceController::class, 'getGroupWiseTrialBalanceDetail'])->name('group-wise-detail');
                Route::get('/print', [TrialBalanceController::class, 'print'])->name('print');
                Route::get('/opening-list/print', [TrialBalanceController::class, 'printOpeningList'])->name('opening-list.print');
                Route::get('/group-wise-list/print', [TrialBalanceController::class, 'printGroupWiseList'])->name('group-wise.print');
                Route::get('/group-wise-balance/print', [TrialBalanceController::class, 'printGroupWiseBalance'])->name('group-wise-balance.print');
                Route::get('/group-wise-detail/print', [TrialBalanceController::class, 'printGroupWiseDetail'])->name('group-wise-detail.print');
                Route::get('/export-excel', [TrialBalanceController::class, 'exportExcel'])->name('export.excel');
                Route::get('/opening-list/export-excel', [TrialBalanceController::class, 'exportExcelOpeningList'])->name('opening-list.export.excel');
                Route::get('/group-wise-list/export-excel', [TrialBalanceController::class, 'exportExcelGroupWiseList'])->name('group-wise.export.excel');
                Route::get('/group-wise-balance/export-excel', [TrialBalanceController::class, 'exportExcelGroupWiseBalance'])->name('group-wise-balance.export.excel');
                Route::get('/group-wise-detail/export-excel', [TrialBalanceController::class, 'exportExcelGroupWiseDetail'])->name('group-wise-detail.export.excel');
            });

            Route::prefix('profit-loss')->as('profit-loss.')->group(function () {
                Route::get('/',             [ProfitLossController::class, 'index'])->name('index');
                Route::get('/list',         [ProfitLossController::class, 'list'])->name('list');
                Route::get('/print',        [ProfitLossController::class, 'print'])->name('print');
                Route::get('/export-excel', [ProfitLossController::class, 'exportExcel'])->name('export.excel');
            });

            Route::prefix('payment-register')->as('payment-register.')->group(function () {
                Route::get('/', [PaymentRegisterController::class, 'index'])->name('index');
                Route::get('/print', [PaymentRegisterController::class, 'print'])->name('print');
            });

            Route::prefix('receipt-register')->as('receipt-register.')->group(function () {
                Route::get('/', [ReceiptRegisterController::class, 'index'])->name('index');
                Route::get('/print', [ReceiptRegisterController::class, 'print'])->name('print');
            });

            Route::prefix('payment-online-rtgs')->as('payment-online-rtgs.')->group(function () {
                Route::get('/', [PaymentOnlineRtgsController::class, 'index'])->name('index');
                Route::get('/pending-list', [PaymentOnlineRtgsController::class, 'fetchPending'])->name('fetch_pending');
                Route::post('/save-update-cheque-no', [PaymentOnlineRtgsController::class, 'saveUpdateChequeNo'])->name('save_update_cheque_no');
                Route::get('/cheque-print', [PaymentOnlineRtgsController::class, 'printCheque'])->name('cheque_print');
                Route::get('/register-print', [PaymentOnlineRtgsController::class, 'printRegister'])->name('register_print');
                Route::get('/fetch-by-cheque', [PaymentOnlineRtgsController::class, 'fetchByCheque'])->name('fetch_by_cheque');
                Route::get('/payment-advice-print', [PaymentOnlineRtgsController::class, 'printPaymentAdvice'])->name('payment_advice_print');
                Route::get('/rtgs-print', [PaymentOnlineRtgsController::class, 'printRtgs'])->name('rtgs.print');
                Route::delete('/delete-selected', [PaymentOnlineRtgsController::class, 'deleteSelected'])->name('delete_selected');
            });

            Route::prefix('manual-cheques')->as('manual-cheques.')->group(function () {
                Route::get('/', [ManualChequeController::class, 'index'])->name('index');
                Route::get('/create', [ManualChequeController::class, 'create'])->name('create');
                Route::post('/store', [ManualChequeController::class, 'store'])->name('store');
                Route::get('/accounts', [ManualChequeController::class, 'getAccounts'])->name('accounts');
                Route::get('/get-payee-total', [ManualChequeController::class, 'getPayeeTotal'])->name('get_payee_total');
                Route::get('/{id}/print', [ManualChequeController::class, 'printCheque'])->name('print');
                Route::delete('/{id}', [ManualChequeController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('mail')->as('mail.')->group(function () {
                Route::post('/rtgs-email-preview',  [MailController::class, 'previewRtgsToBankEmail'])->name('rtgs_email_preview');
                Route::post('/rtgs-send-to-bank',   [MailController::class, 'sendRtgsToBankEmail'])->name('rtgs_send_to_bank');
                Route::post('/payment-advice-email-preview',   [MailController::class, 'previewPaymentAdviceEmail'])->name('payment_advice_email_preview');
                Route::post('/payment-advice-email-send',      [MailController::class, 'sendPaymentAdviceEmail'])->name('payment_advice_email_send');
                Route::post('/payment-advice-email-bulk-send', [MailController::class, 'sendPaymentAdviceBulkEmail'])->name('payment_advice_email_bulk_send');
                Route::post('/preview-dairy-analysis-email', [MailController::class, 'previewDairyAnalysisEmail'])->name('preview_dairy_analysis_email');
                Route::post('/send-dairy-analysis-email', [MailController::class, 'sendDairyAnalysisEmail'])->name('send_dairy_analysis_email');
                Route::post('/preview-urgent-purchase-order-email', [MailController::class, 'previewUrgentPurchaseOrderEmail'])->name('preview_urgent_purchase_order_email');
                Route::post('/send-urgent-purchase-order-email', [MailController::class, 'sendUrgentPurchaseOrderEmail'])->name('send_urgent_purchase_order_email');
                Route::post('/godown-analysis-email-preview', [MailController::class, 'previewGodownAnalysisEmail'])->name('godown-analysis-email-preview');
                Route::post('/godown-analysis-email-send', [MailController::class, 'sendGodownAnalysisEmail'])->name('godown-analysis-email-send');
            });

            Route::prefix('account-balance')->as('account-balance.')->group(function () {
                Route::get('/', [AccountBalanceController::class, 'index'])->name('index');
                Route::get('/opening', [AccountBalanceController::class, 'openingBalance'])->name('opening');
                Route::get('/closing', [AccountBalanceController::class, 'closingBalance'])->name('closing');
                Route::get('/calculate', [AccountBalanceController::class, 'calculate'])->name('calculate');
            });


            Route::prefix('penalty')->as('penalty.')->group(function () {
                Route::get('/', [PenaltyController::class, 'index'])->name('index');
                Route::get('/create', [PenaltyController::class, 'create'])->name('create');
                Route::post('/', [PenaltyController::class, 'store'])->name('store');
                Route::get('/{penalty}', [PenaltyController::class, 'show'])->name('show');
                Route::get('/{penalty}/edit', [PenaltyController::class, 'edit'])->name('edit');
                Route::put('/{penalty}', [PenaltyController::class, 'update'])->name('update');
                Route::delete('/{penalty}', [PenaltyController::class, 'destroy'])->name('destroy');
            });

            /*
            |------------------------------------------------------------------
            | GST Portal — E-Invoice & E-Way Bill management listing
            |------------------------------------------------------------------
            */
            Route::prefix('gst-portal')->as('gst-portal.')->group(function () {
                Route::get('/', [GstPortalController::class, 'index'])->name('index');
                Route::get('/e-invoice-list', [GstPortalController::class, 'eInvoiceList'])->name('e-invoice-list');
                Route::get('/e-way-bill-list', [GstPortalController::class, 'eWayBillList'])->name('e-way-bill-list');

                // Unified E-WayBill / E-Invoice generate screen
                Route::get('/generate', [GstPortalController::class, 'generate'])->name('generate');
                Route::get('/sales-bills-json', [GstPortalController::class, 'salesBillsJson'])->name('sales-bills-json');
                Route::post('/bulk-generate', [GstPortalController::class, 'bulkGenerate'])->name('bulk-generate');
                Route::post('/refetch-irn', [GstPortalController::class, 'refetchIrn'])->name('refetch-irn');
                Route::get('/print-ewb/{ewbNo}', [GstPortalController::class, 'printEwb'])->name('print-ewb');
                Route::get('/error-logs', [GstPortalController::class, 'errorLogs'])->name('error-logs');
                Route::get('/error-logs-json', [GstPortalController::class, 'errorLogsJson'])->name('error-logs-json');
            });

            Route::prefix('setup/gst-credentials')->as('gst-credentials.')->group(function () {
                Route::get('/', [GstCredentialController::class, 'index'])->name('index');
                Route::put('/', [GstCredentialController::class, 'update'])->name('update');
            });

            Route::prefix('setup/mail-config')->as('mail-config.')->group(function () {
                Route::get('/', [MailConfigController::class, 'index'])->name('index');
                Route::put('/', [MailConfigController::class, 'update'])->name('update');
            });

            Route::prefix('setup/bank-mail-config')->as('bank-mail-config.')->group(function () {
                Route::get('/', [BankMailConfigController::class, 'index'])->name('index');
                Route::put('/', [BankMailConfigController::class, 'update'])->name('update');
            });

            /*
            |------------------------------------------------------------------
            | E-Invoice — Whitebook GSP API
            | All routes require the e_invoice.manage permission.
            |------------------------------------------------------------------
            */
            Route::prefix('e-invoice')->as('e-invoice.')->group(function () {

                // Authentication
                Route::post('/authenticate',           [EInvoiceController::class, 'authenticate'])->name('authenticate');

                // GSTN Master Data
                Route::get('/gstn-details/{gstin}',   [EInvoiceController::class, 'getGstnDetails'])->name('gstn-details');
                Route::get('/sync-gstin/{gstin}',     [EInvoiceController::class, 'syncGstinFromCp'])->name('sync-gstin');

                // IRN / E-Invoice Operations
                Route::post('/generate-irn',           [EInvoiceController::class, 'generateIrn'])->name('generate-irn');
                Route::get('/details/{irn}',           [EInvoiceController::class, 'getEInvoiceDetails'])->name('details');
                Route::get('/irn-by-doc-details',      [EInvoiceController::class, 'getIrnByDocDetails'])->name('irn-by-doc-details');
                Route::post('/cancel-irn',             [EInvoiceController::class, 'cancelIrn'])->name('cancel-irn');
                Route::get('/rejected-irns',           [EInvoiceController::class, 'getRejectedIrns'])->name('rejected-irns');

                // E-Way Bill from IRN
                Route::post('/generate-ewaybill',      [EInvoiceController::class, 'generateEwayBillFromIrn'])->name('generate-ewaybill');
                Route::get('/ewaybill-details/{irn}',  [EInvoiceController::class, 'getEwayBillDetailsByIrn'])->name('ewaybill-details');

                // B2C QR Code
                Route::get('/b2c-qr-code',             [EInvoiceController::class, 'getB2cQrCodeDetails'])->name('b2c-qr-code');
            });

            /*
            |------------------------------------------------------------------
            | E-Way Bill — Whitebook GSP API
            | All routes require the eway_bill.manage permission.
            |------------------------------------------------------------------
            */
            Route::prefix('eway-bill')->as('eway-bill.')->group(function () {

                // Authentication
                Route::post('/authenticate', [EwayBillController::class, 'authenticate'])->name('authenticate');

                // E-Way Bill Operations
                Route::post('/generate',                  [EwayBillController::class, 'generate'])->name('generate');
                Route::post('/update-part-b',             [EwayBillController::class, 'updatePartB'])->name('update-part-b');
                Route::post('/generate-consolidated',     [EwayBillController::class, 'generateConsolidated'])->name('generate-consolidated');
                Route::post('/cancel',                    [EwayBillController::class, 'cancel'])->name('cancel');
                Route::post('/reject',                    [EwayBillController::class, 'reject'])->name('reject');
                Route::post('/update-transporter',        [EwayBillController::class, 'updateTransporter'])->name('update-transporter');
                Route::post('/extend-validity',           [EwayBillController::class, 'extendValidity'])->name('extend-validity');
                Route::post('/regenerate-consolidated',   [EwayBillController::class, 'regenerateConsolidated'])->name('regenerate-consolidated');

                // Multi-Vehicle Movement
                Route::post('/multi-vehicle/initiate',    [EwayBillController::class, 'initiateMultiVehicle'])->name('multi-vehicle.initiate');
                Route::post('/multi-vehicle/add',         [EwayBillController::class, 'addMultiVehicles'])->name('multi-vehicle.add');
                Route::post('/multi-vehicle/change',      [EwayBillController::class, 'changeMultiVehicles'])->name('multi-vehicle.change');

                // Retrieval / Query
                Route::get('/details/{ewbNo}',            [EwayBillController::class, 'details'])->name('details');
                Route::get('/by-date',                    [EwayBillController::class, 'getByDate'])->name('by-date');
                Route::get('/rejected-by-others',         [EwayBillController::class, 'getRejectedByOthers'])->name('rejected-by-others');
                Route::get('/by-parties',                 [EwayBillController::class, 'getByParties'])->name('by-parties');
                Route::get('/consolidated',               [EwayBillController::class, 'getConsolidated'])->name('consolidated');
                Route::get('/by-consigner',               [EwayBillController::class, 'getByConsigner'])->name('by-consigner');

                // Transporter-specific queries
                Route::get('/transporter/by-date',                [EwayBillController::class, 'getForTransporterByDate'])->name('transporter.by-date');
                Route::get('/transporter/by-state',               [EwayBillController::class, 'getForTransporterByState'])->name('transporter.by-state');
                Route::get('/transporter/by-gstin',               [EwayBillController::class, 'getForTransporterByGstin'])->name('transporter.by-gstin');
                Route::get('/transporter/report-by-assigned-date', [EwayBillController::class, 'getTransporterReportByAssignedDate'])->name('transporter.report-by-assigned-date');

                // Master Data & Lookup
                Route::get('/master/error-list',          [EwayBillController::class, 'errorList'])->name('master.error-list');
                Route::get('/master/gstin/{gstin}',       [EwayBillController::class, 'gstinDetails'])->name('master.gstin');
                Route::get('/master/transin/{transId}',   [EwayBillController::class, 'transinDetails'])->name('master.transin');
                Route::get('/master/hsn/{hsnCode}',       [EwayBillController::class, 'hsnDetails'])->name('master.hsn');
            });

            Route::prefix('cheque')->as('cheque.')->group(function () {
                Route::get('/', [ChequeController::class, 'index'])->name('index');
                Route::get('/create', [ChequeController::class, 'create'])->name('create');
                Route::post('/store', [ChequeController::class, 'store'])->name('store');
                Route::get('/edit/{cheque}', [ChequeController::class, 'edit'])->name('edit');
                Route::post('/update/{cheque}', [ChequeController::class, 'update'])->name('update');
                Route::delete('/delete/{cheque}', [ChequeController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('godown-analysis')->as('godown-analysis.')->group(function () {
                Route::get('/', [GodownAnalysisController::class, 'index'])->name('index');
                Route::get('/create', [GodownAnalysisController::class, 'create'])->name('create');
                Route::post('/store', [GodownAnalysisController::class, 'store'])->name('store');
                Route::get('/edit/{godown_analysis}', [GodownAnalysisController::class, 'edit'])->name('edit');
                Route::post('/update/{godown_analysis}', [GodownAnalysisController::class, 'update'])->name('update');
                Route::delete('/delete/{godown_analysis}', [GodownAnalysisController::class, 'destroy'])->name('destroy');
                Route::get('/getGrnData/{grnSerial}', [GodownAnalysisController::class, 'getGrnData'])->name('getGrnData');
                Route::get('/pds-print/{godown_analysis}', [GodownAnalysisController::class, 'printAnalysisReport'])->name('pds-print');

                //Print And Export
                Route::get('/print', [GodownAnalysisExportController::class, 'print'])->name('print');
                Route::get('/export-excel', [GodownAnalysisExportController::class, 'exportExcel'])->name('export.excel');
            });

            Route::prefix('bag-challan-labour')->as('bag-challan-labour.')->group(function () {
                Route::get('/', [BagChallanLabourController::class, 'index'])->name('index');
                Route::get('/create', [BagChallanLabourController::class, 'create'])->name('create');
                Route::post('/store', [BagChallanLabourController::class, 'store'])->name('store');
                Route::get('/edit/{bag_challan_labour}', [BagChallanLabourController::class, 'edit'])->name('edit');
                Route::post('/update/{bag_challan_labour}', [BagChallanLabourController::class, 'update'])->name('update');
                Route::delete('/delete/{bag_challan_labour}', [BagChallanLabourController::class, 'destroy'])->name('destroy');
                Route::get('/print', [BagChallanLabourController::class, 'print'])->name('print');
            });

            Route::prefix('payment-advice')->as('payment-advice.')->group(function () {
                Route::get('/', [PaymentAdviceController::class, 'index'])->name('index');
                Route::get('/pending-list', [PaymentAdviceController::class, 'fetchPending'])->name('fetch_pending');
                Route::get('/cheque-print', [PaymentAdviceController::class, 'printCheque'])->name('cheque_print');
                Route::get('/register-print', [PaymentAdviceController::class, 'printRegister'])->name('register_print');
                Route::get('/fetch-by-cheque', [PaymentAdviceController::class, 'fetchByCheque'])->name('fetch_by_cheque');
                Route::get('/payment-advice-print', [PaymentAdviceController::class, 'printPaymentAdvice'])->name('payment_advice_print');
                Route::get('/rtgs-print', [PaymentAdviceController::class, 'printRtgs'])->name('rtgs.print');
            });

            Route::prefix('mail-logs')->as('mail-logs.')->group(function () {
                Route::get('/', [MailLogController::class, 'index'])->name('index');
                Route::get('/data', [MailLogController::class, 'getData'])->name('data');
            });
            
            Route::prefix('onac-payment')->as('onac-payment.')->group(function () {
                Route::get('/', [ONACPaymentPayableController::class, 'create'])->name('create');
                Route::get('/get-advance-list', [ONACPaymentPayableController::class, 'getAdvanceList'])->name('get-advance-list');
                Route::get('/payables', [ONACPaymentPayableController::class, 'payables'])->name('payables');
            });

        }); // end check.company.year

    }); // end screen_lock

}); // end auth



Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

require __DIR__ . '/auth.php';

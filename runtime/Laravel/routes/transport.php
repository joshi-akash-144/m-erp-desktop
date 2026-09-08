<?php

use App\Http\Controllers\ContractorController;
use App\Http\Controllers\ContractorExportController;
use App\Http\Controllers\DairyFileImportController;
use App\Http\Controllers\DieselController;
use App\Http\Controllers\DriverExpenseController;
use App\Http\Controllers\FreightController;
use App\Http\Controllers\FreightExportController;
use App\Http\Controllers\FreightInvoice2Controller;
use App\Http\Controllers\FreightInvoiceExportController;
use App\Http\Controllers\MultiExpenseVoucherController;
use App\Http\Controllers\TransportReferencePaymentController;
use App\Http\Controllers\VehicleExpenditureController;
use App\Http\Controllers\VehicleIncomeReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FreightInvoiceController;
use App\Http\Controllers\ExpenseRegisterController;
use App\Http\Controllers\SalaryModuleController;
use App\Http\Controllers\FreightInvoiceItemOrderController;

Route::prefix('freight')->as('freight.')->group(function () {
    Route::get('/', [FreightController::class, 'index'])->name('index');
    Route::get('/create', [FreightController::class, 'create'])->name('create');
    Route::post('/store', [FreightController::class, 'store'])->name('store');
    Route::get('/edit/{freight}', [FreightController::class, 'edit'])->name('edit');
    Route::post('/update/{freight}', [FreightController::class, 'update'])->name('update');
    Route::delete('/delete/{freight}', [FreightController::class, 'destroy'])->name('destroy');
    Route::get('/getGrnData/{grnSerial}', [FreightController::class, 'getGrnData'])->name('getGrnData');
    Route::get('/getLrNumberData/{lrNumber}', [FreightController::class, 'getLrNumberData'])->name('getLrNumberData');
    Route::get('/getNextBillNumber/{billedToCompanyId}', [FreightController::class, 'getNextBillNumber'])->name('getNextBillNumber');
    Route::get('/checkDuplicateLRNumber', [FreightController::class, 'checkDuplicateLRNumber'])->name('checkDuplicateLRNumber');

    //Print And Export
    Route::get('/freight-print/{id}', [FreightController::class, 'freightPrint'])->name('freight-print');
    Route::get('/print', [FreightExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [FreightExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('freight-invoice')->as('freight-invoice.')->group(function () {
    Route::get('/', [FreightInvoiceController::class, 'index'])->name('index');
    Route::get('/create', [FreightInvoiceController::class, 'create'])->name('create');
    Route::post('/store', [FreightInvoiceController::class, 'store'])->name('store');
    Route::get('/edit/{freight_invoice}', [FreightInvoiceController::class, 'edit'])->name('edit');
    Route::post('/update/{freight_invoice}', [FreightInvoiceController::class, 'update'])->name('update');
    Route::delete('/delete/{freight_invoice}', [FreightInvoiceController::class, 'destroy'])->name('destroy');
    Route::get('/get-dairy-file-data', [FreightInvoiceController::class, 'getDairyFileData'])->name('get-dairy-file-data');
    Route::post('/get-import-items-data', [FreightInvoiceController::class, 'getImportItemsData'])->name('get-import-items-data');
    Route::get('/print/{id}', [FreightInvoiceController::class, 'print'])->name('print-invoice');
    Route::get('/print-zonewise/{id}', [FreightInvoiceController::class, 'printZonewise'])->name('print-zonewise');
    Route::get('/export-zonewise/{id}', [FreightInvoiceController::class, 'exportZonewise'])->name('export-zonewise');

    Route::get('/print', [FreightInvoiceExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [FreightInvoiceExportController::class, 'exportExcel'])->name('export.excel');

    Route::post('/item-order', [FreightInvoiceItemOrderController::class, 'saveItemOrderConfig'])->name('item-order.save');
    Route::delete('/item-order/reset', [FreightInvoiceItemOrderController::class, 'resetItemOrderConfig'])->name('item-order.reset');
});


Route::prefix('dairy-file-import')->as('dairy-file-import.')->group(function () {
    Route::get('/', [DairyFileImportController::class, 'index'])->name('index');
});

Route::prefix('dairy-file-import')->as('dairy-file-import.')->group(function () {
    Route::get('/',               [DairyFileImportController::class, 'index'])->name('index');
    Route::post('/',              [DairyFileImportController::class, 'store'])->name('store');
    Route::get('/download-sample', [DairyFileImportController::class, 'downloadSample'])->name('download-sample');
    Route::get('/dairy-file-register',              [DairyFileImportController::class, 'dairyFileRegister'])->name('dairy-file.register');
    Route::get('/dairy-file-register/list',         [DairyFileImportController::class, 'dairyFileRegisterList'])->name('dairy-file.register.list');
    Route::get('/dairy-file-register/{id}/items',   [DairyFileImportController::class, 'dairyFileImportItems'])->name('dairy-file.import.items');
    Route::delete('/dairy-file-register/item/{id}', [DairyFileImportController::class, 'dairyFileItemDestroy'])->name('dairy-file.item.destroy');
    Route::delete('/dairy-file-register/{id}',      [DairyFileImportController::class, 'dairyFileImportDestroy'])->name('dairy-file.import.destroy');
    Route::delete('/dairy-file-register-bulk',      [DairyFileImportController::class, 'dairyFileBulkDelete'])->name('dairy-file.register.bulk-delete');
    Route::get('/day-to-day-register',          [DairyFileImportController::class, 'dayToDayRegister'])->name('day-to-day.register');
    Route::get('/day-to-day-register/list',     [DairyFileImportController::class, 'dayToDayRegisterList'])->name('day-to-day.register.list');
    Route::delete('/day-to-day-register/{id}',  [DairyFileImportController::class, 'dayToDayDestroy'])->name('day-to-day.register.destroy');
    Route::delete('/day-to-day-register-bulk',  [DairyFileImportController::class, 'dayToDayBulkDelete'])->name('day-to-day.register.bulk-delete');
});

Route::prefix('driver-expense')->as('driver-expense.')->group(function () {
    Route::get('/',                      [DriverExpenseController::class, 'index'])->name('index');
    Route::get('/list',                  [DriverExpenseController::class, 'list'])->name('list');
    Route::get('/create',                [DriverExpenseController::class, 'create'])->name('create');
    Route::post('/store',                [DriverExpenseController::class, 'store'])->name('store');
    Route::get('/get-dairy-import-data', [DriverExpenseController::class, 'getDairyImportData'])->name('get-dairy-import-data');
    Route::get('/register-print',        [DriverExpenseController::class, 'registerPrint'])->name('register-print');
    Route::get('/export-summary',        [DriverExpenseController::class, 'exportSummary'])->name('export-summary');
    Route::get('/{driverExpense}/print', [DriverExpenseController::class, 'printVoucher'])->name('print');
    Route::get('/{driverExpense}/edit',  [DriverExpenseController::class, 'edit'])->name('edit');
    Route::get('/{driverExpense}/data',  [DriverExpenseController::class, 'getData'])->name('data');
    Route::put('/{driverExpense}',       [DriverExpenseController::class, 'update'])->name('update');
});

Route::prefix('vehicle-expenditure')->as('vehicle-expenditure.')->group(function () {
    Route::get('/',           [VehicleExpenditureController::class, 'index'])->name('index');
    Route::get('/report-data', [VehicleExpenditureController::class, 'reportData'])->name('report-data');
    Route::get('/export-excel', [VehicleExpenditureController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('transport-reference-payment')->as('transport-reference-payment.')->group(function () {
    Route::get('/',                        [TransportReferencePaymentController::class, 'index'])->name('index');
    Route::get('/list',                    [TransportReferencePaymentController::class, 'list'])->name('list');
    Route::get('/{id}/detail',             [TransportReferencePaymentController::class, 'detail'])->name('detail');
    Route::post('/store',                  [TransportReferencePaymentController::class, 'store'])->name('store');
    Route::post('/hold',                   [TransportReferencePaymentController::class, 'hold'])->name('hold');
    Route::delete('/delete/{id}',          [TransportReferencePaymentController::class, 'destroy'])->name('destroy');
    Route::get('/register',                [TransportReferencePaymentController::class, 'register'])->name('register');
    Route::get('/register/list',           [TransportReferencePaymentController::class, 'registerList'])->name('register.list');
    Route::get('/register/{id}/detail',    [TransportReferencePaymentController::class, 'registerDetail'])->name('register.detail');
    Route::get('/register/{id}/print',     [TransportReferencePaymentController::class, 'registerPrint'])->name('register.print');
    Route::get('/voucher/{id}/print',      [TransportReferencePaymentController::class, 'voucherPrint'])->name('voucher.print');
});

Route::prefix('multi-expense')->as('multi-expense.')->group(function () {
    Route::get('/',                        [MultiExpenseVoucherController::class, 'index'])->name('index');
    Route::get('/list',                    [MultiExpenseVoucherController::class, 'list'])->name('list');
    Route::get('/pending-challans',        [MultiExpenseVoucherController::class, 'getPendingChallans'])->name('pending-challans');
    Route::get('/create',                  [MultiExpenseVoucherController::class, 'create'])->name('create');
    Route::post('/store',                  [MultiExpenseVoucherController::class, 'store'])->name('store');
    Route::get('/register-print',          [MultiExpenseVoucherController::class, 'registerPrint'])->name('register-print');
    Route::get('/{multiExpenseVoucher}/print', [MultiExpenseVoucherController::class, 'printVoucher'])->name('print');
    Route::get('/{multiExpenseVoucher}/edit',  [MultiExpenseVoucherController::class, 'edit'])->name('edit');
    Route::get('/{multiExpenseVoucher}/data',  [MultiExpenseVoucherController::class, 'getData'])->name('data');
    Route::put('/{multiExpenseVoucher}',       [MultiExpenseVoucherController::class, 'update'])->name('update');
});

Route::prefix('expense-register')->as('expense-register.')->group(function () {
    Route::get('/',         [ExpenseRegisterController::class, 'index'])->name('index');
    Route::get('/list',     [ExpenseRegisterController::class, 'list'])->name('list');
    Route::get('/print',        [ExpenseRegisterController::class, 'print'])->name('print');
    Route::get('/export-excel', [ExpenseRegisterController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('salary-module')->as('salary-module.')->group(function () {
    Route::get('/',                        [SalaryModuleController::class, 'index'])->name('index');
    Route::get('/list',                    [SalaryModuleController::class, 'list'])->name('list');
    Route::get('/create',                  [SalaryModuleController::class, 'create'])->name('create');
    Route::post('/store',                  [SalaryModuleController::class, 'store'])->name('store');
    Route::get('/register-print',          [SalaryModuleController::class, 'registerPrint'])->name('register-print');
    Route::get('/{multiExpenseVoucher}/print', [SalaryModuleController::class, 'printVoucher'])->name('print');
    Route::get('/{multiExpenseVoucher}/edit',  [SalaryModuleController::class, 'edit'])->name('edit');
    Route::get('/{multiExpenseVoucher}/data',  [SalaryModuleController::class, 'getData'])->name('data');
    Route::put('/{multiExpenseVoucher}',       [SalaryModuleController::class, 'update'])->name('update');
});

Route::prefix('diesel')->as('diesel.')->group(function () {
    Route::get('/',          [DieselController::class, 'index'])->name('index');
    Route::get('/list',      [DieselController::class, 'list'])->name('list');
    Route::get('/create',    [DieselController::class, 'create'])->name('create');
    Route::post('/store',    [DieselController::class, 'store'])->name('store');
    Route::get('/get-vehicle-latest-data', [DieselController::class, 'getVehicleLatestData'])->name('get-vehicle-latest-data');
    Route::get('/register-print', [DieselController::class, 'registerPrint'])->name('register-print');
    Route::get('/{diesel}/print', [DieselController::class, 'printVoucher'])->name('print');
    Route::get('/{id}',      [DieselController::class, 'show'])->name('show');
    Route::get('/edit/{id}', [DieselController::class, 'edit'])->name('edit');
    Route::put('/{id}',      [DieselController::class, 'update'])->name('update');
    Route::delete('/{id}',   [DieselController::class, 'destroy'])->name('destroy');
});

Route::prefix('contractors')->as('contractors.')->group(function () {
    Route::get('/', [ContractorController::class, 'index'])->name('index');
    Route::get('/create', [ContractorController::class, 'create'])->name('create');
    Route::post('/', [ContractorController::class, 'store'])->name('store');    
    Route::get('/{contract}/show', [ContractorController::class, 'show'])->name('show');
    Route::get('/{contract}/edit', [ContractorController::class, 'edit'])->name('edit');
    Route::put('/{contract}', [ContractorController::class, 'update'])->name('update');
    Route::delete('/{contract}', [ContractorController::class, 'destroy'])->name('destroy');

    // Print and Export
    Route::get('/print', [ContractorExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [ContractorExportController::class, 'exportExcel'])->name('export.excel');    
});

Route::prefix('vehicle-income')->as('vehicle-income.')->group(function () {
    Route::get('/',           [VehicleIncomeReportController::class, 'index'])->name('index');
    Route::get('/report-data', [VehicleIncomeReportController::class, 'reportData'])->name('report-data');
    Route::get('/print', [VehicleIncomeReportController::class, 'print'])->name('print');
    Route::get('/export-excel', [VehicleIncomeReportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('freight-invoice2')->as('freight-invoice2.')->group(function () {
    Route::get('/',          [FreightInvoice2Controller::class, 'index'])->name('index');
    Route::get('/create',    [FreightInvoice2Controller::class, 'create'])->name('create');
    Route::post('/store',    [FreightInvoice2Controller::class, 'store'])->name('store');
    Route::get('/{id}/edit', [FreightInvoice2Controller::class, 'edit'])->name('edit');
    Route::put('/{id}',      [FreightInvoice2Controller::class, 'update'])->name('update');
    Route::get('/print',     [FreightInvoice2Controller::class, 'print'])->name('print');
    Route::get('/print-invoice/{id}', [FreightInvoice2Controller::class, 'printInvoice'])->name('print-invoice');
    Route::get('/export-excel', [FreightInvoice2Controller::class, 'exportExcel'])->name('export.excel');
});

<?php

use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\PayeeCategoryController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountExportController;
use App\Http\Controllers\SaleTypeController;
use App\Http\Controllers\ConditionController;
use App\Http\Controllers\BillSundryController;
use App\Http\Controllers\ItemExportController;
use App\Http\Controllers\UnitExportController;
use App\Http\Controllers\VehicleOwnerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TaxCategoryController;
use App\Http\Controllers\AccountGroupController;
use App\Http\Controllers\PurchaseTypeController;
use App\Http\Controllers\SaleTypeExportController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\ConditionExportController;
use App\Http\Controllers\BillSundryExportController;
use App\Http\Controllers\BankConfigurationController;
use App\Http\Controllers\TaxCategoryExportController;
use App\Http\Controllers\AccountGroupExportController;
use App\Http\Controllers\BrokerController;
use App\Http\Controllers\ItemGroupController;
use App\Http\Controllers\PurchaseTypeExportController;
use App\Http\Controllers\BrokerExportController;
use App\Http\Controllers\BagsRateController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\ElementExportController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DestinationExportController;

use App\Http\Controllers\DairyParameterController;
use App\Http\Controllers\DairyParameterExportController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GodownController;
use App\Http\Controllers\GodownExportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TransporterExportController;
use App\Http\Controllers\PayeeCategoryExportController;
use App\Http\Controllers\TdsCategoryController;
use App\Http\Controllers\TdsCategoryExportController;
use App\Http\Controllers\TransportPartyController;
use App\Http\Controllers\WeightLocationController;
use App\Http\Controllers\FetchWeightLocationController;
use App\Http\Controllers\GodownUnitLocationController;
use App\Models\Godown;

Route::prefix('tax-categories')->as('tax-categories.')->group(function () {
    Route::get('/', [TaxCategoryController::class, 'index'])->name('index');
    Route::get('/create', [TaxCategoryController::class, 'create'])->name('create');
    Route::post('/', [TaxCategoryController::class, 'store'])->name('store');
    Route::get('/{taxCategory}/edit', [TaxCategoryController::class, 'edit'])->name('edit');
    Route::put('/{taxCategory}', [TaxCategoryController::class, 'update'])->name('update');
    Route::delete('/{taxCategory}', [TaxCategoryController::class, 'destroy'])->name('destroy');
    Route::get('/{taxCategory}/show', [TaxCategoryController::class, 'show'])->name('show');
    Route::post('/{taxCategory}/restore', [TaxCategoryController::class, 'restore'])->name('restore');
    // Route::get('/list', [TaxCategoryController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [TaxCategoryExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [TaxCategoryExportController::class, 'exportExcel'])->name('export.excel');
});
Route::prefix('bank-configurations')->as('bank-configurations.')->group(function () {
    Route::get('/', [BankConfigurationController::class, 'index'])->name('index');
    Route::get('/create', [BankConfigurationController::class, 'create'])->name('create');
    Route::post('/', [BankConfigurationController::class, 'store'])->name('store');
    Route::get('/{bankConfiguration}/edit', [BankConfigurationController::class, 'edit'])->name('edit');
    Route::put('/{bankConfiguration}', [BankConfigurationController::class, 'update'])->name('update');
    Route::delete('/{bankConfiguration}', [BankConfigurationController::class, 'destroy'])->name('destroy');
    Route::get('/{bankConfiguration}/show', [BankConfigurationController::class, 'show'])->name('show');
    Route::post('/{bankConfiguration}/restore', [BankConfigurationController::class, 'restore'])->name('restore');
    // Route::get('/list', [BankConfigurationController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [BankConfigurationController::class, 'print'])->name('print');
    Route::get('/export-excel', [BankConfigurationController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('accounts')->as('accounts.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/create', [AccountController::class, 'create'])->name('create');
    Route::post('/', [AccountController::class, 'store'])->name('store');
    Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
    Route::put('/{account}', [AccountController::class, 'update'])->name('update');
    Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
    Route::get('/{account}/show', [AccountController::class, 'show'])->name('show');
    Route::post('/{account}/restore', [AccountController::class, 'restore'])->name('restore');
    // Route::get('/list', [AccountController::class, 'list'])->name('list');

    //  Print and Export
    Route::get('/print', [AccountExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [AccountExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('account-groups')->as('account-groups.')->group(function () {
    Route::get('/', [AccountGroupController::class, 'index'])->name('index');
    Route::get('/create', [AccountGroupController::class, 'create'])->name('create');
    Route::post('/', [AccountGroupController::class, 'store'])->name('store');
    Route::get('/{accountGroup}/edit', [AccountGroupController::class, 'edit'])->name('edit');
    Route::put('/{accountGroup}', [AccountGroupController::class, 'update'])->name('update');
    Route::delete('/{accountGroup}', [AccountGroupController::class, 'destroy'])->name('destroy');
    Route::get('/{accountGroup}/show', [AccountGroupController::class, 'show'])->name('show');
    // Route::post('/{accountGroup}/restore', [AccountGroupController::class, 'restore'])->name('restore');
    // Route::get('/list', [AccountGroupController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [AccountGroupExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [AccountGroupExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('item-groups')->as('item-groups.')->group(function () {
    Route::get('/', [ItemGroupController::class, 'index'])->name('index');
    Route::get('/create', [ItemGroupController::class, 'create'])->name('create');
    Route::post('/', [ItemGroupController::class, 'store'])->name('store');
    // Route::get('/list', [ItemGroupController::class, 'list'])->name('list');
    Route::get('/{itemGroup}/edit', [ItemGroupController::class, 'edit'])->name('edit');
    Route::put('/{itemGroup}', [ItemGroupController::class, 'update'])->name('update');
    Route::delete('/{itemGroup}', [ItemGroupController::class, 'destroy'])->name('destroy');
    Route::get('/{itemGroup}/show', [ItemGroupController::class, 'show'])->name('show');
    Route::post('/{itemGroup}/restore', [ItemGroupController::class, 'restore'])->name('restore');

    // Print and Export
    // Route::get('/print', [ItemGroupExportController::class, 'print'])->name('print');
    // Route::get('/export-excel', [ItemGroupExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('units')->as('units.')->group(function () {
    Route::get('/', [UnitController::class, 'index'])->name('index');
    Route::get('/create', [UnitController::class, 'create'])->name('create');
    Route::post('/', [UnitController::class, 'store'])->name('store');
    Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
    Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
    Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
    Route::get('/{unit}/show', [UnitController::class, 'show'])->name('show');
    Route::post('/{unit}/restore', [UnitController::class, 'restore'])->name('restore');
    // Route::get('/list', [UnitController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [UnitExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [UnitExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('unit-conversions')->as('unit-conversions.')->group(function () {
    Route::get('/', [UnitConversionController::class, 'index'])->name('index');
    Route::get('/create', [UnitConversionController::class, 'create'])->name('create');
    Route::post('/', [UnitConversionController::class, 'store'])->name('store');
    Route::get('/{unitConversion}/edit', [UnitConversionController::class, 'edit'])->name('edit');
    Route::put('/{unitConversion}', [UnitConversionController::class, 'update'])->name('update');
    Route::delete('/{unitConversion}', [UnitConversionController::class, 'destroy'])->name('destroy');
    Route::get('/{unitConversion}/show', [UnitConversionController::class, 'show'])->name('show');
    Route::post('/{unitConversion}/restore', [UnitConversionController::class, 'restore'])->name('restore');
    // Route::get('/list', [UnitConversionController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [UnitExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [UnitExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('items')->as('items.')->group(function () {
    Route::get('/', [ItemController::class, 'index'])->name('index');
    Route::get('/create', [ItemController::class, 'create'])->name('create');
    Route::post('/', [ItemController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [ItemController::class, 'edit'])->name('edit');
    Route::put('/{item}', [ItemController::class, 'update'])->name('update');
    Route::delete('/{item}', [ItemController::class, 'destroy'])->name('destroy');
    Route::get('/{item}/show', [ItemController::class, 'show'])->name('show');
    Route::post('/{item}/restore', [ItemController::class, 'restore'])->name('restore');
    // Route::get('/list', [ItemController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [ItemExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [ItemExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('bill-sundries')->as('bill-sundries.')->group(function () {
    Route::get('/', [BillSundryController::class, 'index'])->name('index');
    Route::get('/create', [BillSundryController::class, 'create'])->name('create');
    Route::post('/', [BillSundryController::class, 'store'])->name('store');
    Route::get('/{billSundry}/edit', [BillSundryController::class, 'edit'])->name('edit');
    Route::put('/{billSundry}', [BillSundryController::class, 'update'])->name('update');
    Route::delete('/{billSundry}', [BillSundryController::class, 'destroy'])->name('destroy');
    Route::get('/{billSundry}/show', [BillSundryController::class, 'show'])->name('show');
    Route::post('/{billSundry}/restore', [BillSundryController::class, 'restore'])->name('restore');
    // Route::get('/list', [BillSundryController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [BillSundryExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [BillSundryExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('sale-types')->as('sale-types.')->group(function () {
    Route::get('/', [SaleTypeController::class, 'index'])->name('index');
    Route::get('/create', [SaleTypeController::class, 'create'])->name('create');
    Route::post('/', [SaleTypeController::class, 'store'])->name('store');
    Route::get('/{saleType}/edit', [SaleTypeController::class, 'edit'])->name('edit');
    Route::put('/{saleType}', [SaleTypeController::class, 'update'])->name('update');
    Route::delete('/{saleType}', [SaleTypeController::class, 'destroy'])->name('destroy');
    Route::get('/{saleType}/show', [SaleTypeController::class, 'show'])->name('show');
    Route::post('/{saleType}/restore', [SaleTypeController::class, 'restore'])->name('restore');
    // Route::get('/list', [SaleTypeController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [SaleTypeExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [SaleTypeExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('purchase-types')->as('purchase-types.')->group(function () {
    Route::get('/', [PurchaseTypeController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseTypeController::class, 'create'])->name('create');
    Route::post('/', [PurchaseTypeController::class, 'store'])->name('store');
    Route::get('/{purchaseType}/edit', [PurchaseTypeController::class, 'edit'])->name('edit');
    Route::put('/{purchaseType}', [PurchaseTypeController::class, 'update'])->name('update');
    Route::delete('/{purchaseType}', [PurchaseTypeController::class, 'destroy'])->name('destroy');
    Route::get('/{purchaseType}/show', [PurchaseTypeController::class, 'show'])->name('show');
    Route::post('/{purchaseType}/restore', [PurchaseTypeController::class, 'restore'])->name('restore');
    // Route::get('/list', [PurchaseTypeController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [PurchaseTypeExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [PurchaseTypeExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('brokers')->as('brokers.')->group(function () {
    Route::get('/', [BrokerController::class, 'index'])->name('index');
    Route::get('/create', [BrokerController::class, 'create'])->name('create');
    Route::post('/', [BrokerController::class, 'store'])->name('store');
    Route::get('/{broker}/edit', [BrokerController::class, 'edit'])->name('edit');
    Route::put('/{broker}', [BrokerController::class, 'update'])->name('update');
    Route::delete('/{broker}', [BrokerController::class, 'destroy'])->name('destroy');
    Route::get('/{broker}/show', [BrokerController::class, 'show'])->name('show');
    Route::post('/{broker}/restore', [BrokerController::class, 'restore'])->name('restore');
    // Route::get('/list', [BrokerController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [BrokerExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [BrokerExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('conditions')->as('conditions.')->group(function () {
    Route::get('/', [ConditionController::class, 'index'])->name('index');
    Route::get('/create', [ConditionController::class, 'create'])->name('create');
    Route::post('/', [ConditionController::class, 'store'])->name('store');
    Route::get('/{condition}/edit', [ConditionController::class, 'edit'])->name('edit');
    Route::put('/{condition}', [ConditionController::class, 'update'])->name('update');
    Route::delete('/{condition}', [ConditionController::class, 'destroy'])->name('destroy');
    Route::get('/{condition}/show', [ConditionController::class, 'show'])->name('show');
    Route::post('/{condition}/restore', [ConditionController::class, 'restore'])->name('restore');
    // Route::get('/list', [ConditionController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [ConditionExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [ConditionExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('elements')->as('elements.')->group(function () {
    Route::get('/', [ElementController::class, 'index'])->name('index');
    Route::get('/create', [ElementController::class, 'create'])->name('create');
    Route::post('/', [ElementController::class, 'store'])->name('store');
    Route::get('/{element}/edit', [ElementController::class, 'edit'])->name('edit');
    Route::put('/{element}', [ElementController::class, 'update'])->name('update');
    Route::delete('/{element}', [ElementController::class, 'destroy'])->name('destroy');
    Route::get('/{element}/show', [ElementController::class, 'show'])->name('show');
    Route::post('/{element}/restore', [ElementController::class, 'restore'])->name('restore');
    // Route::get('/list', [ElementController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [ElementExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [ElementExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('dairy-parameters')->as('dairy-parameters.')->group(function () {
    Route::get('/', [DairyParameterController::class, 'index'])->name('index');
    Route::get('/create', [DairyParameterController::class, 'create'])->name('create');
    Route::post('/', [DairyParameterController::class, 'store'])->name('store');
    Route::get('/{dairyParameter}/edit', [DairyParameterController::class, 'edit'])->name('edit');
    Route::put('/{dairyParameter}', [DairyParameterController::class, 'update'])->name('update');
    Route::delete('/{dairyParameter}', [DairyParameterController::class, 'destroy'])->name('destroy');
    Route::get('/{dairyParameter}/show', [DairyParameterController::class, 'show'])->name('show');
    Route::post('/{dairyParameter}/restore', [DairyParameterController::class, 'restore'])->name('restore');
    // Route::get('/list', [DairyParameterController::class, 'list'])->name('list');
    // Print and Export

      Route::get('/print', [DairyParameterExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [DairyParameterExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('destinations')->as('destinations.')->group(function () {
    Route::get('/', [DestinationController::class, 'index'])->name('index');
    Route::get('/create', [DestinationController::class, 'create'])->name('create');
    Route::post('/', [DestinationController::class, 'store'])->name('store');
    Route::get('/{destination}/edit', [DestinationController::class, 'edit'])->name('edit');
    Route::put('/{destination}', [DestinationController::class, 'update'])->name('update');
    Route::delete('/{destination}', [DestinationController::class, 'destroy'])->name('destroy');
    Route::get('/{destination}/show', [DestinationController::class, 'show'])->name('show');
    Route::post('/{destination}/restore', [DestinationController::class, 'restore'])->name('restore');
    // Route::get('/list', [DestinationController::class, 'list'])->name('list');

    // Print and Export
    Route::get('/print', [DestinationExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [DestinationExportController::class, 'exportExcel'])->name('export.excel');
});
  


Route::prefix('godowns')->as('godowns.')->group(function () {
    Route::get('/', [GodownController::class, 'index'])->name('index');
    Route::get('/create', [GodownController::class, 'create'])->name('create');
    Route::post('/', [GodownController::class, 'store'])->name('store');
    Route::get('/{godown}/edit', [GodownController::class, 'edit'])->name('edit');
    Route::put('/{godown}', [GodownController::class, 'update'])->name('update');
    Route::delete('/{godown}', [GodownController::class, 'destroy'])->name('destroy');
    Route::get('/{godown}/show', [GodownController::class, 'show'])->name('show');
    Route::post('/{godown}/restore', [GodownController::class, 'restore'])->name('restore');
    // Route::get('/list', [GodownController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [GodownExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [GodownExportController::class, 'exportExcel'])->name('export.excel');
   
});

// Route::prefix('roles')->as('roles.')->group(function () {
//     Route::get('/', [RoleController::class, 'index'])->name('index');
//     Route::get('/create', [RoleController::class, 'create'])->name('create');
//     Route::post('/', [RoleController::class, 'store'])->name('store');
//     Route::get('/{roles}/edit', [RoleController::class, 'edit'])->name('edit');
//     Route::put('/{roles}', [RoleController::class, 'update'])->name('update');
//     Route::delete('/{roles}', [RoleController::class, 'destroy'])->name('destroy');
//     Route::get('/{roles}/show', [RoleController::class, 'show'])->name('show');
//     Route::post('/{roles}/restore', [RoleController::class, 'restore'])->name('restore');
//     Route::get('/list', [RoleController::class, 'list'])->name('list');
// });

Route::prefix('payee-categories')->as('payee-categories.')->group(function () {
    Route::get('/', [PayeeCategoryController::class, 'index'])->name('index');
    Route::get('/create', [PayeeCategoryController::class, 'create'])->name('create');
    Route::post('/', [PayeeCategoryController::class, 'store'])->name('store');
    Route::get('/{payeeCategory}/edit', [PayeeCategoryController::class, 'edit'])->name('edit');
    Route::put('/{payeeCategory}', [PayeeCategoryController::class, 'update'])->name('update');
    Route::delete('/{payeeCategory}', [PayeeCategoryController::class, 'destroy'])->name('destroy');
    Route::get('/{payeeCategory}/show', [PayeeCategoryController::class, 'show'])->name('show');
    Route::post('/{payeeCategory}/restore', [PayeeCategoryController::class, 'restore'])->name('restore');
    // Route::get('/list', [PayeeCategoryController::class, 'list'])->name('list');
    // Print and Export
    Route::get('/print', [PayeeCategoryExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [PayeeCategoryExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('tds-categories')->as('tds-categories.')->group(function () {
    Route::get('/', [TdsCategoryController::class, 'index'])->name('index');
    Route::get('/create', [TdsCategoryController::class, 'create'])->name('create');
    Route::post('/', [TdsCategoryController::class, 'store'])->name('store');
    Route::get('/{tdsCategory}/edit', [TdsCategoryController::class, 'edit'])->name('edit');
    Route::put('/{tdsCategory}', [TdsCategoryController::class, 'update'])->name('update');
    Route::delete('/{tdsCategory}', [TdsCategoryController::class, 'destroy'])->name('destroy');
    Route::get('/{tdsCategory}/show', [TdsCategoryController::class, 'show'])->name('show');
    Route::post('/{tdsCategory}/restore', [TdsCategoryController::class, 'restore'])->name('restore');
    // Route::get('/list', [TdsCategoryController::class, 'list'])->name('list');
    // Print and Export   
    Route::get('/print', [TdsCategoryExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [TdsCategoryExportController::class, 'exportExcel'])->name('export.excel');
});



    Route::prefix('godown-unit-locations')->as('godown_unit_location.')->group(function () {
        Route::get('/', [GodownUnitLocationController::class, 'index'])->name('index');
        Route::get('/create', [GodownUnitLocationController::class, 'create'])->name('create');
        Route::post('/', [GodownUnitLocationController::class, 'store'])->name('store');
        Route::get('/{id}', [GodownUnitLocationController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [GodownUnitLocationController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [GodownUnitLocationController::class, 'update'])->name('update');
        Route::delete('/destroy/{id}', [GodownUnitLocationController::class, 'destroy'])->name('destroy');
    });
    
    Route::prefix('transporters')->as('transporters.')->group(function () {
        Route::get('/', [TransporterController::class, 'index'])->name('index');
        Route::get('/create', [TransporterController::class, 'create'])->name('create');
        Route::post('/', [TransporterController::class, 'store'])->name('store');
        Route::get('/{transporter}/edit', [TransporterController::class, 'edit'])->name('edit');
        Route::put('/{transporter}', [TransporterController::class, 'update'])->name('update');
        Route::delete('/{transporter}', [TransporterController::class, 'destroy'])->name('destroy');
        Route::get('/{transporter}/show', [TransporterController::class, 'show'])->name('show');
        // Print and Export
        Route::get('/print', [TransporterExportController::class, 'print'])->name('print');
        Route::get('/export-excel', [TransporterExportController::class, 'exportExcel'])->name('export.excel');
    });


Route::prefix('vehicle-owners')->as('vehicle-owners.')->group(function () {
    Route::get('/', [VehicleOwnerController::class, 'index'])->name('index');
    Route::get('/create', [VehicleOwnerController::class, 'create'])->name('create');
    Route::post('/', [VehicleOwnerController::class, 'store'])->name('store');
    Route::get('/{vehicleOwner}/edit', [VehicleOwnerController::class, 'edit'])->name('edit');
    Route::put('/{vehicleOwner}', [VehicleOwnerController::class, 'update'])->name('update');
    Route::delete('/{vehicleOwner}', [VehicleOwnerController::class, 'destroy'])->name('destroy');
    Route::get('/{vehicleOwner}/show', [VehicleOwnerController::class, 'show'])->name('show');
});

Route::prefix('drivers')->as('drivers.')->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('index');
    Route::get('/create', [DriverController::class, 'create'])->name('create');
    Route::post('/', [DriverController::class, 'store'])->name('store');
    Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit');
    Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
    Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    Route::get('/{driver}/show', [DriverController::class, 'show'])->name('show');

    // Print and Export
    Route::get('/print', [\App\Http\Controllers\DriverExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [\App\Http\Controllers\DriverExportController::class, 'exportExcel'])->name('export.excel');
});

Route::prefix('vehicles')->as('vehicles.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::get('/create', [VehicleController::class, 'create'])->name('create');
    Route::post('/', [VehicleController::class, 'store'])->name('store');
    Route::get('/{vehicle}/edit', [VehicleController::class, 'edit'])->name('edit');
    Route::put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
    Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
    Route::get('/{vehicle}/show', [VehicleController::class, 'show'])->name('show');

    // Print and Export
    // Route::get('/print', [VehicleExportController::class, 'print'])->name('print');
    // Route::get('/export-excel', [VehicleExportController::class, 'exportExcel'])->name('export.excel');

});
    Route::prefix('zones')->as('zones.')->group(function () {
        Route::get('/', [ZoneController::class, 'index'])->name('index');
        Route::get('/create', [ZoneController::class, 'create'])->name('create');
        Route::post('/', [ZoneController::class, 'store'])->name('store');
        Route::get('/{zone}/edit', [ZoneController::class, 'edit'])->name('edit');
        Route::put('/{zone}', [ZoneController::class, 'update'])->name('update');
        Route::delete('/{zone}', [ZoneController::class, 'destroy'])->name('destroy');
        Route::get('/{zone}/show', [ZoneController::class, 'show'])->name('show');
        // Print and Export
        // Route::get('/print', [ZoneExportController::class, 'print'])->name('print');
        // Route::get('/export-excel', [ZoneExportController::class, 'exportExcel'])->name('export.excel');
    });
    Route::prefix('expense-types')->as('expense-types.')->group(function () {
        Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseTypeController::class, 'create'])->name('create');
        Route::post('/', [ExpenseTypeController::class, 'store'])->name('store');
        Route::get('/{expenseType}/edit', [ExpenseTypeController::class, 'edit'])->name('edit');
        Route::put('/{expenseType}', [ExpenseTypeController::class, 'update'])->name('update');
        Route::delete('/{expenseType}', [ExpenseTypeController::class, 'destroy'])->name('destroy');
        Route::get('/{expenseType}/show', [ExpenseTypeController::class, 'show'])->name('show');
        // Print and Export
        // Route::get('/print', [ExpenseTypeExportController::class, 'print'])->name('print');
        // Route::get('/export-excel', [ExpenseTypeExportController::class, 'exportExcel'])->name('export.excel');
    });

Route::prefix('bags-rates')->as('bags-rates.')->group(function () {
    Route::get('/', [BagsRateController::class, 'index'])->name('index');
    Route::get('/create', [BagsRateController::class, 'create'])->name('create');
    Route::post('/', [BagsRateController::class, 'store'])->name('store');
    Route::get('/{bagsRate}/edit', [BagsRateController::class, 'edit'])->name('edit');
    Route::put('/{bagsRate}', [BagsRateController::class, 'update'])->name('update');
    Route::delete('/{bagsRate}', [BagsRateController::class, 'destroy'])->name('destroy');
    Route::get('/{bagsRate}/show', [BagsRateController::class, 'show'])->name('show');
});

Route::prefix('transport_parties')->as('transport_parties.')->group(function () {
    Route::get('/', [TransportPartyController::class, 'index'])->name('index');
    Route::get('/create', [TransportPartyController::class, 'create'])->name('create');
    Route::post('/', [TransportPartyController::class, 'store'])->name('store');
    Route::get('/{transportParty}/edit', [TransportPartyController::class, 'edit'])->name('edit');
    Route::put('/{transportParty}', [TransportPartyController::class, 'update'])->name('update');
    Route::delete('/{transportParty}', [TransportPartyController::class, 'destroy'])->name('destroy');
    Route::get('/{transportParty}/show', [TransportPartyController::class, 'show'])->name('show');   

    // Print and Export
    Route::get('/print', [TransportPartyController::class, 'print'])->name('print');
    Route::get('/export-excel', [TransportPartyController::class, 'exportExcel'])->name('export.excel');
});

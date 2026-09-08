<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LookupController;

Route::prefix('lookup')->as('lookup.')->group(function () {

    // Main lookup lists (plural)
    Route::get('accounts', [LookupController::class, 'getLedgerAccounts'])->name('accounts');
    Route::get('accounts/party', [LookupController::class, 'getParties'])->name('accounts.party');
    Route::get('brokers', [LookupController::class, 'getBrokers'])->name('brokers');
    Route::get('items', [LookupController::class, 'getItems'])->name('items');
    Route::get('conditions', [LookupController::class, 'getConditions'])->name('conditions');
    Route::get('destinations', [LookupController::class, 'getDestinations'])->name('destinations');
    Route::get('purchase-types', [LookupController::class, 'getPurchaseTypes'])->name('purchase_types');
    Route::get('sales-types', [LookupController::class, 'getSalesTypes'])->name('sale_types');
    Route::get('banks', [LookupController::class, 'getBanks'])->name('banks');
    Route::get('vehicles', [LookupController::class, 'getVehicles'])->name('vehicles');

    // Details of a single resource (singular)
    Route::get('accounts/{id}/details', [LookupController::class, 'getPartyAccountDetails'])
        ->name('account.details');

    Route::get('accounts/party/{id}/details', [LookupController::class, 'getPartyAccountDetails'])
        ->name('accounts.party.details');

    Route::get('items/{id}/details', [LookupController::class, 'getItemDetails'])
        ->name('item.details');

    Route::get('destinations/{id}/details', [LookupController::class, 'getDestinationDetails'])
        ->name('destination.details');

    Route::get('purchase-types/{id}/details', [LookupController::class, 'getPurchaseTypeDetails'])
        ->name('purchase_type.details');

    Route::get('bill-sundries', [LookupController::class, 'billSundries'])->name('billSundries');
    Route::get('voucher-types', [LookupController::class, 'voucherTypes'])->name('voucherTypes');

    Route::get('sale-types/{id}/details', [LookupController::class, 'getSaleTypeDetails'])
        ->name('sale_type.details');

    Route::get('get-account-balance', [LookupController::class, 'getAccountBalance'])
        ->name('account.balance');

    Route::get('get-preload-ledger-accounts', [LookupController::class, 'getPreloadLedgerAccounts'])
        ->name('preload.ledger.accounts');

    Route::get('transport-parties', [LookupController::class, 'getTransportParties'])->name('transport_parties');
    Route::get('expense-accounts', [LookupController::class, 'getExpenseAccounts'])->name('expense_accounts');
    Route::get('all-destinations', [LookupController::class, 'getAllDestinations'])->name('all_destinations');
    Route::get('all-items', [LookupController::class, 'getAllItems'])->name('all_items');
    Route::get('all-vehicles', [LookupController::class, 'getAllVehicles'])->name('all_vehicles');
    Route::get('all-contractors', [LookupController::class, 'getAllContractors'])->name('all_contractors');
});

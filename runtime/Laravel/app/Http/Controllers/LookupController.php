<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\LookupService;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class LookupController extends Controller
{
    protected int $companyId;
    protected LookupService $lookupService;

    public function __construct(LookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function getParties(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $parties = $this->lookupService->getParties($companyId, $request->q);

            return AjaxResponse::success('Party Account Fetch Successfully', data: $parties);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getBrokers(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $brokers = $this->lookupService->getBrokers($companyId, $request->q);

            return AjaxResponse::success('Broker Account Fetch Successfully', data: $brokers);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getVehicles(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $vehicles = $this->lookupService->getVehicles($companyId, $request->q);

            return AjaxResponse::success('Vehicles Fetch Successfully', data: $vehicles);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getAllVehicles(): JsonResponse
    {
        try {
            $companyId = company_id();
            $vehicles  = $this->lookupService->getAllVehicles($companyId);

            return AjaxResponse::success('All Vehicles Fetch Successfully', data: $vehicles);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getItems(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $items = $this->lookupService->getItems($companyId, $request->q);

            return AjaxResponse::success('Items Fetch Successfully', data: $items);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getAllItems(): JsonResponse
    {
        try {
            $companyId = company_id();
            $items     = $this->lookupService->getAllItems($companyId);

            return AjaxResponse::success('All Items Fetch Successfully', data: $items);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getConditions(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $conditions = $this->lookupService->getConditions($companyId, $request->q);

            return AjaxResponse::success('Conditions Fetch Successfully', data: $conditions);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getDestinations(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $destinations = $this->lookupService->getDestinations($companyId, $request->q);

            return AjaxResponse::success('Destinations Fetch Successfully', data: $destinations);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getAllDestinations(): JsonResponse
    {
        try {
            $companyId    = company_id();
            $destinations = $this->lookupService->getAllDestinations($companyId);

            return AjaxResponse::success('All Destinations Fetch Successfully', data: $destinations);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getExpenseAccounts(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $accounts  = $this->lookupService->getExpenseAccounts($companyId, $request->q);

            return AjaxResponse::success('Expense Accounts Fetch Successfully', data: $accounts);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getPartyAccountDetails(int $id): JsonResponse
    {
        try {
            $companyId = company_id();
            $account = $this->lookupService->getPartyAccountDetails($companyId, $id);

            if (!$account) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $account);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getItemDetails(int $id): JsonResponse
    {
        try {
            $companyId = company_id();
            $item = $this->lookupService->getItemDetails($companyId, $id);

            if (!$item) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $item);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getPurchaseTypes(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $purchaseTypes = $this->lookupService->getPurchaseTypes($companyId, $request->q, $request->region);

            if (!$purchaseTypes) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $purchaseTypes);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }


    public function getPurchaseTypeDetails(int $id): JsonResponse
    {
        try {
            $companyId = company_id();
            $purchaseType = $this->lookupService->getPurchaseTypeDetails($companyId, $id);

            if (!$purchaseType) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $purchaseType);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getLedgerAccounts(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $ledgerAccounts = $this->lookupService->getLedgerAccounts($companyId, $request->q);

            return AjaxResponse::success('Ledger Accounts Fetch Successfully', data: $ledgerAccounts);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function billSundries(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $billSundries = $this->lookupService->billSundries($companyId);

            return AjaxResponse::success('Bill Sundries Fetch Successfully', data: $billSundries);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function voucherTypes(): JsonResponse
    {
        try {
            $voucherTypes = $this->lookupService->voucherTypes();

            return AjaxResponse::success('Voucher Types Fetch Successfully', data: $voucherTypes);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getSalesTypes(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $salesTypes = $this->lookupService->getSalesTypes($companyId, $request->q, $request->region);

            if (!$salesTypes) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $salesTypes);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getSaleTypeDetails(int $id): JsonResponse
    {
        // dd('here');
        try {
            $companyId = company_id();
            $purchaseType = $this->lookupService->getSaleTypeDetails($companyId, $id);

            if (!$purchaseType) {
                return AjaxResponse::error(message: __('messages.common.data_not_found'));
            }

            return AjaxResponse::success(message: __('messages.common.data_found'), data: $purchaseType);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getBanks(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $banks = $this->lookupService->getBanks($companyId, $request->q);

            return AjaxResponse::success('Banks Fetch Successfully', data: $banks);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getAccountBalance(Request $request): JsonResponse
    {
        try {
            $accountId = $request->account_id;
            $financialYearId = financial_year_id();
            $companyId = company_id();
            
            $accountBalance = $this->lookupService->getAccountBalance($companyId, $financialYearId, $accountId);

            return AjaxResponse::success('Account Balance Fetch Successfully', data: $accountBalance);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getPreloadLedgerAccounts(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $ledgerAccounts = $this->lookupService->getPreloadLedgerAccounts($companyId);

            return AjaxResponse::success('Ledger Accounts Fetch Successfully', data: $ledgerAccounts);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }
    public function getTransportParties(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $parties = $this->lookupService->getTransportParties($companyId, $request->q);

            return AjaxResponse::success('Transport Parties Fetched Successfully', data: $parties);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

    public function getAllContractors(Request $request): JsonResponse
    {
        try {
            $companyId = company_id();
            $contractors = $this->lookupService->getAllContractors($companyId, $request->q);

            return AjaxResponse::success('Contractors Fetch Successfully', data: $contractors);
        } catch (Throwable $e) {
            return AjaxResponse::error(message: __('messages.common.something_went_wrong'), errors: $e->getMessage());
        }
    }

}

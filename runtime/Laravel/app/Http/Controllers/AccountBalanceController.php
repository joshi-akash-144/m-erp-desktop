<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;


class AccountBalanceController extends Controller
{
    protected AccountBalanceService $accountBalanceService;

    public function __construct(AccountBalanceService $accountBalanceService)
    {
        $this->accountBalanceService = $accountBalanceService;
    }

    //  TODO add hear opening balance logic
    public function openingBalance() {}

    public function closingBalance(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $accountIds = $request->account_ids ?: null;

        $result = $this->accountBalanceService
            ->getClosingBalance($companyId, $financialYearId, $accountIds);

        return AjaxResponse::success(
            message: "Account Balance fetched successfully",
            data: $result
        );
    }

    // TODO when need get hear opening and closing for
    public function calculate(){

    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use App\Services\CompanyService;
use App\Services\DaybookReportService;
use App\Services\MasterDataService;
use Throwable;

use Illuminate\Http\Request;

class DaybookReportController extends Controller
{
    protected DaybookReportService $daybookService;
    protected MasterDataService $masterService;
    protected  $companyService;


    public function __construct(DaybookReportService $daybookService, CompanyService $companyService, MasterDataService $masterService)
    {
        $this->middleware('permission:daybook_report.list')->only('list');
        $this->daybookService = $daybookService;
        $this->companyService = $companyService;
        $this->masterService = $masterService;
    }

    public function index()
    {
        $accounts = $this->masterService->get('accounts', company_id());
        return view('company.pages.daybook.index', ['accounts' => $accounts]);
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $voucherTypeKeys = implode(',', array_keys(config('constants.voucher_type')));

            $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
                'account_id' => 'nullable|exists:accounts,id',
                'voucher_type_id' => 'nullable|in:' . $voucherTypeKeys,
                'narration' => 'nullable|boolean',
            ]);

            $companyId = company_id();
            $financialYearId = financial_year_id();

            $data = $this->daybookService->getDaybookData(
                $companyId,
                $financialYearId,
                $request->all()
            );

            return response()->json([
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'data' => [],
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

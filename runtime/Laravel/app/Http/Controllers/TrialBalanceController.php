<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use App\Services\TrialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class TrialBalanceController extends Controller
{
    protected TrialBalanceService $service;
    protected CompanyService $companyService;

    public function __construct(TrialBalanceService $service, CompanyService $companyService)
    {
        $this->middleware('permission:trial_balance.list')->only(['index', 'getOpeningTrialBalance', 'getGroupWiseTrialBalanceBalance', 'getGroupWiseTrialBalanceDetail', 'getAccountMonthWiseSummary', 'getAccountLedger']);
        $this->middleware('permission:trial_balance.print')->only(['print', 'printOpeningList', 'printGroupWiseList', 'printGroupWiseBalance', 'printGroupWiseDetail', 'printAccountLedger', 'printAccountMonthWise']);
        $this->middleware('permission:trial_balance.export')->only(['exportExcel', 'exportExcelOpeningList', 'exportExcelGroupWiseList', 'exportExcelGroupWiseBalance', 'exportExcelGroupWiseDetail', 'exportExcelAccountLedger', 'exportExcelAccountMonthWise']);

        $this->service        = $service;
        $this->companyService = $companyService;
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $filters = $request->only(['report_type', 'view_type', 'parent_group', 'as_on_date', 'from_date', 'to_date']);

            $trialBalance = $this->service->getTrialBalanceReport(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                filters: $filters
            );

            return AjaxResponse::success(data: $trialBalance);
        }

        return view('company.pages.trial-balance.index');
    }

    public function getGroupWiseTrialBalanceBalance(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $groupId = (int) session('selected_group_id', 0);

        if (!$groupId) {
            return AjaxResponse::error('No group selected. Please click a group first.');
        }

        $filters = $request->only(['as_on_date', 'view_type']);
        $data    = $this->service->getGroupWiseTrialBalanceBalance($groupId, $filters);

        return AjaxResponse::success(data: $data);
    }

    public function getGroupWiseTrialBalanceDetail(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $groupId = (int) session('selected_group_id', 0);

        if (!$groupId) {
            return AjaxResponse::error('No group selected. Please click a group first.');
        }

        $filters = $request->only(['from_date', 'to_date']);
        $data    = $this->service->getGroupWiseTrialBalanceDetail($groupId, $filters);

        return AjaxResponse::success(data: $data);
    }

    public function getOpeningTrialBalance(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $company = $this->companyService->current(company_id());
            $filters = [
                'start_date' => $company->currentFinancialYear->start_date,
                'end_date'   => $company->currentFinancialYear->end_date,
            ];

            $companyId       = company_id();
            $financialYearId = financial_year_id();
            $viewType        = $request->input('view_type', 'group');

            if ($viewType === 'account') {
                $trialBalance = $this->service->getAccountWiseOpeningTrialBalance($companyId, $company, $financialYearId, $filters);
            } else {
                $trialBalance = $this->service->getOpeningTrialBalance($companyId, $company, $financialYearId, $filters);
            }

            return AjaxResponse::success(data: $trialBalance);
        }

        return view('company.pages.trial-balance.opening');
    }

    public function getAccountLedger(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accountId = (int) $request->input('account_id', 0);
        $fromDate  = $request->input('from_date', '');
        $toDate    = $request->input('to_date', '');

        if (!$accountId || !$fromDate || !$toDate) {
            return AjaxResponse::error('Account ID, from date and to date are required.');
        }

        $data = $this->service->getAccountLedger($accountId, $fromDate, $toDate);

        return AjaxResponse::success(data: $data);
    }

    public function getAccountMonthWiseSummary(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accountId = (int) $request->input('account_id', 0);
        $fromDate  = $request->input('from_date', '');
        $toDate    = $request->input('to_date', '');

        if (!$accountId || !$fromDate || !$toDate) {
            return AjaxResponse::error('Account ID, from date and to date are required.');
        }

        $data = $this->service->getAccountMonthWiseSummary($accountId, $fromDate, $toDate);

        return AjaxResponse::success(data: $data);
    }

    public function printAccountLedger(Request $request): JsonResponse
    {
        $request->validate(['format' => 'required|in:print,pdf']);

        $filters = [
            'account_id'  => (int) $request->input('account_id', 0),
            'start_date'  => $request->input('from_date', ''),
            'end_date'    => $request->input('to_date', ''),
            'ledger_by'   => 'single',
            'narration'   => false,
            'voucher_ids' => [],
        ];

        $company = $this->companyService->current(company_id());
        $data    = $this->service->prepareLedgerPrintData($company, $filters);
        $html    = view('company.pages.ledger.print', $data)->render();

        return AjaxResponse::success('Ledger Exported Successfully', ['html' => $html]);
    }

    public function exportExcelAccountLedger(Request $request): JsonResponse
    {
        try {
            $filters = [
                'account_id'  => (int) $request->input('account_id', 0),
                'start_date'  => $request->input('from_date', ''),
                'end_date'    => $request->input('to_date', ''),
                'ledger_by'   => 'single',
                'narration'   => false,
                'voucher_ids' => [],
            ];

            $company = $this->companyService->current(company_id());
            $result  = $this->service->prepareLedgerExportFormat($company, $filters, 'xlsx');

            return AjaxResponse::success("Ledger Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function printAccountMonthWise(Request $request): JsonResponse
    {
        $request->validate(['format' => 'required|in:print,pdf']);

        $accountId = (int) $request->input('account_id', 0);
        $fromDate  = $request->input('from_date', '');
        $toDate    = $request->input('to_date', '');

        $company = $this->companyService->current(company_id());
        $data    = $this->service->prepareMonthWisePrintData($company, $accountId, $fromDate, $toDate);
        $html    = view('company.pages.trial-balance.month-wise-print', $data)->render();

        return AjaxResponse::success('Month Wise Summary Exported Successfully', ['html' => $html]);
    }

    public function exportExcelAccountMonthWise(Request $request): JsonResponse
    {
        try {
            $accountId = (int) $request->input('account_id', 0);
            $fromDate  = $request->input('from_date', '');
            $toDate    = $request->input('to_date', '');

            $company = $this->companyService->current(company_id());
            $result  = $this->service->prepareMonthWiseExportFormat($company, $accountId, $fromDate, $toDate, 'xlsx');

            return AjaxResponse::success("Month Wise Summary Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function selectGroupRow(Request $request): JsonResponse
    {
        session(['selected_group_id' => $request->group_id]);

        return AjaxResponse::success(data: ['success' => session()->has('selected_group_id')]);
    }

    public function getGroupWiseTrialBalance(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $groupId = session('selected_group_id');
        $company = $this->companyService->current(company_id());

        // Use request dates when provided (trial balance index), otherwise fall back to full FY (opening trial balance)
        $filters = [
            'start_date' => $request->input('start_date') ?: $company->currentFinancialYear->start_date,
            'end_date'   => $request->input('end_date')   ?: $company->currentFinancialYear->end_date,
        ];

        $groupwiseData = $this->service->getGroupWiseTrialBalance($groupId, $company, $filters);

        return AjaxResponse::success(data: $groupwiseData);
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->only(['report_type', 'view_type', 'parent_group', 'as_on_date', 'from_date', 'to_date']);
        $company = $this->companyService->current(company_id());
        $data    = $this->service->prepareTrialBalancePrintData($company, $filters);
        $html    = view('company.pages.trial-balance.print', $data)->render();

        return AjaxResponse::success('Trial Balance Exported Successfully', ['html' => $html]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        try {
            $company = $this->companyService->current(company_id());
            $filters = $request->only(['report_type', 'view_type', 'parent_group', 'as_on_date', 'from_date', 'to_date']);
            $result  = $this->service->prepareTrialBalanceExportFormat($company, $filters, 'xlsx');

            return AjaxResponse::success("Trial Balance Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function printOpeningList(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company  = $this->companyService->current(company_id());
        $filters  = [
            'start_date' => $company->currentFinancialYear->start_date,
            'end_date'   => $company->currentFinancialYear->end_date,
        ];
        $viewType = $request->input('view_type', 'group');

        if ($viewType === 'account') {
            $data = $this->service->prepareAccountWiseOpeningTrialBalancePrintData($company, $filters);
        } else {
            $data = $this->service->prepareOpeningTrialBalancePrintData($company, $filters);
        }

        $html = view('company.pages.trial-balance.print', $data)->render();

        return AjaxResponse::success('Opening Trial Balance Exported Successfully', ['html' => $html]);
    }

    public function exportExcelOpeningList(Request $request): JsonResponse
    {
        try {
            $company  = $this->companyService->current(company_id());
            $filters  = [
                'start_date' => $company->currentFinancialYear->start_date,
                'end_date'   => $company->currentFinancialYear->end_date,
            ];
            $viewType = $request->input('view_type', 'group');

            if ($viewType === 'account') {
                $result = $this->service->prepareAccountWiseOpeningTrialBalanceExportFormat($company, $filters, 'xlsx');
            } else {
                $result = $this->service->prepareOpeningTrialBalanceExportFormat($company, $filters, 'xlsx');
            }

            return AjaxResponse::success("Opening Trial Balance Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function printGroupWiseBalance(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->only(['as_on_date', 'view_type']);
        $groupId = (int) session('selected_group_id', 0);

        if (!$groupId) {
            return AjaxResponse::error('No group selected. Please click a group first.');
        }

        $data = $this->service->prepareGroupWiseBalancePrintData($company, $filters, $groupId);
        $html = view('company.pages.trial-balance.group-wise-print', $data)->render();

        return AjaxResponse::success('Group Wise Trial Balance Exported Successfully', ['html' => $html]);
    }

    public function exportExcelGroupWiseBalance(Request $request): JsonResponse
    {
        try {
            $company = $this->companyService->current(company_id());
            $filters = $request->only(['as_on_date', 'view_type']);
            $groupId = (int) session('selected_group_id', 0);

            if (!$groupId) {
                return AjaxResponse::error('No group selected. Please click a group first.');
            }

            $result = $this->service->prepareGroupWiseBalanceExportFormat($company, $filters, $groupId, 'xlsx');

            return AjaxResponse::success("Group Wise Trial Balance Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function printGroupWiseList(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company   = $this->companyService->current(company_id());
        $startDate = $company->currentFinancialYear->start_date;
        $filters   = [
            'start_date' => $startDate,
            'end_date'   => $company->currentFinancialYear->end_date,
            'as_on_date' => date('d-m-Y', strtotime($startDate)),
        ];
        $groupId = (int) session('selected_group_id', 0);

        if (!$groupId) {
            return AjaxResponse::error('No group selected. Please click a group first.');
        }

        $data = $this->service->prepareGroupWiseTrialBalancePrintData($company, $filters, $groupId);
        $html = view('company.pages.trial-balance.group-wise-print', $data)->render();

        return AjaxResponse::success('Group Wise Trial Balance Exported Successfully', ['html' => $html]);
    }

    public function exportExcelGroupWiseList(): JsonResponse
    {
        try {
            $company = $this->companyService->current(company_id());
            $filters = [
                'start_date' => $company->currentFinancialYear->start_date,
                'end_date'   => $company->currentFinancialYear->end_date,
            ];
            $groupId = (int) session('selected_group_id', 0);

            if (!$groupId) {
                return AjaxResponse::error('No group selected. Please click a group first.');
            }

            $result = $this->service->prepareGroupWiseTrialBalanceExportFormat($company, $filters, $groupId, 'xlsx');

            return AjaxResponse::success("Group Wise Trial Balance Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function printGroupWiseDetail(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');
        $filters = [
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'start_date' => $fromDate ? date('d-m-Y', strtotime($fromDate)) : '',
            'end_date'   => $toDate ? date('d-m-Y', strtotime($toDate)) : '',
        ];
        $groupId = (int) session('selected_group_id', 0);

        if (!$groupId) {
            return AjaxResponse::error('No group selected. Please click a group first.');
        }

        $data = $this->service->prepareGroupWiseDetailPrintData($company, $filters, $groupId);
        $html = view('company.pages.trial-balance.group-wise-print', $data)->render();

        return AjaxResponse::success('Group Wise Trial Balance Detail Exported Successfully', ['html' => $html]);
    }

    public function exportExcelGroupWiseDetail(Request $request): JsonResponse
    {
        try {
            $company = $this->companyService->current(company_id());
            $fromDate = $request->input('from_date');
            $toDate   = $request->input('to_date');
            $filters = [
                'from_date'  => $fromDate,
                'to_date'    => $toDate,
                'start_date' => $fromDate ? date('d-m-Y', strtotime($fromDate)) : '',
                'end_date'   => $toDate ? date('d-m-Y', strtotime($toDate)) : '',
            ];
            $groupId = (int) session('selected_group_id', 0);

            if (!$groupId) {
                return AjaxResponse::error('No group selected. Please click a group first.');
            }

            $result = $this->service->prepareGroupWiseDetailExportFormat($company, $filters, $groupId, 'xlsx');

            return AjaxResponse::success("Group Wise Trial Balance Detail Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use App\Services\ProfitLossService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class ProfitLossController extends Controller
{
    protected ProfitLossService $service;
    protected CompanyService $companyService;

    public function __construct(ProfitLossService $service, CompanyService $companyService)
    {
        $this->middleware('permission:profit_loss.list')->only(['index', 'list']);
        $this->middleware('permission:profit_loss.print')->only('print');
        $this->middleware('permission:profit_loss.export')->only('exportExcel');

        $this->service        = $service;
        $this->companyService = $companyService;
    }

    public function index(): View
    {
        return view('company.pages.profit-loss.index');
    }

    public function list(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $filters = $request->only(['from_date', 'to_date', 'view_type']);

        $report = $this->service->getTradingAndPLReport(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $filters
        );

        return AjaxResponse::success(data: $report);
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->only(['from_date', 'to_date', 'view_type']);
        $company = $this->companyService->current(company_id());
        $data    = $this->service->preparePrintData($company, $filters);
        $html    = view('company.pages.profit-loss.print', $data)->render();

        return AjaxResponse::success('Profit & Loss Report Generated Successfully', ['html' => $html]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['from_date', 'to_date', 'view_type']);
            $company = $this->companyService->current(company_id());
            $result  = $this->service->prepareExportFormat($company, $filters, 'xlsx');

            return AjaxResponse::success("Profit & Loss Exported successfully ({$result['format']})", [
                'file_url'  => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

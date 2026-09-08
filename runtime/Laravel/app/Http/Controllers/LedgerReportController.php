<?php

namespace App\Http\Controllers;

use App\Http\Requests\LedgerReportRequest;
use App\Models\VoucherType;
use App\Services\LedgerReportService;
use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use App\Services\CompanyService;
use Throwable;

use Illuminate\Http\Request;

class LedgerReportController extends Controller
{
    protected LedgerReportService $ledgerReportService;
    protected  $companyService;

    public function __construct(LedgerReportService $ledgerReportService, CompanyService $companyService)
    {
        $this->middleware('permission:ledger_report.list')->only('list');
        $this->middleware('permission:ledger_report.print')->only('print');
        $this->middleware('permission:ledger_report.export')->only(['export']);
        $this->ledgerReportService = $ledgerReportService;
        $this->companyService = $companyService;
    }

    public function index()
    {
        $voucherTypes = VoucherType::all();
        return view('company.pages.ledger.index', compact('voucherTypes'));
    }

    public function list(LedgerReportRequest $request)
    {
        $filter = $request->validated();
        if (empty($filter['account_id'])) {
            return response()->json([
                'data'             => [],
                'debit'            => 0,
                'credit'           => 0,
                'opening_balance'  => 0,
                'closing_balance'  => 0,
            ]);
        }

        $companyId = company_id();
        $financialYearId = financial_year_id();
        $data = $this->ledgerReportService->getLedgerData($companyId, $financialYearId, $filter);

        return response()->json([
            'data'      => $data['transactions'],
            'debit'     => $data['totals']['total_debit'] ?? 0,
            'credit'    => $data['totals']['total_credit'] ?? 0,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'closing_balance' => $data['closing_balance'] ?? 0,
        ]);
    }

    public function print(Request $request): JsonResponse{
        // Printing Item Wise Stock Status    
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter;
        $company = $this->companyService->current(company_id());
        $data = $this->ledgerReportService->prepareLedgerPrintData($company, $filters);

        $html = view('company.pages.ledger.print', $data)->render();

        return AjaxResponse::success(
            message: 'Ledger Report Exported Successfully',
            data:['html'=>$html]
        );
    }

    public function exportExcel(Request $request): JsonResponse{
        try {
            $company = $this->companyService->current(company_id());
            $filters = $request->currentFilter ?? [];
            $result = $this->ledgerReportService->prepareLedgerExportFormat($company,$filters,'xlsx');

            return AjaxResponse::success("Ledger Exported successfully ({$result['format']})", [
                'file_url' => $result['file_url'],
                'file_name' => $result['file_name'],
            ]);

        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}

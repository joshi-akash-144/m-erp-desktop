<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use App\Services\CompanyService;
use App\Services\DaybookReportService;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DaybookExport;
use Illuminate\Http\Request;

class DaybookReportExportController extends Controller
{
    protected DaybookReportService $daybookService;
    protected $companyService;


    public function __construct(DaybookReportService $daybookService, CompanyService $companyService)
    {
        $this->middleware('permission:daybook_report.print')->only('print');
        $this->middleware('permission:daybook_report.export')->only('export');
        $this->daybookService = $daybookService;
        $this->companyService = $companyService;
    }

    public function print(Request $request): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];

            $daybooks = $this->daybookService->getDaybookData(
                company_id(),
                financial_year_id(),
                $filters
            );
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['start_date'])
                ? Carbon::parse($filters['start_date'])->format('d-m-Y')
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');

            $endDate = !empty($filters['end_date'])
                ? Carbon::parse($filters['end_date'])->format('d-m-Y')
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');

            $datePeriod = "$startDate to $endDate";
            $tableConfig = [
                "columns" => [
                    ["label" => "Date", "class" => "text-start", "width" => "12%"],
                    ["label" => "Voucher Type", "class" => "text-center", "width" => "10%"],
                    ["label" => "Vch / Ref No.", "class" => "text-center", "width" => "10%"],
                    ["label" => "Particulars", "class" => "text-start", "width" => "30%"],
                    ["label" => "Debit", "class" => "text-end", "width" => "13%"],
                    ["label" => "Credit", "class" => "text-end", "width" => "35%"],
                ],
            ];
            $totalDebit = $daybooks->filter(fn($row) => !isset($row->row_type))->sum('debit');
            $totalCredit = $daybooks->filter(fn($row) => !isset($row->row_type))->sum('credit');
            $data = [
                'company' => $company,
                'currentYear' => $currentYear,
                'daybooks' => $daybooks,
                'tableConfig' => $tableConfig,
                'orientation' => $request->input('orientation', 'landscape'),
                'datePeriod' => $datePeriod,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
            ];
            // dd(json_decode(json_encode($data),true));
            $html = view('company.pages.daybook.print', $data)->render();

            return AjaxResponse::success(
                message: 'Daybook Report generated successfully',
                data: [
                    'html' => $html,
                ]
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.daybook_report.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    public function exportExcel(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'csv');
    }
    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];

            $daybooks = $this->daybookService->getDaybookData(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['start_date'])
                ? $filters['start_date']
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');

            $endDate = !empty($filters['end_date'])
                ? $filters['end_date']
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');

            $datePeriod = "$startDate to $endDate";

            $headings = [
                'Date',
                'Voucher Type',
                'Vch / Ref No.',
                'Particulars',
                'Debit',
                'Credit',
            ];

            $directory = 'daybook_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_daybook_report_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new DaybookExport($company, $daybooks, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Daybook Report Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

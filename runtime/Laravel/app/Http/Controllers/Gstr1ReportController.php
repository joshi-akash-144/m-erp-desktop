<?php

namespace App\Http\Controllers;

use App\Services\Gstr1ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Gstr1ReportController extends Controller
{
    protected Gstr1ReportService $gstr1ReportService;

    public function __construct(Gstr1ReportService $gstr1ReportService)
    {
        $this->middleware('permission:gstr1_report.list');
        $this->gstr1ReportService = $gstr1ReportService;
    }

    public function index()
    {
        $company = \App\Models\Company::find(company_id());
        $companyName = $company ? $company->name : company_name();
        $companyGst  = $company ? $company->gst_number : '';
        return view('company.pages.gst.gstr1.index', compact('companyName', 'companyGst'));
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $data = $this->gstr1ReportService->getSummary(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $request->only(['from_date', 'to_date'])
        );

        return response()->json($data);
    }

    public function detail(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'section'   => 'required|string|in:b2b,b2cl,b2cs,cdnr,cdnu,exports,nil_rated,hsn_detail',
        ]);

        $data = $this->gstr1ReportService->getDetail(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $request->only(['from_date', 'to_date', 'section', 'hsn_code'])
        );

        return response()->json($data);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date'   => 'required|date|after_or_equal:from_date',
            ]);

            $company = \App\Models\Company::find(company_id());
            $startDate = \Carbon\Carbon::parse($request->from_date)->format('d-m-Y');
            $endDate = \Carbon\Carbon::parse($request->to_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";

            $data = $this->gstr1ReportService->getSummary(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date'])
            );

            $directory = 'gstr1_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr1_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr1Export($company, $data, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-1 Report Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return \App\Helpers\AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function exportDetailExcel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date'   => 'required|date|after_or_equal:from_date',
                'section'   => 'required|string|in:b2b,b2cl,b2cs,cdnr,cdnu,exports,nil_rated,hsn_summary,hsn_detail',
            ]);

            $company = \App\Models\Company::find(company_id());
            $startDate = \Carbon\Carbon::parse($request->from_date)->format('d-m-Y');
            $endDate   = \Carbon\Carbon::parse($request->to_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
            $section = $request->section;

            $data = $this->gstr1ReportService->getDetailForExport(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date', 'section', 'hsn_code'])
            );

            $directory = 'gstr1_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr1_{$section}_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr1DetailExport($company, $section, $data, $datePeriod, $request->hsn_code),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-1 {$section} Exported successfully (xlsx)", [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return \App\Helpers\AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function exportOfflineExcel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date'   => 'required|date|after_or_equal:from_date',
            ]);

            $company = \App\Models\Company::find(company_id());
            $startDate = \Carbon\Carbon::parse($request->from_date)->format('d-m-Y');
            $endDate = \Carbon\Carbon::parse($request->to_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";

            $summaryData = $this->gstr1ReportService->getSummary(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date'])
            );

            $detailDataMap = [];
            $sections = ['b2b', 'b2cl', 'b2cs', 'cdnr', 'cdnu', 'exports', 'nil_rated', 'hsn_summary'];
            
            foreach ($sections as $section) {
                // HSN summary takes no section param but generates differently if section is hsn_summary
                $detailDataMap[$section] = $this->gstr1ReportService->getDetailForExport(
                    companyId:       company_id(),
                    financialYearId: financial_year_id(),
                    filters:         array_merge($request->only(['from_date', 'to_date']), ['section' => $section])
                );
            }

            $directory = 'gstr1_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $gstin     = strtoupper($company->gst_number ?? 'GSTIN');
            $monthName = \Carbon\Carbon::parse($request->from_date)->format('F'); // e.g. April
            $year      = \Carbon\Carbon::parse($request->from_date)->format('Y'); // e.g. 2026
            $fileName  = "GSTR1_{$gstin}_{$monthName}_{$year}_ERP.xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr1OfflineExport($company, $summaryData, $detailDataMap, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-1 Offline Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return \App\Helpers\AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}


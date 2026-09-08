<?php

namespace App\Http\Controllers;

use App\Services\Gstr3bReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Gstr3bReportController extends Controller
{
    protected Gstr3bReportService $gstr3bReportService;

    public function __construct(Gstr3bReportService $gstr3bReportService)
    {
        $this->middleware('permission:gstr3b_report.list');
        $this->gstr3bReportService = $gstr3bReportService;
    }

    public function index()
    {
        $company = \App\Models\Company::find(company_id());
        $companyName = $company ? $company->name : company_name();
        $companyGst  = $company ? $company->gst_number : '';
        return view('company.pages.gst.gstr3b.index', compact('companyName', 'companyGst'));
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $data = $this->gstr3bReportService->getSummary(
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
            'section'   => 'required|string',
        ]);

        $data = $this->gstr3bReportService->getDetail(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $request->only(['from_date', 'to_date', 'section', 'size'])
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

            $data = $this->gstr3bReportService->getSummary(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date'])
            );

            $directory = 'gstr3b_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr3b_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr3bExport($company, $data, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-3B Report Exported successfully (xlsx)", [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
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
                'section'   => 'required|string',
            ]);

            $company = \App\Models\Company::find(company_id());
            $startDate = \Carbon\Carbon::parse($request->from_date)->format('d-m-Y');
            $endDate   = \Carbon\Carbon::parse($request->to_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
            $section = $request->section;

            $data = $this->gstr3bReportService->getDetailForExport(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date', 'section'])
            );

            $directory = 'gstr3b_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $safeSection = str_replace('.', '_', $section);
            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr3b_{$safeSection}_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr3bDetailExport($company, $section, $data, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-3B {$section} Exported successfully (xlsx)", [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return \App\Helpers\AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

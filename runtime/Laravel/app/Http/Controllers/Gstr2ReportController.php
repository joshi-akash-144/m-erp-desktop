<?php

namespace App\Http\Controllers;

use App\Services\Gstr2ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Gstr2ReportController extends Controller
{
    protected Gstr2ReportService $gstr2ReportService;

    public function __construct(Gstr2ReportService $gstr2ReportService)
    {
        $this->middleware('permission:gstr2_report.list');
        $this->gstr2ReportService = $gstr2ReportService;
    }

    public function index()
    {
        $company = \App\Models\Company::find(company_id());
        $companyName = $company ? $company->name : company_name();
        $companyGst  = $company ? $company->gst_number : '';
        return view('company.pages.gst.gstr2.index', compact('companyName', 'companyGst'));
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $data = $this->gstr2ReportService->getSummary(
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
            'section'   => 'required|string|in:b2b,b2bur,cdnr,cdnu,import_goods,import_services,nil_rated,hsn_detail',
        ]);

        $data = $this->gstr2ReportService->getDetail(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $request->only(['from_date', 'to_date', 'section', 'hsn_code', 'size'])
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

            $data = $this->gstr2ReportService->getSummary(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date'])
            );

            $directory = 'gstr2_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr2_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr2Export($company, $data, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-2 Report Exported successfully (xlsx)", [
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
                'section'   => 'required|string|in:b2b,b2bur,cdnr,cdnu,import_goods,import_services,nil_rated,hsn_summary,hsn_detail',
            ]);

            $company = \App\Models\Company::find(company_id());
            $startDate = \Carbon\Carbon::parse($request->from_date)->format('d-m-Y');
            $endDate   = \Carbon\Carbon::parse($request->to_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
            $section = $request->section;

            $data = $this->gstr2ReportService->getDetailForExport(
                companyId:       company_id(),
                financialYearId: financial_year_id(),
                filters:         $request->only(['from_date', 'to_date', 'section', 'hsn_code'])
            );

            $directory = 'gstr2_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!\Illuminate\Support\Facades\File::exists($directoryPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = \Illuminate\Support\Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_gstr2_{$section}_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\Gstr2DetailExport($company, $section, $data, $datePeriod, $request->hsn_code),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return \App\Helpers\AjaxResponse::success("GSTR-2 {$section} Exported successfully (xlsx)", [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return \App\Helpers\AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

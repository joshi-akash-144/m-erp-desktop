<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Vehicle;
use App\Models\Company;
use App\Services\VehicleExpenditureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exports\VehicleExpenditureExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VehicleExpenditureController extends Controller
{

    /*------------------------------------------------------------------
    | INDEX — blade view
    ------------------------------------------------------------------*/
    public function index(Request $request)
    {
        $companyId = company_id();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        return view('company.pages.driver-expense.expenditure-report', compact('vehicles'));
    }

    /*------------------------------------------------------------------
    | REPORT DATA — AJAX JSON
    ------------------------------------------------------------------*/
    public function reportData(Request $request, VehicleExpenditureService $service): JsonResponse
    {
        $request->validate([
            'from_date'  => ['nullable', 'date_format:Y-m-d'],
            'to_date'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'vehicle_id' => ['nullable', 'integer'],
            'view_type'  => ['nullable', 'string', 'in:summary,detail'],
        ]);

        $filters = $request->only(['from_date', 'to_date', 'vehicle_id', 'view_type']);

        $result = $service->getReport(
            companyId:       company_id(),
            financialYearId: financial_year_id(),
            filters:         $filters
        );

        return AjaxResponse::success('Report loaded.', $result);
    }

    /*------------------------------------------------------------------
    | EXPORT EXCEL — JSON response with file URL
    ------------------------------------------------------------------*/
    public function exportExcel(Request $request, VehicleExpenditureService $service): JsonResponse
    {
        try {
            $filters = $request->input('currentFilter', []);

            $companyId = company_id();
            $financialYearId = financial_year_id();

            $result = $service->getReport(
                $companyId,
                $financialYearId,
                $filters
            );

            $company = Company::find($companyId);

            // Setup Date Period string
            $fromDate = $filters['from_date'] ?? null;
            $toDate   = $filters['to_date'] ?? null;

            if ($fromDate && $toDate) {
                $datePeriod = Carbon::parse($fromDate)->format('d-m-Y') . " to " . Carbon::parse($toDate)->format('d-m-Y');
            } elseif ($fromDate) {
                $datePeriod = "From " . Carbon::parse($fromDate)->format('d-m-Y');
            } elseif ($toDate) {
                $datePeriod = "Till " . Carbon::parse($toDate)->format('d-m-Y');
            } else {
                $datePeriod = "All Time";
            }

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_vehicle_expenditure_" . now()->format('d_m_Y_His') . ".xlsx";

            Excel::store(
                new VehicleExpenditureExport($company, $result['columns'], $result['reportRows'], $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Vehicle Expenditure Report Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (\Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

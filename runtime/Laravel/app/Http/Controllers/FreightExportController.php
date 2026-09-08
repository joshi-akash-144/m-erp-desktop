<?php

namespace App\Http\Controllers;

use App\Exports\FreightExport;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\FreightService;
use Carbon\Carbon;

class FreightExportController extends Controller
{
    protected CompanyService $companyService;
    protected FreightService $freightService;

    public function __construct(CompanyService $companyService, FreightService $freightService)
    {
        $this->middleware('permission:freight.export')->only(['exportExcel', 'exportCsv']);
        $this->companyService = $companyService;
        $this->freightService = $freightService;
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

        $activeFilters = array_filter($request->currentFilter ?? [], function ($val) {
            return $val !== null && $val !== '';
        });

        if (empty($activeFilters)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a date range to export this register.'
            ]);
        }

            $filters = array_merge($request->currentFilter ?? [], [
                'company_id'        => company_id(),
                'financial_year_id' => financial_year_id(),
            ]);

            $company     = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($request->currentFilter['start_date'])
                ? Carbon::parse($request->currentFilter['start_date'])->format('d-m-Y')
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');

            $endDate = !empty($request->currentFilter['end_date'])
                ? Carbon::parse($request->currentFilter['end_date'])->format('d-m-Y')
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');

            $datePeriod = "$startDate to $endDate";

            $exportData = $this->freightService->freightExportList($filters);

            $headings = [
                'Bill To',
                'Bill No',
                'Invoice Date',
                'Lr No.',
                'Vehicle No.',
                'Item',
                'Consignor',
                'Consignee',
                'From Destination',
                'To Destination',
                'Bag Count',
                'Net Weight',
                'KMS',
                'Freight Rate',
                'Freight',
            ];

            $directory     = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }
            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_freight_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new FreightExport($company, $exportData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Freight Exported successfully ({$format})", [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
     
}

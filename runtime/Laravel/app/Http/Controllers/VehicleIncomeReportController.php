<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Vehicle;
use App\Models\Account;
use App\Models\Company;
use App\Models\Voucher;
use App\Services\VehicleIncomeReportService;
use App\Exports\VehicleIncomeExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VehicleIncomeReportController extends Controller
{
    public function index(Request $request, VehicleIncomeReportService $service)
    {
        $companyId = company_id();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $accounts  = Account::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        
        $voucherNumbers = $service->getVoucherNumbers($companyId);

        return view('company.pages.vehicle-income.index', compact('vehicles', 'accounts', 'voucherNumbers'));
    }

    public function reportData(Request $request, VehicleIncomeReportService $service): JsonResponse
    {
        $request->validate([
            'from_date'  => ['nullable', 'date_format:Y-m-d'],
            'to_date'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'vehicle_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
            'type'       => ['nullable', 'string', 'in:all,freight,freight_invoice,freight_invoice2'],
            'voucher_no' => ['nullable', 'string'],
        ]);

        $filters = $request->only(['from_date', 'to_date', 'vehicle_id', 'account_id', 'type', 'voucher_no']);

        $result = $service->getReport(company_id(), financial_year_id(), $filters);
          
        return AjaxResponse::success('Report loaded.', $result);
    }

    public function print(Request $request, VehicleIncomeReportService $service): JsonResponse
    {
        $filters = $request->input('currentFilter', []);
        
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $result = $service->getReport($companyId, $financialYearId, $filters);
        
        $company = Company::find($companyId);
        $reportData = $result['data'] ?? [];

        $html = view('company.pages.vehicle-income.print', compact('company', 'reportData', 'filters'))->render();

        return AjaxResponse::success(
            message: 'Vehicle Income Report printed successfully.',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request, VehicleIncomeReportService $service): JsonResponse
    {
        try {
            $filters = $request->input('currentFilter', []);

            $companyId = company_id();
            $financialYearId = financial_year_id();

            $result = $service->getReport($companyId, $financialYearId, $filters);

            $company = Company::find($companyId);
            $reportData = $result['data'] ?? [];

            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->format('d-m-Y') : ($currentYear ? Carbon::parse($currentYear->start_date)->format('d-m-Y') : '');

            $endDate = !empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->format('d-m-Y') : ($currentYear ? Carbon::parse($currentYear->end_date)->format('d-m-Y') : '');

            $datePeriod = "$startDate to $endDate";
            
            $reportType = 'Type:- All';
            if (!empty($filters['type']) && $filters['type'] !== 'all') {
                $reportType = 'Type:- ' . ucwords(str_replace('_', ' ', $filters['type']));
            }

            $columns = [
                ['title' => 'Voucher No', 'field' => 'voucher_no'],
                ['title' => 'Date', 'field' => 'date'],
                ['title' => 'Party Name', 'field' => 'party_name'],
                ['title' => 'Ref No', 'field' => 'ref_no'],
                ['title' => 'Vehicle Number', 'field' => 'vehicle_name'],
                ['title' => 'Type', 'field' => 'type'],
                ['title' => 'Amount', 'field' => 'amount'],
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_vehicle_income_" . now()->format('d_m_Y_His') . ".xlsx";

            Excel::store(
                new VehicleIncomeExport($company, $columns, $reportData, $datePeriod, $reportType),
                "{$directory}/{$fileName}",'public',\Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Vehicle Income Report Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (\Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

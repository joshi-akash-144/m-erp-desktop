<?php

namespace App\Http\Controllers;

use App\Exports\DriverExport;
use App\Models\Driver;
use App\Models\VoucherType;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class DriverExportController extends Controller
{
    protected int $companyId;
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {

        $this->middleware('permission:driver.print')->only('print');
        $this->middleware('permission:driver.export')->only(['exportExcel', 'exportCsv']);

        $this->companyService = $companyService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);
        
        $query = Driver::select('id', 'account_id', 'vehicle_id', 'license_number')
            ->with([
                'account:id,name',
                'vehicle:id,name',
            ])
            ->where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('account', function($q2) use ($searchTerm) {
                    $q2->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('license_number', 'like', '%' . $searchTerm . '%');
            });
        }

        $drivers = $query->orderBy('id', 'asc')->get();        
        $obMap = $this->buildObMap($drivers->pluck('account_id')->filter()->toArray());

        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Driver name", "class" => "text-start ps-2","width"=>"48%"],
                ["label" => "Vehicle No", "class" => "text-start","width"=>"18%"],
                ["label" => "Opening Balance(Dr)", "class" => "text-end","width"=>"15%"],
                ["label" => "License No", "class" => "text-end","width"=>"15%"],
            ],
        ];

        $data = [
            'company'     => $company,
            'drivers'     => $drivers,
            'obMap'       => $obMap,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.driver.print', $data)->render();

        return AjaxResponse::success(
            message: 'Driver fetched successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'csv');
    }

    private function buildObMap(array $accountIds): \Illuminate\Support\Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->select('vt.account_id', 'vt.debit', 'vt.credit')
            ->whereIn('vt.account_id', $accountIds)
            ->where('v.company_id', company_id())
            ->where('v.financial_year_id', financial_year_id())
            ->where('v.voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('v.is_opening', true)
            ->whereNull('v.deleted_at')
            ->get()
            ->keyBy('account_id');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);
        
        $query = Driver::with(['account.taxDetail', 'vehicle:id,name'])
            ->where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('account', function($q2) use ($searchTerm) {
                    $q2->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('license_number', 'like', '%' . $searchTerm . '%');
            });
        }

        $drivers = $query->orderBy('id', 'asc')->get();

        $obMap = $this->buildObMap($drivers->pluck('account_id')->filter()->toArray());
       
        $headings = [
            'Driver Name',
            'Mobile Number',
            'Address Line 1',
            'Address Line 2',
            'City',
            'Pincode',
            'Vehicle No',
            'Opening Balance(Dr)',            
            'License No',
            'License Category',
            'License Issuing Authority',
            'License Expiry Date (TR)',
            'License Expiry Date (NT)',
            'Date of Joining',
            'Aadhaar Number',
            'Religion',
            'Qualification',
            'Marital Status',
            // 'Blood Group',
            'Salary',            
            'PAN No',
            'Remarks'
        ];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_drivers_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new DriverExport($company, $drivers, $headings, $obMap),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );


            return AjaxResponse::success(
                message: 'Drivers Exported successfully ({$format})',
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Export failed',
                errors: [$e->getMessage()],
            );
        }
    }
}

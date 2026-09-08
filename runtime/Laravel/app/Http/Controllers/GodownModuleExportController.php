<?php

namespace App\Http\Controllers;

use App\Repositories\GodownModuleRepository;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use App\Services\GodownModuleService;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Exports\GodownModuleExport;
use App\Exports\GodownTransporterExport; 
use Carbon\Carbon;

class GodownModuleExportController extends Controller
{
    protected GodownModuleRepository $repository;
    protected $companyService;
    protected $godownService;

    public function __construct(
        GodownModuleRepository $godownModuleRepository, 
        CompanyService $companyService, 
        GodownModuleService $godownService
    ) {
        $this->middleware('permission:godown_module.print')->only('print');
        $this->middleware('permission:godown_module.export')->only(['exportExcel', 'exportCsv']);

        $this->repository = $godownModuleRepository;
        $this->companyService = $companyService;
        $this->godownService = $godownService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter ?? [];
        
        // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownData = $godownDataResponse['godownData'];
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
                ["label" => "Sr No.<br>GRN No", "class" => "text-start",'width'=>"7%"],
                ["label" => "Product<br>IN/OUT", "class" => "text-start",'width'=>"5%"],
                ["label" => "Vehicle No", "class" => "text-start",'width'=>"7%"],
                ["label" => "Particular<br>Product", "class" => "text-start",'width'=>"32%"],
                ["label" => "Challan No.<br>Cha. Weight", "class" => "text-start",'width'=>"9%"],
                ["label" => "Bags", "class" => "text-start",'width'=>"4%"],
                ["label" => "Destination", "class" => "text-start",'width'=>"20%"],
                ["label" => "Date", "class" => "text-start",'width'=>"9%"],
                ["label" => "Weight", "class" => "text-start",'width'=>"14%"],
                ["label" => "Net Weight", "class" => "text-end",'width'=>"7%"],
                ["label" => "Without Bag Weight", "class" => "text-end",'width'=>"7%"],
            ],
        ];

        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'start_date' => $filters['start_date'] ?? 'N/A',
            'end_date' => $filters['end_date'] ?? 'N/A',
            'datePeriod' => $datePeriod,
            'page' => 1,
            'action_id' => $filters['product_status'] ?? 'all',
            'tableConfig' => $tableConfig,
        ];

        $html = view('company.pages.godown.print', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Godown Print data generated successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function printTicket(Request $request): JsonResponse
    {
        $filters = $request->currentFilter ?? [];
        $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownData = $godownDataResponse['godownData'];
        // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $company = $this->companyService->current(company_id());
        
        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
            'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
        ];

        $html = view('company.pages.godown.ticket', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Godown Ticket data generated successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function printLetter(Request $request): JsonResponse
    {
        $filters = $request->currentFilter ?? [];
        $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownData = $godownDataResponse['godownData'];
        // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $company = $this->companyService->current(company_id());
        
        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
            'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
        ];

        $html = view('company.pages.godown.letter', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Godown Letter data generated successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function printGatepass(Request $request): JsonResponse
    {
        $filters = $request->currentFilter ?? [];
        $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownData = $godownDataResponse['godownData'];
        // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $company = $this->companyService->current(company_id());
        
        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
            'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
        ];

        $html = view('company.pages.godown.gatepass', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Godown Gatepass data generated successfully',
            data: [
                'html' => $html,
            ]
        );
    }
    
    public function printGRN(Request $request): JsonResponse
    {
        $filters = $request->currentFilter ?? [];
        $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $godownData = $godownDataResponse['godownData'];
        // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
        $company = $this->companyService->current(company_id());
        
        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
            'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
        ];

        $html = view('company.pages.grn.grn_print_report', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Godown GRN data generated successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function printTransporter(Request $request): JsonResponse
    {
        $filters = $request->currentFilter ?? [];
        $godownData = $this->godownService->getTransportersForExport(company_id(), financial_year_id(), $filters);
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
                ["label" => "Sr No.", "class" => "text-center", 'width' => "5%"],
                ["label" => "Vehicle No", "class" => "text-start ps-2", 'width' => "20%"],
                ["label" => "Transporter Name", "class" => "text-start ps-2", 'width' => "25%"],
                ["label" => "LR Number", "class" => "text-start ps-2", 'width' => "12.5%"],
                ["label" => "Gross Weight", "class" => "text-end pe-2", 'width' => "12.5%"],
                ["label" => "Tare Weight", "class" => "text-end pe-2", 'width' => "12.5%"],
                ["label" => "Net Weight", "class" => "text-end pe-2", 'width' => "12.5%"],
                ["label" => "Without Bag Weight", "class" => "text-end pe-2", 'width' => "12.5%"],
            ],
        ];

        $data = [
            'data' => $godownData,
            'companyName' => $company->print_name ?? $company->name,
            'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
            'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
            'gstNumber' => $company->gst_number ?? '',
            'datePeriod' => $datePeriod,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.godown.transporter.print', compact('data'))->render();

        return AjaxResponse::success(
            message: 'Transporter Print data generated successfully',
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

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];
            // $godownData = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
            $godownDataResponse = $this->godownService->getGodowns(company_id(), financial_year_id(), $filters);
            $godownData = $godownDataResponse['godownData'];
            $company = $this->companyService->current(company_id());

            $headings = [
                'SR NO',
                'GRN NO',
                'IN/OUT',
                'VEHICLE NO',
                'ACCOUNT NAME',
                'ITEM NAME',
                'PARTY DESTINATION',
                'CHALLAN NO',
                'CHALLAN WEIGHT',
                'BAGS',
                'GODOWN',
                'UNIT',
                'DATE',
                'DATE IN',
                'TIME IN',
                'DATE OUT',
                'TIME OUT',
                'P.QTY',
                'RATE',
                'GROSS WEIGHT',
                'TARE WEIGHT',
                'NET WEIGHT',
                'WITHOUT BAG WEIGHT'
            ];

            $directory = 'godown_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_godown_list_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new GodownModuleExport($company, $godownData, $headings, $filters),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Godown List Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
    public function exportTransporterExcel(Request $request): JsonResponse
    {
        return $this->exportTransporterByFormat($request, 'xlsx');
    }

    public function exportTransporterCsv(Request $request): JsonResponse
    {
        return $this->exportTransporterByFormat($request, 'csv');
    }

    private function exportTransporterByFormat(Request $request, string $format): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];
            $transporterData = $this->godownService->getTransportersForExport(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());

            $headings = [
                'SR NO',
                'VEHICLE NO',
                'TRANSPORTER NAME',
                'LR NUMBER',
                'GROSS WEIGHT',
                'TARE WEIGHT',
                'NET WEIGHT',
                'WITHOUT BAG WEIGHT'
            ];

            $directory = 'godown_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_godown_transporters_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new GodownTransporterExport($company, $transporterData, $headings, $filters),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Transporters Weight Summary Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}

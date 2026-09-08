<?php

namespace App\Http\Controllers;

use App\Exports\ContractorExport;
use App\Models\Contractor;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ContractorExportController extends Controller
{
    protected int $companyId;
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->middleware('permission:contractor.print')->only('print');
        $this->middleware('permission:contractor.export')->only(['exportExcel', 'exportCsv']);

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
        
        $query = Contractor::where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // if (!empty($filters['filter_contract_id'])) {
        //     $query->where('id', $filters['filter_contract_id']);
        // }

        $contractors = $query->orderBy('name', 'asc')->get();        

        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"10%"],
                ["label" => "Contractor Name", "class" => "text-start ps-2","width"=>"50%"],
                ["label" => "City", "class" => "text-start","width"=>"40%"],
            ],
        ];

        $data = [
            'company'     => $company,
            'contractors'   => $contractors,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.contractor.print', $data)->render();

        return AjaxResponse::success(
            message: 'Contract fetched successfully',
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
        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);
        
        $query = Contractor::where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        // if (!empty($filters['filter_contract_id'])) {
        //     $query->where('id', $filters['filter_contract_id']);
        // }

        $contracts = $query->orderBy('name', 'asc')->get();
       
        $headings = [
            'Contractor Name',
            'City',
        ];

        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_contractor_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new ContractorExport($company, $contracts, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'Contractor Exported successfully',
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

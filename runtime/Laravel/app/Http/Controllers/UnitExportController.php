<?php

namespace App\Http\Controllers;

use App\Exports\UnitExport;
use App\Helpers\AjaxResponse;
use App\Repositories\UnitRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class UnitExportController extends Controller
{
    protected UnitRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(UnitRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:unit.print')->only('print');
        $this->middleware('permission:unit.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


        $this->middleware(function ($request, $next) {
            $this->companyId = session('company_id');
            return $next($request);
        });
    }

    public function print(Request $request): JsonResponse
    {

        $request->validate([
            'format' => 'required|in:print,pdf',
        ]);

        $company = $this->companyService->current($this->companyId);
        $filters = $request->input('currentFilter', []);

        $units = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => $this->companyId],
            orderBy: 'name',            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_unit_id'])) {
                        $query->where('id', $filters['filter_unit_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],
        );

        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-center", "width" => "5%"],
                ["label" => "Name", "class" => "text-start ps-2", "width" => "35%"],
                ["label" => "Print Name", "class" => "text-start ps-2", "width" => "35%"],
                ["label" => "UQC", "class" => "text-start ps-2", "width" => "25%"],
            ]
        ];

        $data = [
            'company' => $company,
            'units' => $units,
            'tableConfig' => $tableConfig
        ];

        $html = view('company.pages.masters.unit.print', $data)->render();

        return AjaxResponse::success(
            message: 'Unit fetched successfully',
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
        $company = $this->companyService->current($this->companyId);
        $filters = $request->input('currentFilter', []);

        $units = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => $this->companyId],
            orderBy: 'name',            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_unit_id'])) {
                        $query->where('id', $filters['filter_unit_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],            
        );

        $headings = ['Name', 'Print Name', 'UQC'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_units_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new UnitExport($company, $units, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'Units Exported successfully ({$format})',
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

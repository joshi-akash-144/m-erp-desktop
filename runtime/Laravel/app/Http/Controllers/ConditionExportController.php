<?php

namespace App\Http\Controllers;

use App\Exports\ConditionExport;
use App\Helpers\AjaxResponse;
use App\Repositories\ConditionRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ConditionExportController extends Controller
{
    protected ConditionRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(ConditionRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:condition.print')->only('print');
        $this->middleware('permission:condition.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


        // $this->middleware(function ($request, $next) {
        //     $this->companyId = session('company_id');
        //     return $next($request);
        // });
    }

    public function print(Request $request): JsonResponse
    {

        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);

        $conditions = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'name',           
            scopes: [
                function ($query) use ($filters) {
                    if (!empty($filters['filter_condition_id'])) {
                        $query->where('id', $filters['filter_condition_id']);
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
        //keep simple table structure for flexibility
        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Name", "class" => "text-start ps-4","width"=>"30%"],
                ["label" => "Print Name", "class" => "text-start ps-2","width"=>"20%"],
            ],
        ];

        $data = [
            'company' => $company,
            'conditions' => $conditions,
            'tableConfig' => $tableConfig,
            'orientation'=> $request->input('orientation','portrait'),
        ];


        $html = view('company.pages.masters.condition.print', $data)->render();

        return AjaxResponse::success(
            message: 'Conditions fetched successfully',
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

        $conditions = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'name',           
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_condition_id'])) {
                        $query->where('id', $filters['filter_condition_id']);
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

        $headings = ['Name', 'Print Name'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_conditions_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new ConditionExport($company, $conditions, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'Conditions Exported successfully ({$format})',
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

<?php

namespace App\Http\Controllers;

use App\Exports\DairyParameterExport;
use App\Helpers\AjaxResponse;
use App\Repositories\DairyParameterRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class DairyParameterExportController extends Controller
{
    protected DairyParameterRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(DairyParameterRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:dairy_parameter.print')->only('print');
        $this->middleware('permission:dairy_parameter.export')->only(['export']);

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

        $dairyParameters = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'id',            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('guarantee', 'like', '%' . $searchTerm . '%')
                                ->orWhere('id', 'like', '%' . $searchTerm . '%')
                                ->orWhereHas('condition', function ($q2) use ($searchTerm) {
                                    $q2->where('name', 'like', '%' . $searchTerm . '%');
                                })
                                ->orWhereHas('element', function ($q3) use ($searchTerm) {
                                    $q3->where('name', 'like', '%' . $searchTerm . '%');
                                });
                        });
                    }
                }
            ]
        );
        // keep simple table structure for flexibility
        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Condition", "class" => "text-start ps-2","width"=>"30%"],
                ["label" => "Element", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "Guarantee", "class" => "text-end ps-2","width"=>"25%"],
            ]
        ];

        $data = [
            'company' => $company,
            'dairyParamters' => $dairyParameters,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation','portrait'),
        ];

        $html = view('company.pages.masters.dairy-parameter.print', $data)->render();

        return AjaxResponse::success(
            message: 'Dairy Parameters fetched successfully',
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

        $dairyParameters = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'id',            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('guarantee', 'like', '%' . $searchTerm . '%')
                                ->orWhere('id', 'like', '%' . $searchTerm . '%')
                                ->orWhereHas('condition', function ($q2) use ($searchTerm) {
                                    $q2->where('name', 'like', '%' . $searchTerm . '%');
                                })
                                ->orWhereHas('element', function ($q3) use ($searchTerm) {
                                    $q3->where('name', 'like', '%' . $searchTerm . '%');
                                });
                        });
                    }
                }
            ]
        );

        $headings = ['Condition Name', 'Element Name','Guarantee'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_dairy_parameters_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new DairyParameterExport($company, $dairyParameters, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'dairy Parameters Exported successfully ({$format})',
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

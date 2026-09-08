<?php

namespace App\Http\Controllers;

use App\Exports\ElementExport;
use App\Helpers\AjaxResponse;
use App\Repositories\ElementRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ElementExportController extends Controller
{
    protected ElementRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(ElementRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:element.print')->only('print');
        $this->middleware('permission:element.export')->only(['export']);

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

        $elements = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => $this->companyId],
            orderBy: 'name',
            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_element_id'])) {
                        $query->where('id', $filters['filter_element_id']);
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

        $table = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Name", "class" => "text-start ps-2","width"=>"30%"],
                ["label" => "Print Name", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "Range", "class" => "text-start ps-2","width"=>"25%"],
            ],
            "body" => []
        ];

        foreach ($elements as $key => $element) {
            $table["body"][] = [
                [
                    "value" => $key + 1 . '.',
                    "class" => "text-center",
                    "style" => "width: 5%;",
                ],
                [
                    "value" => str($element->name)->limit(40),
                    "class" => "text-start ps-2",
                    "style" => "width: 35%;",
                ],
                [
                    "value" => str($element->print_name)->limit(40),
                    "class" => "text-start ps-2",
                    "style" => "width: 35%;",
                ],
                [
                    "value" => $element->range ?: '',
                    "class" => "text-start ps-2",
                    "style" => "width: 25%;",
                ],
            ];
        }



        $data = [
            'company' => $company,
            'elements' => $elements,
            'tableConfig' => $table,
            'orientation'=> $request->input('orientation','portrait'),
        ];


        $html = view('company.pages.masters.element.print', $data)->render();

        return AjaxResponse::success(
            message: 'Element fetched successfully',
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

        $elements = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => $this->companyId],
            orderBy: 'name',
            
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_element_id'])) {
                        $query->where('id', $filters['filter_element_id']);
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

        $headings = ['Name', 'Print Name', 'Range'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_elements_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new ElementExport($company, $elements, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'Elements Exported successfully ({$format})',
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

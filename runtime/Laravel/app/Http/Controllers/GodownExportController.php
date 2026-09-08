<?php

namespace App\Http\Controllers;

use App\Exports\GodownExport;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use App\Models\Godown;
use App\Repositories\GodownRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class GodownExportController extends Controller
{
    protected GodownRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(GodownRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:godown.print')->only('print');
        $this->middleware('permission:godown.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


        // $this->middleware(function ($request, $next) {
        //     company_id() = session('company_id');
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

        $godowns = $this->repository->all(
            columns: ['*'],
            // with: ['parent'], // Eager load
            filters: ['company_id' => company_id()],
            orderBy: 'godown_name',           
            scopes: [                
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('id', $filters['filter_group_id']);
                    }                 
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('godown_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('remark', 'like', '%' . $searchTerm . '%')
                              ->orWhereHas('destinationName', function ($q2) use ($searchTerm) {
                                  $q2->where('name', 'like', '%' . $searchTerm . '%');
                              });
                        });
                    }
                }
            ],
        );

        // Keep simple table structure for flexibility
        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start", "width" => "5%"],
                ["label" => "Destination Name", "class" => "text-start ps-2", "width" => "40%"],
                ["label" => "Godown Name", "class" => "text-start", "width" => "40%"],
                ["label" => "Remark", "class" => "text-start", "width" => "15%"],
            ]
        ];

        $data = [
            'company' => $company,
            'godowns' => $godowns,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.godown.print', $data)->render();

        return AjaxResponse::success(
            message: __('messages.godown.fetched'),
            data: ['html' => $html]
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

        $godowns = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'godown_name',           
            scopes: [                
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('id', $filters['filter_group_id']);
                    }                 
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('godown_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('remark', 'like', '%' . $searchTerm . '%')
                              ->orWhereHas('destinationName', function ($q2) use ($searchTerm) {
                                  $q2->where('name', 'like', '%' . $searchTerm . '%');
                              });
                        });
                    }
                }
            ],
        );


        $headings = ['Destination Name', 'Godwon Name', 'Remark'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_Godowns_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new GodownExport($company, $godowns, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );


            return AjaxResponse::success(
                message: 'Account Group Exported successfully ({$format})',
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

    public function printGrnReport(Request $request)
    {
        dd($request->toArray());
    }

    
}

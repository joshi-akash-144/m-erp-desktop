<?php

namespace App\Http\Controllers;

use App\Exports\SaleTypeExport;
use App\Repositories\SaleTypeRepository;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class SaleTypeExportController extends Controller
{
    protected SaleTypeRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(SaleTypeRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:sale_type.print')->only('print');
        $this->middleware('permission:sale_type.export')->only(['export']);

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
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);

        $saleTypes = $this->repository->all(
            columns: ['*'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'name',            
            scopes: [
                function ($query) use ($filters) {
                    if (!empty($filters['filter_type_id'])) {
                        $query->where('taxation_type', $filters['filter_type_id']);
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
                ["label" => "Name", "class" => "text-start ps-2","width"=>"40%"],
                ["label" => "Account", "class" => "text-start ps-2","width"=>"40%"],
                ["label" => "Taxation Type", "class" => "text-start ps-2","width"=>"40%"],
                ["label" => "Transaction Type", "class" => "text-start ps-2","width"=>"40%"],
                ["label" => "Region", "class" => "text-start ps-2","width"=>"40%"],
            ]
        ];


        $data = [
            'company' => $company,
            'saleTypes' => $saleTypes,
            'tableConfig' => $table,
        ];


        $html = view('company.pages.masters.sale-type.print', $data)->render();

        return AjaxResponse::success(
            message: 'Sale Type fetched successfully',
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
        
        $saleTypes = $this->repository->all(
            columns: ['*'],
            with: ['account:id,name'],
            filters: ['company_id' => company_id()],
            orderBy: 'name',            
            scopes: [
                function ($query) use ($filters) {
                    if (!empty($filters['filter_type_id'])) {
                        $query->where('taxation_type', $filters['filter_type_id']);
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

        $headings = ['Name','Specify Account','Taxation Type','Transaction Type','Region','CGST %','SGST %','IGST %'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_sale_types_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new SaleTypeExport($company, $saleTypes, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'Sale Types Exported successfully ({$format})',
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

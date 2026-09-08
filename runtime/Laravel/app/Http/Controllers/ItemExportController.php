<?php

namespace App\Http\Controllers;

use App\Exports\ItemExport;
use App\Helpers\AjaxResponse;
use App\Repositories\ItemRepository;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ItemExportController extends Controller
{
    protected ItemRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(ItemRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:item.print')->only('print');
        $this->middleware('permission:item.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


    }

    public function print(Request $request): JsonResponse
    {

        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation'=>'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->currentFilter;

        $items = $this->repository->all(
            columns: ['*'],
            with: ['itemGroup','taxCategory','currentYearBalance','unit'],
            filters: ['company_id' => company_id()],
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('item_group_id', $filters['filter_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],
            orderBy: 'name'
        );
        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start",'width'=>"5%"],
                ["label" => "Print Name", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "SKU", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "Group", "class" => "text-start ps-2","width"=>"25%"],
                ["label" => "Unit", "class" => "text-start ps-2","width"=>"15%"],
                ["label" => "Tax Cate.", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "HSN/SAC", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "Ope. Stock", "class" => "text-end ps-2","width"=>"20%"],
                ["label" => "Ope. Value", "class" => "text-end ps-2","width"=>"20%"],
            ]
        ];



        $data = [
            'company' => $company,
            'items' => $items,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation','portrait'),
        ];


        $html = view('company.pages.masters.item.print', $data)->render();

        return AjaxResponse::success(
            message: 'Item fetched successfully',
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
        $filters = $request->currentFilter;

        $items = $this->repository->all(
            columns: ['*'],
            with: ['itemGroup','taxCategory','currentYearBalance','unit'],
            filters: ['company_id' => company_id()],
            scopes: [
                fn($query) => $query->status('active'), 
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('item_group_id', $filters['filter_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],
            orderBy: 'name'
        );

        $headings = ['Name', 'Print Name', 'Group','Unit','Tax Category','HSN/SAC','Opening Stock','Opening Value'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_items_" . now()->format('d_m_Y_His') . ".{$format}";
        
        try {
            Excel::store(
                new ItemExport($company, $items, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            
            return AjaxResponse::success(
                message: 'Items Exported successfully ({$format})',
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

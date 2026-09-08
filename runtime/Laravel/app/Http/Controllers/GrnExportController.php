<?php

namespace App\Http\Controllers;

use App\Exports\GrnExport;
use App\Repositories\GrnRepository;
use App\Helpers\AjaxResponse;
use App\Models\Grn;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\GrnService;
use Illuminate\View\View;
use carbon\carbon;

class GrnExportController extends Controller
{
    protected GrnRepository $repository;
    protected int $companyId;
    protected  $companyService;

    protected $grnService;


    public function __construct(GrnRepository $repository, CompanyService $companyService, GrnService $grnService)
    {

        $this->middleware('permission:grn.print')->only('print');
        $this->middleware('permission:grn.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->grnService = $grnService;

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

        // Handle filters robustly
        $filters = $request->currentFilter ?? [];
        // if (is_string($filters)) {
        //     $filters = json_decode($filters, true) ?? [];
        // }
        // $filters = array_merge($request->only(['start_date', 'end_date', 'grn_no', 'grn_number', 'account_id', 'item_id', 'broker_id', 'condition_id', 'destination_id', 'grn_status', 'qc_status']), $filters ?? []);

        $grns = $this->grnService->grnListAll(company_id(), financial_year_id(), $filters);       
        
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        
        $startDate = !empty($filters['start_date']) 
            ? $filters['start_date'] 
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($filters['end_date']) 
            ? $filters['end_date'] 
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";

        $tableConfig = [
            "columns" => [
                ["label" => "Grn No.", "class" => "text-start text-nowrap",'width'=>"7%"],
                ["label" => "Date In", "class" => "text-start",'width'=>"9%"],
                ["label" => "Date Out", "class" => "text-start",'width'=>"9%"],
                ["label" => "Bill No.", "class" => "text-end",'width'=>"5%"],
                ["label" => "P.O.No.", "class" => "text-end",'width'=>"5%"],
                ["label" => "Supplier", "class" => "text-start",'width'=>"22%"],
                ["label" => "Items", "class" => "text-start",'width'=>"11%"],
                ["label" => "Destination", "class" => "text-start",'width'=>"11%"],
                ["label" => "Qty.", "class" => "text-end",'width'=>"6%"],
                ["label" => "P.Qty.", "class" => "text-end",'width'=>"6%"],
                ["label" => "Rate", "class" => "text-end",'width'=>"6%"],
                ["label" => "Incl Rate", "class" => "text-end",'width'=>"12%"],               
            ],
        ];   

        $data = [
            'company' => $company,
            'currentYear'=> $currentYear,
            'grns' => $grns,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation','landscape'),
            'datePeriod' => $datePeriod,
        ];

        $html = view('company.pages.grn.print', $data)->render();

        return AjaxResponse::success(
            message: 'Grn Data fetched successfully',
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
            // if (is_string($filters)) {
            //     $filters = json_decode($filters, true) ?? [];
            // }

            $grns = $this->grnService->grnListAll(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['start_date']) ? $filters['start_date'] : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            $endDate = !empty($filters['end_date']) ? $filters['end_date'] : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        
            $headings = [
                'Grn.No.',
                'Grn In Date',
                'Grn Out Date',
                'Bill No.',
                'P.O.No.',
                'Supplier',
                'Broker',
                'Item',
                'Destination',
                'Condition',
                'P.Qty.',
                'Quantity',
                'Incl Rate',
                'Rate',
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_grn_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use your Export class (make sure it handles $grns)
            Excel::store(
                new GrnExport($company, $grns, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Purchase Orders Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
    
    public function grnPrint(Request $request): View |JsonResponse
    {
        // dd($request->toArray());
        $grnId = $request->grnId;
        $company = $this->companyService->current(company_id());       
        $grnData = $this->grnService->getEditData(
            grnId: $grnId,
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $companyName = $company->name;
        
        // dd($grnData);
        $html = view('company.pages.grn.grn_print_report', compact('grnData', 'companyName'))->render();
        return AjaxResponse::success(
            message: 'grn print fetched successfully',
            data: [
                'html' => $html,
            ]
        );
        
    }

}

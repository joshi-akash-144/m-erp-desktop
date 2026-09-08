<?php

namespace App\Http\Controllers;

use App\Exports\SalesOrderWithBillExport;
use App\Repositories\SalesOrderRepository;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\SalesOrderService;
use Carbon\Carbon;


class SalesOrderWithBillExportController extends Controller
{
    protected SalesOrderRepository $repository;
    protected int $companyId;
    protected  $companyService;

    protected $salesOrderService;


    public function __construct(SalesOrderRepository $repository, CompanyService $companyService, SalesOrderService $salesOrderService)
    {

        $this->middleware('permission:sales_order.print')->only('print');
        $this->middleware('permission:sales_order.export')->only(['exportExcel', 'exportCsv']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->salesOrderService = $salesOrderService;
    }

    public function Print(Request $request): JsonResponse
    {

        $request->validate([
            'format' => 'required|in:print,pdf',
        ]);

        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;

        $filters = $request->currentFilter ?? [];

        $startDate = !empty($filters['start_date'])
            ? $filters['start_date']
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');

        $endDate = !empty($filters['end_date'])
            ? $filters['end_date']
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');

        $datePeriod = "$startDate to $endDate";

        $result = $this->salesOrderService->salesOrderDetailList(company_id(), financial_year_id(), $filters);

        $tableConfig = [
            "columns" => [
                ["label" => "SO No", "class" => "text-center"],
                ["label" => "SO Date", "class" => "text-start"],
                ["label" => "Customer", "class" => "text-start"],
                ["label" => "Item", "class" => "text-start"],
                ["label" => "Rate", "class" => "text-end"],
                ["label" => "Ord.Qty", "class" => "text-end"],
                ["label" => "Invoice Date", "class" => "text-start"],
                ["label" => "GRN Number", "class" => "text-start"],
                ["label" => "Invoice No", "class" => "text-start"],
                ["label" => "Vehicle", "class" => "text-center"],
                ["label" => "Bags", "class" => "text-end"],
                ["label" => "Bill.Qty", "class" => "text-end"],
                ["label" => "Rem.Qty", "class" => "text-end"],
            ],
            "body" => $result['data']
        ];

        $data = [
            'company' => $company,
            'currentYear' => $currentYear,
            'reportData' => $result['data'],
            'footerData' => $result['footerData'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tableConfig' => $tableConfig,
            'datePeriod' => $datePeriod,
            'orientation' => 'landscape',
        ];

        $html = view('company.pages.sales-order-with-bill.print', $data)->render();

        return AjaxResponse::success(
            message: 'Detail report fetched successfully',
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

            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $filters = $request->currentFilter ?? [];

            $startDate = !empty($filters['start_date'])
                ? $filters['start_date']
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');

            $endDate = !empty($filters['end_date'])
                ? $filters['end_date']
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');

            $datePeriod = "$startDate to $endDate";

            $salesOrdersData = $this->salesOrderService->salesOrderDetailList(company_id(), financial_year_id(), $filters);
            $salesOrders = collect($salesOrdersData['data'] ?? []);
            $footerData = $salesOrdersData['footerData'] ?? [];

            $headings = [
                'So.No',
                'Date',
                'Customer Name',
                'P.O. No.',
                'Product Name',
                'Destination',
                'Rate',
                'Order Qty',
                'Invoice Date',
                'GRN Number',
                'Invoice No',
                'Vehicle No',
                'Bags',
                'P.Qty',
                'Bill.Qty',
                'Rem.Qty',
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_sales_order_with_bill_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new SalesOrderWithBillExport($company, $salesOrders, $headings, $footerData, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Sales Orders with Bill Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\DeliveryChallanService;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\Exports\DeliveryChallanExport;
use Illuminate\Support\Facades\File;

class DeliveryChallanExportController extends Controller
{
    protected DeliveryChallanService $service;
    protected CompanyService $companyService;

    public function __construct(DeliveryChallanService $service, CompanyService $companyService)
    {
        $this->middleware('permission:delivery_challan.print')->only('print');
        $this->middleware('permission:delivery_challan.print-letter')->only('printLetter');

        $this->service = $service;
        $this->companyService = $companyService;
    }

    public function print(Request $request): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];

            $challans = $this->service->deliveryChallanRepo->listAll(company_id(), financial_year_id(), $filters);
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
                    ["label" => "DC No.", "class" => "text-start text-nowrap", 'width' => "6%"],
                    ["label" => "Date", "class" => "text-start", 'width' => "8%"],
                    ["label" => "S.O.No.", "class" => "text-end", 'width' => "5%"],
                    ["label" => "Customer", "class" => "text-start", 'width' => "25%"],
                    ["label" => "Items", "class" => "text-start", 'width' => "15%"],
                    ["label" => "Destination", "class" => "text-start", 'width' => "15%"],
                    ["label" => "Qty.", "class" => "text-end", 'width' => "6%"],
                    ["label" => "P.Qty.", "class" => "text-end", 'width' => "6%"],
                    ["label" => "Rate", "class" => "text-end", 'width' => "6%"],
                    ["label" => "Incl Rate", "class" => "text-end", 'width' => "12%"],
                ],
            ];

            $data = [
                'company' => $company,
                'currentYear' => $currentYear,
                'deliveryChallans' => $challans,
                'tableConfig' => $tableConfig,
                'orientation' => $request->input('orientation', 'landscape'),
                'datePeriod' => $datePeriod,
            ];
            // dd(json_decode(json_encode($challans),true));
            $html = view('company.pages.delivery-challan.print', $data)->render();

            return AjaxResponse::success(
                message: 'Delivery Challan Report generated successfully',
                data: [
                    'html' => $html,
                ]
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.delivery_challan.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Print the Delivery Challan as a Letter.
     */
    public function printLetter(Request $request): JsonResponse
    {
        try {
            $filters = $request->currentFilter ?? [];

            // Handle specific IDs if passed
            if (!empty($request->ids)) {
                $filters['ids'] = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            }

            $challans = $this->service->deliveryChallanRepo->listAll(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());

            $data = [
                'data' => $challans,
                'companyName' => $company->print_name ?? $company->name,
                'companyAddress' => trim(($company->address_one ?? '') . ' ' . ($company->address_two ?? '')),
                'companyPhone' => $company->mobile_number ?? $company->phone_number ?? '',
            ];

            $html = view('company.pages.godown.letter', compact('data'))->render();

            return AjaxResponse::success(
                message: 'Delivery Challan Letter generated successfully',
                data: [
                    'html' => $html,
                ]
            );
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: __('messages.delivery_challan.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
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

            $challans = $this->service->deliveryChallanRepo->listAll(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['start_date'])
                ? $filters['start_date']
                : format_date($currentYear->start_date, 'd-m-Y');

            $endDate = !empty($filters['end_date'])
                ? $filters['end_date']
                : format_date($currentYear->end_date, 'd-m-Y');

            $datePeriod = "$startDate to $endDate";

            $headings = [
                'DC No.',
                'Date',
                'SO No.',
                'Customer',
                'Item',
                'Destination',
                'Condition',
                'Quantity',
                'Party Qty',
                'Rate',
                'Incl Rate',
            ];

            $directory = 'delivery_challan_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_delivery_challan_report_" . now()->format('d_m_Y_His') . ".{$format}";

            Excel::store(
                new DeliveryChallanExport($company, $challans, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Delivery Challan Report Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}
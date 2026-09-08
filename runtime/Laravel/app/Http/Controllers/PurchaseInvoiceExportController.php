<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseInvoiceExport;
use App\Repositories\PurchaseInvoiceRepository;
use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\PurchaseInvoiceService;
use Illuminate\View\View;
use Carbon\Carbon;

class PurchaseInvoiceExportController extends Controller
{
    protected PurchaseInvoiceRepository $repository;
    protected int $companyId;
    protected  $companyService;

    protected $purchaseInvoiceService;


    public function __construct(PurchaseInvoiceRepository $repository, CompanyService $companyService, PurchaseInvoiceService $purchaseInvoiceService)
    {
        $this->middleware('permission:purchase_invoice.print')->only('print');
        $this->middleware('permission:purchase_invoice.export')->only(['exportExcel', 'exportCsv']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->purchaseInvoiceService = $purchaseInvoiceService;
    }

    public function print(Request $request): JsonResponse
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(0);

            $request->validate([
                'format' => 'required|in:print,pdf',
                'orientation' => 'nullable|in:portrait,landscape',
            ]);

            $filters = $request->currentFilter ?? [];
            $purchaseInvoice = $this->purchaseInvoiceService->purchaseInvoiceListAll(company_id(), financial_year_id(), $filters);

            // Pre-sort invoices: account name A→Z, then date earliest first, then reference_number ASC
            $purchaseInvoice = $purchaseInvoice->sort(function ($a, $b) {
                // 1. Account name A → Z
                $nameCompare = strcasecmp(
                    $a->account->name ?? '',
                    $b->account->name ?? ''
                );
                if ($nameCompare !== 0) {
                    return $nameCompare;
                }
                // 2. Invoice date earliest → latest (Y-m-d string compare works correctly)
                $dateCompare = strcmp($a->invoice_date ?? '', $b->invoice_date ?? '');
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }
                // 3. Reference number ASC (natural sort so "2" < "10")
                return strnatcasecmp($a->reference_number ?? '', $b->reference_number ?? '');
            })->values();

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
                    ["label" => "Sr No.", "class" => "text-start", 'width' => "6%"],
                    ["label" => "File No.", "class" => "text-start", 'width' => "6%"],
                    ["label" => "Date", "class" => "text-start", 'width' => "5%"],
                    ["label" => "Bill No.", "class" => "text-end", 'width' => "8%"],
                    ["label" => "Supplier Name", "class" => "text-start", 'width' => "34%"],
                    ["label" => "City", "class" => "text-start", 'width' => "15%"],
                    ["label" => "Destination", "class" => "text-start", 'width' => "22%"],
                    ["label" => "Qty.", "class" => "text-end", 'width' => "10%"],
                    ["label" => "Rate", "class" => "text-end", 'width' => "15%"],
                    ["label" => "Amount", "class" => "text-end ", 'width' => "15%"],
                    ["label" => "C.D.", "class" => "text-end", 'width' => "10%"],
                ],
            ];

            $purchaseRegisterData = [];
            $total_cd = 0;
            $total_rebate = 0;
            foreach ($purchaseInvoice as $data) {
                $accountName = !empty($data->account) ? $data->account->name : '';
                $accountCity = !empty($data->account) ? $data->account->city : '';

                $cdData = $data->billSundries->where('code', 1001)->first();
                $rebateData = $data->billSundries->where('code', 1008)->first();

                $cdValue = $cdData ? abs($cdData->value) : 0;
                $rebateValue = $rebateData ? abs($rebateData->value) : 0;

                $supplier_id = $data->account_id ?? 0;

                $purchaseRegisterData[$supplier_id][] = [
                    'voucher_number' => $data->invoice_serial ?? "",
                    'file_no' => $data->file_number ?? "",
                    'date' => $data->invoice_date ?? "",
                    'other_ref_no' => $data->reference_number ?? "",
                    'sales_inv_no' => $data->sales_invoice_serial ?? "",

                    'account_name' => $accountName ?? "",
                    'account_city' => $accountCity ?? "",

                    'destination_name' => $data->details[0]->destination->name ?? "",
                    'qty' => $data->details[0]->quantity ?? "",
                    'rate' => $data->details[0]->rate ?? "",
                    'net_total' => $data->net_amount ?? 0,
                    'cd' => $cdValue,
                    'rebate' => $rebateValue,
                ];

                for ($i = 1; $i < count($data->details); $i++) {
                    $item = $data->details[$i];
                    $temp = [
                        'voucher_number' => "",
                        'file_no' => "",
                        'date' => "",
                        'other_ref_no' => "",
                        'sales_inv_no' => "",

                        'account_name' => "",
                        'account_city' => "",

                        'destination_name' => $item->destination->name ?? "",
                        'qty' => $item->quantity ?? "",
                        'rate' => $item->rate ?? "",
                        'net_total' => 0,
                        'cd' => 0,
                        'rebate' => 0,
                    ];
                    $purchaseRegisterData[$supplier_id][] = $temp;
                }

                $total_cd += $cdValue;
                $total_rebate += $rebateValue;
            }
            $filter['file_no'] = $filters['file_no'] ?? "";
            $companyName = Company::where('id', company_id())->value('name');
            $html = view('company.pages.purchase-invoice.print', compact('purchaseRegisterData', 'companyName', 'total_cd', 'total_rebate', 'filter'))->render();

            return AjaxResponse::success(
                message: 'Purchase Invoice fetched successfully',
                data: [
                    'html' => $html,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error('Failed to generate print preview: ' . $e->getMessage(), [$e->getMessage()], 500);
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
            ini_set('memory_limit', '512M');
            set_time_limit(0);

            $filters = $request->currentFilter ?? [];

            $purchaseInvoice = $this->purchaseInvoiceService->purchaseInvoiceListAll(company_id(), financial_year_id(), $filters);
            $company = $this->companyService->current(company_id());
            $currentYear = $company->currentFinancialYear;

            $startDate = !empty($filters['start_date'])
                ? $filters['start_date']
                : Carbon::parse($currentYear->start_date)->format('d-m-Y');

            $endDate = !empty($filters['end_date'])
                ? $filters['end_date']
                : Carbon::parse($currentYear->end_date)->format('d-m-Y');

            $datePeriod = "$startDate to $endDate";

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_purchase_invoice_" . now()->format('d_m_Y_His') . ".{$format}";

            // ✅ Use PurchaseInvoiceExport class (handles bill sundries and multi-item rows)
            Excel::store(
                new PurchaseInvoiceExport($company, $purchaseInvoice, [], $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Purchase Invoices Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed: ' . $e->getMessage(), [$e->getMessage()], 500);
        }
    }

    // public function grnPrint(Request $request): View |JsonResponse
    // {
    //     // dd($request->toArray());
    //     $grnId = $request->grnId;
    //     $company = $this->companyService->current(company_id());       
    //     $grnData = $this->purchaseInvoiceService->getEditData(
    //         grnId: $grnId,
    //         companyId: company_id(),
    //         financialYearId: financial_year_id()
    //     );

    //     $companyName = $company->name;

    //     // dd($grnData);
    //     $html = view('company.pages.grn.grn_print_report', compact('grnData', 'companyName'))->render();
    //     return AjaxResponse::success(
    //         message: 'grn print fetched successfully',
    //         data: [
    //             'html' => $html,
    //         ]
    //     );

    // }

}

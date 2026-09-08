<?php

namespace App\Http\Controllers;
use Throwable;
use Illuminate\Http\Request;
use App\Helpers\AjaxResponse;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use App\Services\StockVoucherService;
use App\Repositories\StockVoucherRepository;
class StockExportController extends Controller
{

    protected StockVoucherRepository $repository;
    protected int $companyId;
    protected CompanyService $companyService;
    protected StockVoucherService $stockVoucherService;


    public function __construct(StockVoucherRepository $repository, CompanyService $companyService, StockVoucherService $stockVoucherService)
    {

        $this->middleware('permission:stock_status.print')->only('print');
        $this->middleware('permission:stock_status.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;
        $this->stockVoucherService = $stockVoucherService;

    }

    public function exportItemWise(Request $request):JsonResponse{
        return $this->exportItemWiseFormat($request,'xlsx');
    }
    public function exportItemWiseFormat(Request $request, string $format): JsonResponse{
           try{
                $filters = $request->currentFilter;
                $filters['size'] = 10000; // Get all for export

                $stocksData = $this->stockVoucherService->getStockStatus(company_id(), financial_year_id(),$filters ?? []);
                $stocks = $stocksData['data'] ?? [];
                $company = $this->companyService->current(company_id());
                
                $headings = [
                    'Item Name',
                    'Unit',
                    'Quantity',
                    'Avg Rate',
                    'Amount'
                ];

                $rows = collect($stocks)->map(function($stock) {
                    return [
                        $stock['item_name'],
                        $stock['unit_name'],
                        round($stock['quantity'],3),
                        round($stock['rate'],2),
                        round($stock['amount'],2),
                    ];
                });

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_item_wise_stock_" . now()->format('d_m_Y_His') . ".{$format}";
            
            $dateLabel = !empty($filters['as_at_date'])
                ? 'As At: ' . $filters['as_at_date']
                : 'From: ' . ($filters['start_date'] ?? '') . '  To: ' . ($filters['end_date'] ?? '');

            Excel::store(
                new \App\Exports\StockExport(
                    $company,
                    'company.pages.stock.item-wise-export',
                    $rows,
                    $headings,
                    [
                        'report_title' => 'Item Wise Stock Status',
                        'grandTotal'   => $stocksData['grand_total'] ?? [],
                        'dateLabel'    => $dateLabel,
                    ]
                ),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Stock Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        }catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function exportDateWise(Request $request):JsonResponse{
        return $this->exportDateWiseFormat($request,'xlsx');
    }

    public function exportDateWiseFormat(Request $request, string $format): JsonResponse{
        try {
            // dd($request->currentFilter);
            $itemId = $request->currentFilter['item_id'];
            $startDate = $request->currentFilter['start_date'];
            $endDate = $request->currentFilter['end_date'];

            if (!$itemId || !$startDate || !$endDate) {
                 return AjaxResponse::error('Invalid Request: Missing item or date range.');
            }

            $filters = $request->currentFilter ?? [];
            $filters['size'] = 10000; // Get all for export

            $stocksData = $this->stockVoucherService->dateWiseStockStatus(company_id(), financial_year_id(), $itemId, $startDate, $endDate, $filters);
            $stocks = $stocksData['data'] ?? [];
            $company = $this->companyService->current(company_id());

            $headings = [
                'Date',
                'Voucher Type',
                'Vch No.',
                'Sales Inv No.',
                'Party Name',
                'Qty In',
                'Qty Out',
                'Balance',
            ];

            $rows = collect($stocks)->map(function ($stock) {
                return [
                    format_date($stock['voucher_date']),
                    $stock['voucher_type'],
                    $stock['voucher_bill_no'],
                    $stock['sales_invoice_number'],
                    $stock['supplier_name'],
                    round($stock['quantity_in'],3),
                    round($stock['quantity_out'],3),
                    round($stock['balance'],2),
                ];
            });

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_date_wise_stock_" . now()->format('d_m_Y_His') . ".{$format}";

            $dateLabel = 'From: ' . $startDate . '  To: ' . $endDate;

            Excel::store(
                new \App\Exports\StockExport(
                    $company,
                    'company.pages.stock.date-wise-export',
                    $rows,
                    $headings,
                    [
                        'report_title' => 'Date Wise Stock Status',
                        'openingStock' => $stocksData['opening_stock'] ?? 0,
                        'closingStock' => $stocksData['closing_stock'] ?? 0,
                        'openingAmount'=> $stocksData['opening_amount'] ?? 0,
                        'closingAmount'=> $stocksData['closing_amount'] ?? 0,
                        'itemName'     => $stocksData['item_name'] ?? '',
                        'dateLabel'    => $dateLabel,
                    ]
                ),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Stock Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function exportMonthWise(Request $request):JsonResponse{
        return $this->exportMonthWiseFormat($request,'xlsx');
    }

    public function exportMonthWiseFormat(Request $request, string $format): JsonResponse{
        try {
            $itemId    = $request->currentFilter['item_id'] ?? null;
            $startDate = $request->currentFilter['start_date'] ?? null;
            $endDate   = $request->currentFilter['end_date']   ?? null;
            $asAtDate  = $request->currentFilter['as_at_date'] ?? null;

            if (!$itemId || (!($startDate && $endDate) && !$asAtDate)) {
                return AjaxResponse::error('Invalid Request: Missing item or date range.');
            }

            $filters = $request->currentFilter ?? [];
            $filters['size'] = 10000;

            $stocksData = $this->stockVoucherService->monthWiseStockStatus(company_id(), financial_year_id(), $itemId, $filters);
            $stocks = $stocksData['data'] ?? [];
            $company = $this->companyService->current(company_id());

            $headings = [
                'Month',
                'Qty In',
                'Amount In',
                'Qty Out',
                'Amount Out',
                'Balance Qty',
                'Balance Amount',
            ];

            $rows = collect($stocks)->map(function ($stock) {
                return [
                    $stock['month_name_year'] ?? ($stock['month_name'] ?? ''),
                    round($stock['qty_in'],3),
                    round($stock['amount_in'],2),
                    round($stock['qty_out'],3),
                    round($stock['amount_out'],2),
                    round($stock['balance'],3),
                    round($stock['balance_amount'] ?? 0,2),
                ];
            });

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_month_wise_stock_" . now()->format('d_m_Y_His') . ".{$format}";

            $dateLabel = !empty($filters['as_at_date'])
                ? 'As At: ' . $filters['as_at_date']
                : 'From: ' . ($filters['start_date'] ?? '') . '  To: ' . ($filters['end_date'] ?? '');

            Excel::store(
                new \App\Exports\StockExport(
                    $company,
                    'company.pages.stock.month-wise-export',
                    $rows,
                    $headings,
                    [
                        'report_title' => 'Month Wise Stock Status',
                        'openingStock' => $stocksData['opening_stock'] ?? 0,
                        'closingStock' => $stocksData['closing_stock'] ?? 0,
                        'openingAmount'=> $stocksData['opening_amount'] ?? 0,
                        'closingAmount'=> $stocksData['closing_amount'] ?? 0,
                        'itemName'     => $stocksData['item_name'] ?? '',
                        'dateLabel'    => $dateLabel,
                    ]
                ),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Stock Exported successfully ({$format})", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    // Printing Item Wise Stock Status
    public function printItemWise(Request $request): JsonResponse{
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->currentFilter;
        $filters['size'] = 10000; // Get all for export

        $stocksData = $this->stockVoucherService->getStockStatus(company_id(), financial_year_id(),$filters ?? []);
        $stocks = $stocksData['data'] ?? [];
        $company = $this->companyService->current(company_id());
        $currentYear = $company->currentFinancialYear;
        
        $tableConfig = [
            'columns' => [
                ["label" => "Sr No.",     "class" => "text-start", "width" => "6%"],
                ["label" => "Item Name",  "class" => "text-start", "width" => "30%"],
                ["label" => "Unit",       "class" => "text-start", "width" => "10%"],
                ["label" => "Quantity",   "class" => "text-end",   "width" => "15%"],
                ["label" => "Avg Rate",   "class" => "text-end",   "width" => "15%"],
                ["label" => "Amount",     "class" => "text-end",   "width" => "15%"],
            ],
        ];

        $data = [
            'company' => $company,
            'filters' => $filters,
            'currentYear' => $currentYear,
            'ItemWiseStock'=> $stocks,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];
        $html = view('company.pages.stock.item-wise-print', $data)->render();

        return AjaxResponse::success(
            message: 'Stock Exported Successfully',
            data:['html'=>$html]
        );
    }
    
    public function printMonthWise(Request $request): JsonResponse{
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $itemId   = $request->currentFilter['item_id']    ?? null;
        $startDate = $request->currentFilter['start_date'] ?? null;
        $endDate   = $request->currentFilter['end_date']   ?? null;
        $asAtDate  = $request->currentFilter['as_at_date'] ?? null;

        if (!$itemId || (!($startDate && $endDate) && !$asAtDate)) {
            return AjaxResponse::error('Invalid Request: Missing item or date range.');
        }

        $filters = $request->currentFilter ?? [];
        $filters['size'] = 10000;

        $stocksData = $this->stockVoucherService->monthWiseStockStatus(company_id(), financial_year_id(), $itemId, $filters);
        
        $company = $this->companyService->current(company_id());

        $currentYear = $company->currentFinancialYear;

        $tableConfig = [
            'columns' => [
                ["label" => "Sr No.",      "class" => "text-start", "width" => "6%"],
                ["label" => "Month",       "class" => "text-start", "width" => "16%"],
                ["label" => "Qty In",      "class" => "text-end",   "width" => "11%"],
                ["label" => "Amount In",   "class" => "text-end",   "width" => "11%"],
                ["label" => "Qty Out",     "class" => "text-end",   "width" => "11%"],
                ["label" => "Amount Out",  "class" => "text-end",   "width" => "11%"],
                ["label" => "Balance Qty", "class" => "text-end",   "width" => "11%"],
                ["label" => "Balance Amount", "class" => "text-end","width" => "11%"],
            ],
        ];

        $data = [
            'company' => $company,
            'filters' => $filters,
            'currentYear' => $currentYear,
            'MonthWiseStock'=> $stocksData,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.stock.month-wise-print', $data)->render();

        return AjaxResponse::success(
            message: 'Month Wise Stock Exported Successfully',
            data:['html'=>$html]
        );
    }

    public function printDateWise(Request $request){
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);
        // dd($request->all());
        $itemId = $request->currentFilter['item_id'];
        $startDate = $request->currentFilter['start_date'];
        $endDate = $request->currentFilter['end_date'];

        if (!$itemId || !$startDate || !$endDate) {
            return AjaxResponse::error('Invalid Request: Missing item or date range.');
        }

        $filters = $request->currentFilter ?? [];
        $filters['size'] = 10000; // Get all for export

        $stocksData = $this->stockVoucherService->dateWiseStockStatus(company_id(), financial_year_id(), $itemId, $startDate, $endDate, $filters);

        $company = $this->companyService->current(company_id());

        $currentYear = $company->currentFinancialYear;

        $tableConfig = [
            'columns' => [
                ["label" => "Sr No.",        "class" => "text-start", "width" => "5%"],
                ["label" => "Date",          "class" => "text-start", "width" => "10%"],
                ["label" => "Voucher Type",  "class" => "text-start", "width" => "12%"],
                ["label" => "Vch No.",       "class" => "text-start", "width" => "10%"],
                ["label" => "Sales Inv No.", "class" => "text-start", "width" => "10%"],
                ["label" => "Party Name",    "class" => "text-start", "width" => "28%"],
                ["label" => "Qty In",        "class" => "text-end",   "width" => "9%"],
                ["label" => "Qty Out",       "class" => "text-end",   "width" => "9%"],
                ["label" => "Balance",       "class" => "text-end",   "width" => "9%"],
            ],
        ];

        $data = [
            'company' => $company,
            'filters' => $filters,
            'currentYear' => $currentYear,
            'DateWiseStock'=> $stocksData,
            'tableConfig' => $tableConfig,
            // 'orientation' => $request->input('orientation', 'portrait'),
            'orientation' => 'portrait',
        ];

        $html = view('company.pages.stock.date-wise-print', $data)->render();

        return AjaxResponse::success(
            message: 'Date Wise Stock Exported Successfully',
            data:['html'=>$html]
        );
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Models\Voucher;
use App\Models\VoucherType;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Account;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use App\Exports\ReceiptVoucherRegisterExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use App\Services\CompanyService;



class ReceiptVoucherRegisterExportController extends Controller
{
    protected MasterDataService $masterService;
    protected int $companyId;
    protected  $companyService;

    public function __construct(MasterDataService $masterService, CompanyService $companyService)
    {
        $this->masterService = $masterService;
         $this->companyService = $companyService;
    }


    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters = $request->input('currentFilter', []);

        $company = $this->companyService->current(company_id());
        $financialYearId = financial_year_id();
       
        // Vouchers with Relationship
        $voucherQuery = Voucher::query()
            ->with(['details.account:id,name'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_type_id', VoucherType::RECEIPT);

        $voucherQuery->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('voucher_date', [
                Carbon::parse($filters['start_date'])->format('Y-m-d'),
                Carbon::parse($filters['end_date'])->format('Y-m-d')
            ]);
        });

        $voucherQuery->when(!empty($filters['account_id']), function ($q) use ($filters) {
            $q->whereHas('details', function ($d) use ($filters) {
                $d->where('account_id', $filters['account_id']);
            });
        });

        $vouchers = $voucherQuery->orderBy('voucher_date', 'asc')
            ->orderBy('voucher_serial', 'asc')
            ->get();

        $formattedData = collect();

        // 2. Map Transactions using Native Relationship
        foreach ($vouchers as $voucher) {
            $firstRow = true;

            foreach ($voucher->details as $trx) {
                $formattedData->push([
                    'voucher_id'     => $voucher->id,
                    'voucher_date'   => $firstRow ? $voucher->voucher_date : "",
                    'voucher_number' => $firstRow ? $voucher->voucher_number : "",
                    'particulars'    => $trx->account->name ?? 'N/A',
                    'debit'          => (float) ($trx->debit ?? 0),
                    'credit'         => (float) ($trx->credit ?? 0),
                    'row_type'       => 'transaction',
                ]);
                $firstRow = false;
            }

            if (!empty($filters['narration']) && $filters['narration'] == '1' && !empty($voucher->narration)) {
                $formattedData->push([
                    'voucher_id'     => $voucher->id,
                    'voucher_date'   => "",
                    'voucher_number' => "",
                    'particulars'    => $voucher->narration,
                    'debit'          => 0,
                    'credit'         => 0,
                    'row_type'       => 'narration',
                ]);
            }
        }
    
        // 4. Fetch Account Name for display
        $accountName = null;
        if (!empty($filters['account_id'])) {
            $accountName = Account::where('id', $filters['account_id'])->value('name');
        }

        $tableConfig = [
            "columns" => [
                ["label" => "Date", "class" => "text-start", "width" => "12%"],
                ["label" => "Particulars", "class" => "text-start", "width" => "30%"],
                ["label" => "Voucher No.", "class" => "text-center", "width" => "10%"],
                ["label" => "Debit", "class" => "text-end", "width" => "12%"],
                ["label" => "Credit", "class" => "text-end", "width" => "12%"],
            ],
        ];

        $data = [
            'company'     => $company,
            'tableConfig' => $tableConfig,
            'voucherData' => $formattedData,
            'filters'     => $filters,
            'account'     => $accountName,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.receipt-voucher-register.print', $data)->render();

        return AjaxResponse::success(
            message: 'Receipt Voucher Register printed successfully.',
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

    public function exportByFormat(Request $request, string $format): JsonResponse
    {
        try {
            $filters = $request->input('currentFilter', []);

            $company = $this->companyService->current(company_id());
            $financialYearId = financial_year_id();

            // 1. Query Vouchers with Relationship Eager Loading
            $voucherQuery = Voucher::query()
                ->with(['details.account:id,name'])
                ->where('company_id', $company->id)
                ->where('financial_year_id', $financialYearId)
                ->where('voucher_type_id', VoucherType::RECEIPT);

            $voucherQuery->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereBetween('voucher_date', [
                    Carbon::parse($filters['start_date'])->format('Y-m-d'),
                    Carbon::parse($filters['end_date'])->format('Y-m-d')
                ]);
            });

            $voucherQuery->when(!empty($filters['account_id']), function ($q) use ($filters) {
                $q->whereHas('details', function ($d) use ($filters) {
                    $d->where('account_id', $filters['account_id']);
                });
            });

            $vouchers = $voucherQuery->orderBy('voucher_date', 'asc')
                ->orderBy('voucher_serial', 'asc')
                ->get();

            $formattedData = collect();

            // 2. Map Transactions using Native Relationship
            foreach ($vouchers as $voucher) {
                $firstRow = true;

                foreach ($voucher->details as $trx) {
                    $formattedData->push([
                        'voucher_id'     => $voucher->id,
                        'voucher_date'   => $firstRow ? $voucher->voucher_date : "",
                        'voucher_number' => $firstRow ? $voucher->voucher_number : "",
                        'particulars'    => $trx->account->name ?? 'N/A',
                        'debit'          => (float) ($trx->debit ?? 0),
                        'credit'         => (float) ($trx->credit ?? 0),
                        'row_type'       => 'transaction',
                    ]);
                    $firstRow = false;
                }

                if (!empty($filters['narration']) && $filters['narration'] == '1' && !empty($voucher->narration)) {
                    $formattedData->push([
                        'voucher_id'     => $voucher->id,
                        'voucher_date'   => "",
                        'voucher_number' => "",
                        'particulars'    => $voucher->narration,
                        'debit'          => 0,
                        'credit'         => 0,
                        'row_type'       => 'narration',
                    ]);
                }
            }

        

            // Setup Date Period string
            $startDate = !empty($filters['start_date']) 
                ? Carbon::parse($filters['start_date'])->format('d-m-Y')
                : Carbon::now()->startOfMonth()->format('d-m-Y');
            
            $endDate = !empty($filters['end_date']) 
                ? Carbon::parse($filters['end_date'])->format('d-m-Y')
                : Carbon::now()->format('d-m-Y');
            
            $datePeriod = "$startDate to $endDate";

            $headings = ['Date', 'Particulars', 'Voucher No.', 'Debit', 'Credit'];

            // 3. Setup file generation paths
            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->name, '_');
            $fileName = "{$companyNameSlug}_receipt_voucher_register_" . now()->format('d_m_Y_His') . ".xlsx";

            // 4. Write using Maatwebsite\Excel
            Excel::store(
                new ReceiptVoucherRegisterExport($company, $formattedData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Receipt Voucher Register Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

}
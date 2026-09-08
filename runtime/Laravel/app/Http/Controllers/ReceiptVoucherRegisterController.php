<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use App\Models\Reference;
use App\Models\Company;
use App\Models\Account;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use App\Exports\ReceiptVoucherRegisterExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ReceiptVoucherRegisterController extends Controller
{
    protected MasterDataService $masterService;

    public function __construct(MasterDataService $masterService)
    {
        $this->masterService = $masterService;
    }

    public function index(Request $request)
    {    
        if ($request->ajax()) {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',  
                'narration',                            
            ]);
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);
            
            $companyId = company_id();
            $financialYearId = financial_year_id();

            // 1. Paginate Primary Voucher Records with Relationship Eager Loading
            $voucherQuery = Voucher::query()
                ->with(['details.account:id,name'])
                ->where('company_id', $companyId)
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

            $paginator = $voucherQuery->orderByDesc('voucher_serial')
                ->simplePaginate($filters['size'], ['*'], 'page', $filters['page']);

            $vouchers = collect($paginator->items());
            $flattenedResult = [];
            
            // 2. Transaction Mapping using native Relationship
            foreach ($vouchers as $voucher) {
                foreach ($voucher->details as $trx) {
                    $flattenedResult[] = [
                        'voucher_id'     => $voucher->id,
                        'voucher_date'   => $voucher->voucher_date,
                        'voucher_number' => $voucher->voucher_number,
                        'account_name'   => $trx->account->name ?? 'N/A',
                        'debit'          => (float) ($trx->debit ?? 0),
                        'credit'         => (float) ($trx->credit ?? 0),
                        'row_type'       => 'transaction',
                    ];
                }

                if (!empty($filters['narration']) && $filters['narration'] == '1' && !empty($voucher->narration)) {
                    $flattenedResult[] = [
                        'voucher_id'     => $voucher->id,                        
                        'account_name'   => $voucher->narration,            
                        'row_type'       => 'narration',
                    ];
                }
            }
            
            // system permissions linked to current operational
            $permissions = userPermissions([
                'payment_voucher.view',
                'payment_voucher.update',
                'payment_voucher.delete',
            ], true);

            return response()->json([
                'data'         => $flattenedResult,
                'total'        => $filters['page'] * $filters['size'] + ($paginator->hasMorePages() ? 1 : 0),
                'last_page'    => $paginator->hasMorePages() ? $filters['page'] + 1 : $filters['page'],
                'current_page' => $filters['page'],
                'permissions'  => $permissions
            ]);
        }

        $masterData = $this->masterService->salesOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));
        return view('company.pages.receipt-voucher-register.index', compact('customers'));        
    }

    /**
     * Fetch specific reference settlement allocations linked to a single voucher.
     * Powers dynamic sidebar preview widget.
     */
    public function references(Request $request)
    {       
        $idOrNumber = $request->input('voucher_number');
        
        $voucher = Voucher::with('references')
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where(function($q) use ($idOrNumber) {
                $q->where('id', $idOrNumber);                  
            })
            ->first();        
            
        if (!$voucher) {
            return response()->json([
                'status' => false,
                'message' => 'Voucher not found',
                'data' => []
            ]);
        }

        $allReference = $voucher->references;
        
        $formattedData = $allReference->map(function($alloc) use ($voucher) {
           
            return [
                'voucher_number' => $voucher->voucher_number,
                'ref_no'         => $alloc->reference_number,
                'ref_date'       => $alloc->reference_date,
                'pay_amount'     => (float)$alloc->amount,
                'payment_mode'   => 2,
            ];
        });
        
        return response()->json([
            'status' => 'success',
            'data'   => $formattedData
        ]);
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'customers'    => $masterData['customers'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
        ];
    }
   
    // {
    //     $request->validate([
    //         'format' => 'required|in:print,pdf',
    //         'orientation' => 'nullable|in:portrait,landscape',
    //     ]);

    //     $filters = $request->input('currentFilter', []);

    //     $companyId = company_id();
    //     $financialYearId = financial_year_id();

    //     // Vouchers with Relationship
    //     $voucherQuery = Voucher::query()
    //         ->with(['details.account:id,name'])
    //         ->where('company_id', $companyId)
    //         ->where('financial_year_id', $financialYearId)
    //         ->where('voucher_type_id', VoucherType::RECEIPT);

    //     $voucherQuery->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
    //         $q->whereBetween('voucher_date', [
    //             Carbon::parse($filters['start_date'])->format('Y-m-d'),
    //             Carbon::parse($filters['end_date'])->format('Y-m-d')
    //         ]);
    //     });

    //     $voucherQuery->when(!empty($filters['account_id']), function ($q) use ($filters) {
    //         $q->whereHas('details', function ($d) use ($filters) {
    //             $d->where('account_id', $filters['account_id']);
    //         });
    //     });

    //     $vouchers = $voucherQuery->orderBy('voucher_date', 'asc')
    //         ->orderBy('voucher_serial', 'asc')
    //         ->get();

    //     $formattedData = collect();

    //     // 2. Map Transactions using Native Relationship
    //     foreach ($vouchers as $voucher) {
    //         $firstRow = true;

    //         foreach ($voucher->details as $trx) {
    //             $formattedData->push([
    //                 'voucher_id'     => $voucher->id,
    //                 'voucher_date'   => $firstRow ? $voucher->voucher_date : "",
    //                 'voucher_number' => $firstRow ? $voucher->voucher_number : "",
    //                 'particulars'    => $trx->account->name ?? 'N/A',
    //                 'debit'          => (float) ($trx->debit ?? 0),
    //                 'credit'         => (float) ($trx->credit ?? 0),
    //                 'row_type'       => 'transaction',
    //             ]);
    //             $firstRow = false;
    //         }

    //         if (!empty($filters['narration']) && $filters['narration'] == '1' && !empty($voucher->narration)) {
    //             $formattedData->push([
    //                 'voucher_id'     => $voucher->id,
    //                 'voucher_date'   => "",
    //                 'voucher_number' => "",
    //                 'particulars'    => $voucher->narration,
    //                 'debit'          => 0,
    //                 'credit'         => 0,
    //                 'row_type'       => 'narration',
    //             ]);
    //         }
    //     }

    //     // 3. Fetch Company details
    //     $company = Company::find($companyId);

    //     // 4. Fetch Account Name for display
    //     $accountName = null;
    //     if (!empty($filters['account_id'])) {
    //         $accountName = Account::where('id', $filters['account_id'])->value('name');
    //     }

    //     $tableConfig = [
    //         "columns" => [
    //             ["label" => "Date", "class" => "text-start", "width" => "12%"],
    //             ["label" => "Particulars", "class" => "text-start", "width" => "30%"],
    //             ["label" => "Voucher No.", "class" => "text-center", "width" => "10%"],
    //             ["label" => "Debit", "class" => "text-end", "width" => "12%"],
    //             ["label" => "Credit", "class" => "text-end", "width" => "12%"],
    //         ],
    //     ];

    //     $data = [
    //         'company'     => $company,
    //         'tableConfig' => $tableConfig,
    //         'voucherData' => $formattedData,
    //         'filters'     => $filters,
    //         'account'     => $accountName,
    //         'orientation' => $request->input('orientation', 'portrait'),
    //     ];

    //     $html = view('company.pages.receipt-voucher-register.print', $data)->render();

    //     return AjaxResponse::success(
    //         message: 'Receipt Voucher Register printed successfully.',
    //         data: [
    //             'html' => $html,
    //         ]
    //     );
    // }

    // public function exportExcel(Request $request): JsonResponse
    // {
    //     try {
    //         $filters = $request->input('currentFilter', []);

    //         $companyId = company_id();
    //         $financialYearId = financial_year_id();

    //         // 1. Query Vouchers with Relationship Eager Loading
    //         $voucherQuery = Voucher::query()
    //             ->with(['details.account:id,name'])
    //             ->where('company_id', $companyId)
    //             ->where('financial_year_id', $financialYearId)
    //             ->where('voucher_type_id', VoucherType::RECEIPT);

    //         $voucherQuery->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
    //             $q->whereBetween('voucher_date', [
    //                 Carbon::parse($filters['start_date'])->format('Y-m-d'),
    //                 Carbon::parse($filters['end_date'])->format('Y-m-d')
    //             ]);
    //         });

    //         $voucherQuery->when(!empty($filters['account_id']), function ($q) use ($filters) {
    //             $q->whereHas('details', function ($d) use ($filters) {
    //                 $d->where('account_id', $filters['account_id']);
    //             });
    //         });

    //         $vouchers = $voucherQuery->orderBy('voucher_date', 'asc')
    //             ->orderBy('voucher_serial', 'asc')
    //             ->get();

    //         $formattedData = collect();

    //         // 2. Map Transactions using Native Relationship
    //         foreach ($vouchers as $voucher) {
    //             $firstRow = true;

    //             foreach ($voucher->details as $trx) {
    //                 $formattedData->push([
    //                     'voucher_id'     => $voucher->id,
    //                     'voucher_date'   => $firstRow ? $voucher->voucher_date : "",
    //                     'voucher_number' => $firstRow ? $voucher->voucher_number : "",
    //                     'particulars'    => $trx->account->name ?? 'N/A',
    //                     'debit'          => (float) ($trx->debit ?? 0),
    //                     'credit'         => (float) ($trx->credit ?? 0),
    //                     'row_type'       => 'transaction',
    //                 ]);
    //                 $firstRow = false;
    //             }

    //             if (!empty($filters['narration']) && $filters['narration'] == '1' && !empty($voucher->narration)) {
    //                 $formattedData->push([
    //                     'voucher_id'     => $voucher->id,
    //                     'voucher_date'   => "",
    //                     'voucher_number' => "",
    //                     'particulars'    => $voucher->narration,
    //                     'debit'          => 0,
    //                     'credit'         => 0,
    //                     'row_type'       => 'narration',
    //                 ]);
    //             }
    //         }

    //         // Fetch company model
    //         $company = Company::find($companyId);

    //         // Setup Date Period string
    //         $startDate = !empty($filters['start_date']) 
    //             ? Carbon::parse($filters['start_date'])->format('d-m-Y')
    //             : Carbon::now()->startOfMonth()->format('d-m-Y');
            
    //         $endDate = !empty($filters['end_date']) 
    //             ? Carbon::parse($filters['end_date'])->format('d-m-Y')
    //             : Carbon::now()->format('d-m-Y');
            
    //         $datePeriod = "$startDate to $endDate";

    //         $headings = ['Date', 'Particulars', 'Voucher No.', 'Debit', 'Credit'];

    //         // 3. Setup file generation paths
    //         $directory = 'master_reports';
    //         $directoryPath = storage_path("app/public/{$directory}");

    //         if (!File::exists($directoryPath)) {
    //             File::makeDirectory($directoryPath, 0777, true, true);
    //         }

    //         $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
    //         $fileName = "{$companyNameSlug}_receipt_voucher_register_" . now()->format('d_m_Y_His') . ".xlsx";

    //         // 4. Write using Maatwebsite\Excel
    //         Excel::store(
    //             new ReceiptVoucherRegisterExport($company, $formattedData, $headings, $datePeriod),
    //             "{$directory}/{$fileName}",
    //             'public',
    //             \Maatwebsite\Excel\Excel::XLSX
    //         );

    //         return AjaxResponse::success("Receipt Voucher Register Exported successfully (xlsx)", [
    //             'file_url' => asset("storage/{$directory}/{$fileName}"),
    //             'file_name' => $fileName,
    //         ]);

    //     } catch (Throwable $e) {
    //         return AjaxResponse::error('Export failed', [$e->getMessage()]);
    //     }
    // }

}
<?php

namespace App\Http\Controllers;

use App\Exports\DebitNoteVoucherRegisterExport;
use App\Helpers\AjaxResponse;
use App\Models\Account;
use App\Models\Company;

use App\Rules\ValidFinancialYearDate;
use App\Services\DebitNoteVoucherService;
use App\Services\MasterDataService;
use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DebitNoteVoucherController extends Controller
{
    protected DebitNoteVoucherService $service;
    protected MasterDataService $masterDataService;
    protected CompanyService $companyService;

    public function __construct(MasterDataService $masterDataService, DebitNoteVoucherService $service, CompanyService $companyService)
    {
        // Apply middleware for permissions
        $this->middleware('permission:debit_note_voucher.list')->only(['index']);
        $this->middleware('permission:debit_note_voucher.create')->only(['create', 'store']);
        $this->middleware('permission:debit_note_voucher.update')->only(['edit', 'update']);
        $this->middleware('permission:debit_note_voucher.delete')->only('destroy');
        $this->middleware('permission:debit_note_voucher.restore')->only('restore');

        $this->service = $service;
        // $this->commonRepository = $commonRepository;
        // $this->dataTable = $dataTable;
        $this->masterDataService = $masterDataService;
        $this->companyService = $companyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $page = (int) $request->input('page', 1);
            $size = (int) $request->input('size', 50);

            $request->validate([
                'start_date'  => ['required', 'date_format:Y-m-d', new ValidFinancialYearDate],
                'end_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', new ValidFinancialYearDate],
                'account_id'  => 'nullable|exists:accounts,id',
                'narration'   => 'required|in:0,1',
                'voucher_no'  => 'nullable|exists:vouchers,id',
            ]);


            $result = $this->service->list(
                companyId: company_id(),
                financialYearId: financial_year_id(),
                page: $page,
                size: $size,
                filter: $request->all()
            );

            return response()->json([
                'data' => $result['data'],
                'last_page' => $result['last_page'],
                'permissions' => $result['permissions']
            ]);
        }
        $vouchers = $this->service->voucherSerials(company_id(), financial_year_id());
        $accounts = $this->masterDataService->get('accounts', company_id());
        return view('company.pages.debit-note-voucher.index', compact('accounts', 'vouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $serialInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $serial = $serialInfo->serial;
        $ledgerAccounts = $this->masterDataService->get('accounts', company_id());

        return view('company.pages.debit-note-voucher.create', compact('serial', 'ledgerAccounts'));
    }

    /** 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid|unique:vouchers,uuid',
            'voucher_date' => 'required|date|date_format:Y-m-d',
            new ValidFinancialYearDate,
            'narration' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'rows' => 'required|array|min:1',
        ]);

        try {
            $voucher = $this->service->createVoucher(
                $request->all(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            return AjaxResponse::success(
                message: "Debit Note Voucher Number {$voucher->voucher_serial} has been created successfully.",
                data: $voucher
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.debit_note_voucher.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $voucherId = null;
        if ($request->ajax()) {
            $voucherId = $request->input('voucher_id');
            $data = $this->service->details($voucherId);
            return AjaxResponse::success(
                message: "Debit Note Voucher details has been fetched successfully.",
                data: $data
            );
        }
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $voucherSerials = $this->service->voucherSerials($companyId, $financialYearId);
        $ledgerAccounts = $this->masterDataService->get('accounts', company_id());        return view('company.pages.debit-note-voucher.edit', compact('voucherSerials', 'ledgerAccounts', 'voucherId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ($this->service->isVoucherLocked($id)) {
            return AjaxResponse::error(
                message: 'This voucher cannot be edited because one or more of its references have already been settled by a payment or receipt.',
                code: 422
            );
        }

        $request->validate([
            'voucher_date' => ['required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'narration'    => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'rows'         => 'required|array|min:1',
        ]);

        try {
            $voucher = $this->service->updateVoucher(
                $id,
                $request->all(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            return AjaxResponse::success(
                message: "Debit Note Voucher {$voucher->voucher_serial} has been updated successfully.",
                data: $voucher
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        if ($this->service->isVoucherLocked($id)) {
            return AjaxResponse::error(
                message: 'This voucher cannot be deleted because one or more of its references have already been settled by another payment or receipt.',
                code: 422
            );
        }

        try {
            $this->service->deleteVoucher((int) $id);

            return AjaxResponse::success(message: 'Debit Note Voucher deleted successfully.');
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: $e->getMessage(),
                code: 500
            );
        }
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $filters     = $request->input('currentFilter', []);
        $companyId   = company_id();
        $financialYearId = financial_year_id();

        $rows = $this->service->getVoucherDataForExport($companyId, $financialYearId, $filters);

        $formattedData = collect();
        $prevVoucherId = null;

        foreach ($rows as $row) {
            $isFirst = $prevVoucherId !== $row->voucher_id;
            $prevVoucherId = $row->voucher_id;

            $formattedData->push([
                'voucher_id'     => $row->voucher_id,
                'voucher_date'   => $isFirst ? $row->voucher_date : '',
                'voucher_number' => $isFirst ? $row->voucher_serial : '',
                'particulars'    => $row->account_name,
                'debit'          => (float) ($row->debit ?? 0),
                'credit'         => (float) ($row->credit ?? 0),
                'row_type'       => 'transaction',
            ]);

            if ($isFirst && !empty($filters['narration']) && $filters['narration'] == '1' && !empty($row->full_narration)) {
                // narration row appended after all transaction rows of this voucher in loop below
            }
        }

        // Second pass: inject narration rows after last transaction of each voucher
        if (!empty($filters['narration']) && $filters['narration'] == '1') {
            $withNarration = collect();
            $groups = $rows->groupBy('voucher_id');
            $formattedData = collect();
            $prevId = null;

            foreach ($rows as $row) {
                $isFirst = $prevId !== $row->voucher_id;
                if (!$isFirst) {
                    $prevId = $row->voucher_id;
                    $formattedData->push([
                        'voucher_id'     => $row->voucher_id,
                        'voucher_date'   => '',
                        'voucher_number' => '',
                        'particulars'    => $row->account_name,
                        'debit'          => (float) ($row->debit ?? 0),
                        'credit'         => (float) ($row->credit ?? 0),
                        'row_type'       => 'transaction',
                    ]);
                    continue;
                }

                // first row of a new voucher — flush narration for previous
                if ($prevId !== null) {
                    $prevRow = $rows->firstWhere('voucher_id', $prevId);
                    if ($prevRow && !empty($prevRow->full_narration)) {
                        $formattedData->push([
                            'voucher_id'     => $prevId,
                            'voucher_date'   => '',
                            'voucher_number' => '',
                            'particulars'    => $prevRow->full_narration,
                            'debit'          => 0,
                            'credit'         => 0,
                            'row_type'       => 'narration',
                        ]);
                    }
                }

                $prevId = $row->voucher_id;
                $formattedData->push([
                    'voucher_id'     => $row->voucher_id,
                    'voucher_date'   => $row->voucher_date,
                    'voucher_number' => $row->voucher_serial,
                    'particulars'    => $row->account_name,
                    'debit'          => (float) ($row->debit ?? 0),
                    'credit'         => (float) ($row->credit ?? 0),
                    'row_type'       => 'transaction',
                ]);
            }

            // flush last voucher narration
            if ($prevId !== null) {
                $prevRow = $rows->firstWhere('voucher_id', $prevId);
                if ($prevRow && !empty($prevRow->full_narration)) {
                    $formattedData->push([
                        'voucher_id'     => $prevId,
                        'voucher_date'   => '',
                        'voucher_number' => '',
                        'particulars'    => $prevRow->full_narration,
                        'debit'          => 0,
                        'credit'         => 0,
                        'row_type'       => 'narration',
                    ]);
                }
            }
        }

        $company     = Company::find($companyId);
        $accountName = !empty($filters['account_id'])
            ? Account::where('id', $filters['account_id'])->value('name')
            : null;

        $tableConfig = [
            'columns' => [
                ['label' => 'Date',        'class' => 'text-start', 'width' => '12%'],
                ['label' => 'Particulars', 'class' => 'text-start', 'width' => '38%'],
                ['label' => 'Voucher No.', 'class' => 'text-center', 'width' => '14%'],
                ['label' => 'Debit',       'class' => 'text-end',   'width' => '18%'],
                ['label' => 'Credit',      'class' => 'text-end',   'width' => '18%'],
            ],
        ];

        $html = view('company.pages.debit-note-voucher.print', [
            'company'     => $company,
            'tableConfig' => $tableConfig,
            'voucherData' => $formattedData,
            'filters'     => $filters,
            'account'     => $accountName,
            'orientation' => $request->input('orientation', 'portrait'),
        ])->render();

        return AjaxResponse::success('Debit Note Voucher Register printed successfully.', ['html' => $html]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        try {
            $filters         = $request->input('currentFilter', []);
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $rows = $this->service->getVoucherDataForExport($companyId, $financialYearId, $filters);

            $formattedData = collect();
            $prevId        = null;

            foreach ($rows as $row) {
                $isFirst = $prevId !== $row->voucher_id;

                if ($isFirst && $prevId !== null && !empty($filters['narration']) && $filters['narration'] == '1') {
                    $prevRow = $rows->firstWhere('voucher_id', $prevId);
                    if ($prevRow && !empty($prevRow->full_narration)) {
                        $formattedData->push([
                            'voucher_date'   => '',
                            'voucher_number' => '',
                            'particulars'    => $prevRow->full_narration,
                            'debit'          => 0,
                            'credit'         => 0,
                            'row_type'       => 'narration',
                        ]);
                    }
                }

                $prevId = $row->voucher_id;
                $formattedData->push([
                    'voucher_date'   => $isFirst ? $row->voucher_date : '',
                    'voucher_number' => $isFirst ? $row->voucher_serial : '',
                    'particulars'    => $row->account_name,
                    'debit'          => (float) ($row->debit ?? 0),
                    'credit'         => (float) ($row->credit ?? 0),
                    'row_type'       => 'transaction',
                ]);
            }

            if ($prevId !== null && !empty($filters['narration']) && $filters['narration'] == '1') {
                $prevRow = $rows->firstWhere('voucher_id', $prevId);
                if ($prevRow && !empty($prevRow->full_narration)) {
                    $formattedData->push([
                        'voucher_date'   => '',
                        'voucher_number' => '',
                        'particulars'    => $prevRow->full_narration,
                        'debit'          => 0,
                        'credit'         => 0,
                        'row_type'       => 'narration',
                    ]);
                }
            }

            $company    = Company::find($companyId);
            $startDate  = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->format('d-m-Y') : Carbon::now()->startOfMonth()->format('d-m-Y');
            $endDate    = !empty($filters['end_date'])   ? Carbon::parse($filters['end_date'])->format('d-m-Y')   : Carbon::now()->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
            $headings   = ['Date', 'Particulars', 'Voucher No.', 'Debit', 'Credit'];

            $directory     = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $slug     = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$slug}_debit_note_voucher_register_" . now()->format('d_m_Y_His') . '.xlsx';

            Excel::store(
                new DebitNoteVoucherRegisterExport($company, $formattedData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success('Debit Note Voucher Register exported successfully (xlsx)', [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function details(string $id)
    {
        try {
            
            $data = $this->service->details($id);
            return AjaxResponse::success(
                message: "Debit Note Voucher details has been fetched successfully.",
                data: $data
            );
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.purchase_invoice.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
public function printVoucher(string $id)
    {
        $data = $this->service->details($id);
        $voucher = $data['voucher'];
        $company = $this->companyService->current(company_id());

        $receipt_voucher_data = [];

        $totalDebit = 0;
        $totalCredit = 0;
        $sr = 1;

        $receipt_voucher_data[] = [
            'column1' => 'Sr',
            'column2' => 'Particular',
            'column3' => 'Debit',
            'column4' => 'Credit',
        ];

        foreach ($voucher->details as $detail) {
            $receipt_voucher_data[] = [
                'column1' => $sr++,
                'column2' => $detail->account_name,
                'column3' => $detail->debit > 0 ? formatIndianNumber($detail->debit) : '',
                'column4' => $detail->credit > 0 ? formatIndianNumber($detail->credit) : '',
            ];
            $totalDebit += $detail->debit;
            $totalCredit += $detail->credit;
        }

        $receipt_voucher_data[] = [
            'column1' => 'Total',
            'column2' => formatIndianNumber($totalDebit),
            'column3' => formatIndianNumber($totalCredit),
        ];

        $narration = $voucher->narration;
        $voucher_serial = $voucher->voucher_serial;
        $voucher_date = Carbon::parse($voucher->voucher_date)->format('d-m-Y');

        return view('company.pages.debit-note-voucher.print-journal', compact(
            'company',
            'voucher_serial',
            'voucher_date',
            'receipt_voucher_data',
            'narration'
        ));
    }

    public function printJournal(string $id)
    {
        $data = $this->service->details($id);
        $voucher = $data['voucher'];
        $company = $this->companyService->current(company_id());

        $receipt_voucher_data = [];
        $receipt_voucher_breakup = [];

        $totalDebit = 0;
        $totalCredit = 0;
        $sr = 1;
        $account_name = '';

        $receipt_voucher_data[] = [
            'column1' => 'Sr',
            'column2' => 'Particular',
            'column3' => 'Debit',
            'column4' => 'Credit',
        ];

        foreach ($voucher->details as $detail) {
            $receipt_voucher_data[] = [
                'column1' => $sr++,
                'column2' => $detail->account_name,
                'column3' => $detail->debit > 0 ? formatIndianNumber($detail->debit) : '',
                'column4' => $detail->credit > 0 ? formatIndianNumber($detail->credit) : '',
            ];
            $totalDebit += $detail->debit;
            $totalCredit += $detail->credit;

            if (!empty($detail->refs) && count($detail->refs) > 0) {
                if (empty($account_name)) {
                    $account_name = $detail->account_name;
                }
                if (empty($receipt_voucher_breakup)) {
                    $receipt_voucher_breakup[] = [
                        'column1' => 'Sr.',
                        'column3' => 'Po Num.',
                        'column4' => 'Ref No.',
                        'column5' => 'Ref Date',
                        'column8' => 'Product',
                        'column9' => 'Destination',
                        'column10' => 'Qty',
                        'column11' => 'Amount',
                    ];
                }

                static $breakupSr = 1;
                foreach ($detail->refs as $ref) {
                    $receipt_voucher_breakup[] = [
                        'column1' => $breakupSr++,
                        'column3' => $ref['po_number'] ?? '',
                        'column4' => $ref['ref_number'] ?? '',
                        'column5' => isset($ref['ref_date']) && $ref['ref_date'] ? Carbon::parse($ref['ref_date'])->format('d/m/Y') : '',
                        'column8' => $ref['product'] ?? '',
                        'column9' => $ref['destination'] ?? '',
                        'column10' => $ref['qty'] ?? '',
                        'column11' => formatIndianNumber($ref['ref_amount']),
                    ];
                }
            }
        }

        $receipt_voucher_data[] = [
            'column1' => 'Total',
            'column2' => formatIndianNumber($totalDebit),
            'column3' => formatIndianNumber($totalCredit),
        ];

        $narration = $voucher->narration;
        $voucher_serial = $voucher->voucher_serial;
        $voucher_date = Carbon::parse($voucher->voucher_date)->format('d / m / Y');

        $html = view('company.pages.receipt-voucher.modal-print-receipt', compact(
            'company',
            'voucher_serial',
            'voucher_date',
            'receipt_voucher_data',
            'account_name',
            'receipt_voucher_breakup',
            'narration'
        ))->render();

        return AjaxResponse::success('Receipt Voucher printed successfully.', ['html' => $html]);
    }
}

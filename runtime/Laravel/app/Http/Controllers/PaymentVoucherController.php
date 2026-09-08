<?php

namespace App\Http\Controllers;

use App\Exports\PaymentVoucherRegisterExport;
use App\Helpers\AjaxResponse;
use App\Models\Account;
use App\Models\Company;
use App\Models\PaymentVoucher;
use App\Rules\ValidFinancialYearDate;
use App\Services\ChequeService;
use App\Services\PaymentVoucherService;
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

class PaymentVoucherController extends Controller
{
    protected PaymentVoucherService $service;
    protected MasterDataService $masterDataService;
    protected ChequeService $chequeService;
    protected CompanyService $companyService;

    public function __construct(MasterDataService $masterDataService, PaymentVoucherService $service, ChequeService $chequeService, CompanyService $companyService)
    {
        $this->middleware('permission:payment_voucher.list')->only(['index']);
        $this->middleware('permission:payment_voucher.create')->only(['create', 'store']);
        $this->middleware('permission:payment_voucher.update')->only(['edit', 'update']);
        $this->middleware('permission:payment_voucher.delete')->only('destroy');

        $this->service           = $service;
        $this->masterDataService = $masterDataService;
        $this->chequeService     = $chequeService;
        $this->companyService    = $companyService;
    }

    /*--------------------------------------------------------------
    | INDEX
    --------------------------------------------------------------*/
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $page = (int) $request->input('page', 1);
            $size = (int) $request->input('size', 50);

            $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d', new ValidFinancialYearDate],
                'end_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', new ValidFinancialYearDate],
                'account_id' => 'nullable|exists:accounts,id',
                'narration'  => 'required|in:0,1',
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
                'data'       => $result['data'],
                'last_page'  => $result['last_page'],
                'permissions' => $result['permissions'],
            ]);
        }

        $vouchers = $this->service->voucherSerials(company_id(), financial_year_id());
        $accounts = $this->masterDataService->get('accounts', company_id());
        return view('company.pages.payment-voucher.index', compact('accounts', 'vouchers'));
    }

    /*--------------------------------------------------------------
    | CREATE
    --------------------------------------------------------------*/
    public function create()
    {
        $serialInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $serial         = $serialInfo->serial;
        $ledgerAccounts = $this->masterDataService->get('accounts', company_id());

        return view('company.pages.payment-voucher.create', compact('serial', 'ledgerAccounts'));
    }

    /*--------------------------------------------------------------
    | STORE
    --------------------------------------------------------------*/
    public function store(Request $request)
    {
        $request->validate([
            'uuid'         => 'required|uuid|unique:vouchers,uuid',
            'voucher_date' => 'required|date|date_format:Y-m-d',
            new ValidFinancialYearDate,
            'narration'    => 'nullable|string|max:255',
            'rows'         => 'required|array|min:1',
        ]);

        try {
            $response = $this->service->createVoucher(
                $request->all(),
                companyId: company_id(),
                financialYearId: financial_year_id()
            );
            return AjaxResponse::success(
                message: $response['message'] ?? "Payment Voucher {$response['voucher']->voucher_serial} has been created successfully.",
                data: $response
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: 'Failed to create payment voucher.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /*--------------------------------------------------------------
    | EDIT
    --------------------------------------------------------------*/
    public function edit(Request $request)
    {
        if ($request->ajax()) {
            $voucherId = $request->input('voucher_id');
            $data      = $this->service->details($voucherId);

            return AjaxResponse::success(
                message: "Payment Voucher details has been fetched successfully.",
                data: $data
            );
        }

        $companyId       = company_id();
        $financialYearId = financial_year_id();
        $voucherSerials  = $this->service->voucherSerials($companyId, $financialYearId);
        $ledgerAccounts  = $this->masterDataService->get('accounts', company_id());
        $voucherId       = null;

        return view('company.pages.payment-voucher.edit', compact('voucherSerials', 'ledgerAccounts', 'voucherId'));
    }

    /*--------------------------------------------------------------
    | UPDATE
    --------------------------------------------------------------*/
    public function update(Request $request, string $id)
    {
        if ($this->service->isVoucherLocked($id)) {
            return AjaxResponse::error(
                message: 'This voucher cannot be edited because one or more of its references have already been settled by a payment or receipt.',
                code: 422
            );
        }

        $isPaid = PaymentVoucher::where('voucher_id', $id)->where('is_paid', true)->where('is_voucher_only', false)->first();
        if ($isPaid) {
            return AjaxResponse::error(
                message: 'This voucher cannot be edited because it has already been paid.',
                code: 422
            );
        }

        $isPaymentPayable = PaymentVoucher::where('voucher_id', $id)->where('entry_from', PaymentVoucher::ENTRY_FROM_PAYMENT_PAYABLE)->first();
        if ($isPaymentPayable) {
            return AjaxResponse::error(
                message: 'This voucher cannot be edited because it is being used as a payment payable.',
                code: 422
            );
        }

        $isApproved = PaymentVoucher::where('voucher_id', $id)->where('is_approved', true)->where('is_voucher_only', false)->first();
        if ($isApproved) {
            return AjaxResponse::error(
                message: 'This voucher cannot be edited because it has already been approved.',
                code: 422
            );
        }

        $request->validate([
            'voucher_date' => ['required', 'date', 'date_format:Y-m-d', new ValidFinancialYearDate],
            'narration'    => 'nullable|string|max:255',
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
                message: "Payment Voucher {$voucher->voucher_serial} has been updated successfully.",
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
                message: 'Failed to update payment voucher.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /*--------------------------------------------------------------
    | DESTROY
    --------------------------------------------------------------*/
    public function destroy(string $id)
    {
        if ($this->service->isVoucherLocked($id)) {
            return AjaxResponse::error(
                message: 'This voucher cannot be deleted because one or more of its references have already been settled by another payment or receipt.',
                code: 422
            );
        }

        try {

            $this->service->deleteVoucher([$id]);

            return AjaxResponse::success(message: 'Payment Voucher deleted successfully.');
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: 'Failed to delete payment voucher.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /*--------------------------------------------------------------
    | DETAILS (reference preview)
    --------------------------------------------------------------*/
    public function details(string $id)
    {
        try {
            $data = $this->service->details($id);

            return AjaxResponse::success(
                message: "Payment Voucher details has been fetched successfully.",
                data: $data
            );
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: 'Failed to fetch payment voucher details.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /*--------------------------------------------------------------
    | CHEQUE PRINT
    --------------------------------------------------------------*/
    // public function chequePrint(Request $request)
    // {
    //     $request->validate([
    //         'cheque_name' => 'required|string|max:255',
    //         'cheque_date' => 'required|string',
    //         'amount'      => 'required|numeric|min:0',
    //         'ac_pay'      => 'required|in:Y,N',
    //         'rtgs'        => 'nullable|in:Y,N',
    //         'cheque_no'   => 'nullable|string|max:50',
    //         'bank_name'   => 'nullable|string|max:255',
    //     ]);

    //     $data = $this->chequeService->buildPrintData($request->only([
    //         'cheque_name', 'cheque_date', 'amount', 'ac_pay', 'rtgs', 'cheque_no', 'bank_name',
    //     ]));

    //     return view('company.pages.payment-voucher.cheque_print', $data);
    // }

    /*--------------------------------------------------------------
    | PRINT
    --------------------------------------------------------------*/
    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format'      => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

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

        $html = view('company.pages.payment-voucher.print', [
            'company'     => $company,
            'tableConfig' => $tableConfig,
            'voucherData' => $formattedData,
            'filters'     => $filters,
            'account'     => $accountName,
            'orientation' => $request->input('orientation', 'portrait'),
        ])->render();

        return AjaxResponse::success('Payment Voucher Register printed successfully.', ['html' => $html]);
    }

    public function printVoucher(string $id)
    {
        $data = $this->service->details($id);
        $voucher = $data['voucher'];
        $company = $this->companyService->current(company_id());

        $payment_voucher_data = [];

        $totalDebit = 0;
        $totalCredit = 0;
        $sr = 1;

        $payment_voucher_data[] = [
            'column1' => 'Sr',
            'column2' => 'Particular',
            'column3' => 'Debit',
            'column4' => 'Credit',
        ];

        foreach ($voucher->details as $detail) {
            $payment_voucher_data[] = [
                'column1' => $sr++,
                'column2' => $detail->account_name,
                'column3' => $detail->debit > 0 ? formatIndianNumber($detail->debit) : '',
                'column4' => $detail->credit > 0 ? formatIndianNumber($detail->credit) : '',
            ];
            $totalDebit += $detail->debit;
            $totalCredit += $detail->credit;
        }

        $payment_voucher_data[] = [
            'column1' => 'Total',
            'column2' => formatIndianNumber($totalDebit),
            'column3' => formatIndianNumber($totalCredit),
        ];

        $narration = $voucher->narration;
        $voucher_serial = $voucher->voucher_serial;
        $voucher_date = Carbon::parse($voucher->voucher_date)->format('d-m-Y');

        return view('company.pages.payment-voucher.print-voucher-payment', compact(
            'company',
            'voucher_serial',
            'voucher_date',
            'payment_voucher_data',
            'narration'
        ));
    }

    /*--------------------------------------------------------------
    | EXPORT EXCEL
    --------------------------------------------------------------*/
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
            $fileName = "{$slug}_payment_voucher_register_" . now()->format('d_m_Y_His') . '.xlsx';

            Excel::store(
                new PaymentVoucherRegisterExport($company, $formattedData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success('Payment Voucher Register exported successfully (xlsx)', [
                'file_url'  => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function fetchPendingPurchaseOrders(Request $request)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();
        $filters         = ['account_id' => $request->input('account_id')];

        $pendingOrders = $this->service->fetchPendingPurchaseOrders($companyId, $financialYearId, $filters);

        return AjaxResponse::success('Pending Purchase Orders fetched successfully', $pendingOrders);
    }

    public function printPaymentAdvice(Request $request)
    {
        $validated = $request->validate([
            'payment_voucher_ids'   => 'required|array|min:1',
            'payment_voucher_ids.*' => 'required|integer|exists:payment_vouchers,id',
        ]);

        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $data = $this->service->printPaymentAdvice(
                $validated['payment_voucher_ids'],
                $companyId,
                $financialYearId
            );

            if (empty($data) || empty($data['voucher'])) {
                return AjaxResponse::error('No payment advice data found for the selected records.');
            }

            $html = view('company.pages.payment-online-rtgs.payment-advice-print', compact('data'))->render();

            return AjaxResponse::success('Payment advice prepared.', data: ['html' => $html]);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to prepare payment advice: ' . $e->getMessage());
        }
    }
}

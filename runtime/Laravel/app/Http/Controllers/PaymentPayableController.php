<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\JournalVoucher;
use App\Models\PaymentVoucher;
use App\Models\PurchaseInvoice;
use App\Models\ReceiptVoucher;
use App\Models\Reference;
use App\Models\ReferenceAllocation;
use App\Models\SalesInvoice;
use App\Models\VoucherType;
use App\Services\CompanyService;
use App\Services\MasterDataService;
use App\Services\ReferenceService;
use App\Services\VoucherService;
use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentPayableController extends Controller
{

    protected MasterDataService $masterDataService;
    protected VoucherService $voucherService;
    protected ReferenceService $referenceService;

    public function __construct(MasterDataService $masterDataService, VoucherService $voucherService, ReferenceService $referenceService)
    {
        $this->middleware('permission:payment_payable.list')->only(['index', 'create']);
        $this->middleware('permission:payment_payable.create')->only(['store']);
        $this->middleware('permission:payment_payable.update')->only(['edit', 'update']);
        $this->middleware('permission:payment_payable.delete')->only('destroy');
        $this->middleware('permission:payment_payable.restore')->only('restore');

        $this->masterDataService = $masterDataService;
        $this->voucherService = $voucherService;
        $this->referenceService = $referenceService;
    }



    public function create(Request $request)
    {
        $paymentVoucherSerial = $this->voucherService->getNextVoucherNumber(VoucherType::PAYMENT, company_id(), financial_year_id())->serial;

        $accounts       = $this->masterDataService->getCreditors(company_id());
        $banks          = $this->masterDataService->banks(company_id());
        $ledgerAccounts  = $this->masterDataService->get('accounts', company_id());

        return view('company.pages.payment-payable.index', compact('accounts', 'paymentVoucherSerial', 'banks', 'ledgerAccounts'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'payment_date' => 'required|date_format:Y-m-d',
                'bank_id'      => 'required',
            ], [
                'payment_date.required'    => 'Payment Date is required.',
                'payment_date.date_format' => 'Payment Date must be in YYYY-MM-DD format.',
                'bank_id.required'         => 'Bank / Cash account is required.',
            ]);

            $payment = $request->all();
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $response = $this->referenceService->settlementByPayable($companyId, $financialYearId, $payment);

            if ($response['status'] === true) {
                return AjaxResponse::success(
                    message: $response['message'] ?? "Payment Saved Successfully",
                    data: $response,
                    code: 200
                );
            }

            return AjaxResponse::error(
                message: $response['message'] ?? "Something went wrong",
                code: $response['code'] ?? 400
            );
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'success' => false,
                'message' => $firstError ?? 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: "Failed to process payment settlement. Please try again.",
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
public function pendingPayment(Request $request, CompanyService $companyService)
    {
        try {
            $paymentVouchers = PaymentVoucher::with('account', 'voucher')
                ->whereHas('voucher')
                ->where('company_id', company_id())
                ->where('financial_year_id', financial_year_id())
                ->where('entry_from', PaymentVoucher::ENTRY_FROM_PAYMENT_PAYABLE)
                ->where('is_paid', 0)
                ->get();
            
            $allocations = ReferenceAllocation::whereIn('voucher_id', $paymentVouchers->pluck('voucher_id'))->get();
            $referenceIds = $allocations->pluck('reference_id')->filter()->unique();

            $references = Reference::whereIn('id', $referenceIds)->get()->keyBy('id');

            $sourceTypeMap = [
                'purchase_invoice' => PurchaseInvoice::class,
                'sales_invoice' => SalesInvoice::class,
                'payment' => PaymentVoucher::class,
                'receipt' => ReceiptVoucher::class,
                'journal' => JournalVoucher::class,
            ];

            $sourceModels = [];
            foreach ($references->groupBy('source_type') as $type => $refs) {
                if (!$type || !isset($sourceTypeMap[$type])) continue;
                
                $className = $sourceTypeMap[$type];
                $sourceIds = $refs->pluck('source_id')->toArray();
                
                $query = $className::whereIn('id', $sourceIds);
                
                $relations = [];
                if (method_exists($className, 'details')) {
                    $relations[] = 'details.item';
                    $relations[] = 'details.destination';
                }
                if (method_exists($className, 'billSundries')) {
                    $relations[] = 'billSundries';
                }
                
                if (!empty($relations)) {
                    $query->with($relations);
                }
                
                $sourceModels[$type] = $query->get()->keyBy('id');
            }

            $dateWiseData = [];
            $supplierData = [];
            $dateWiseFileNumber = [];
            $paymentVoucherNumberCollection = [];

            foreach ($paymentVouchers as $pv) {
                $voucherNumber = $pv->voucher->voucher_number ?? $pv->voucher_id;
                $voucherDate = $pv->voucher->voucher_date ?? '';
                $supplierId = $pv->account_id;

                if (!isset($paymentVoucherNumberCollection[$voucherDate][$voucherNumber])) {
                    $paymentVoucherNumberCollection[$voucherDate][$voucherNumber] = 0;
                }

                $paymentVoucherNumberCollection[$voucherDate][$voucherNumber] += $pv->paid_amount;

                if (!isset($supplierData[$supplierId])) {
                    $supplierData[$supplierId] = [
                        'supplier_name' => $pv->account->name ?? '',
                        'city' => $pv->account->city ?? ''
                    ];
                }

                $pvAllocations = $allocations->where('voucher_id', $pv->voucher_id);

                foreach ($pvAllocations as $alloc) {
                    $ref = $references->get($alloc->reference_id);
                    $pi = ($ref && $ref->source_type && isset($sourceModels[$ref->source_type]))
                        ? $sourceModels[$ref->source_type]->get($ref->source_id)
                        : null;

                    $itemData = [
                        'file_no' => $pi->file_number ?? ($ref->file_number ?? ''),
                        'ref_no' => $pi->reference_number ?? $pi->voucher_number ?? ($ref->reference_number ?? ''),
                        'ref_date' => $pi->invoice_date ?? $pi->voucher_date ?? ($ref->reference_date ?? ''),
                        'show_date' => $pi->show_date ?? '',
                        'pay_amount' => $alloc->amount,
                        'cd_percentage' => 0,
                        'cd' => 0,
                        'net_total' => $pi->net_amount ?? '',
                        'rebate' => 0,
                        'destination_name' => '',
                        'product_name' => '',
                    ];

                    if ($pi && method_exists($pi, 'details') && $pi->details && $pi->details->count() > 0) {
                        $firstDetail = $pi->details->first();
                        $itemData['destination_name'] = $firstDetail->destination->name ?? '';
                        $itemData['product_name'] = $firstDetail->item->name ?? '';
                    }

                    if ($pi && method_exists($pi, 'billSundries') && $pi->billSundries) {
                        foreach ($pi->billSundries as $sundry) {
                            if ($sundry->code == '1001') {
                                $itemData['cd_percentage'] = $sundry->rate_percent ?? 0;
                                $itemData['cd'] = abs($sundry->amount ?? 0);
                            } elseif ($sundry->code == '1008') {
                                $itemData['rebate'] = abs($sundry->amount ?? 0);
                            }
                        }
                    }

                    $dateWiseData[$voucherDate][$voucherNumber][$supplierId][] = $itemData;
                    $dateWiseFileNumber[$voucherDate][] = $pv->file_number ?? '';
                }
            }

            $modifyDateWiseFile = [];
            foreach ($dateWiseFileNumber as $date => $values) {
                $modifyDateWiseFile[$date] = implode(', ', array_unique($values));
            }


            ksort($dateWiseData);
            ksort($paymentVoucherNumberCollection);

            $company = $companyService->current();
            $companyDetail = ['company_name' => $company->print_name ?? $company->name ?? ''];
            $fileNo = $request->file_no ?? '';

            return view('company.pages.payment-payable.payment-approval-print', compact(
                'companyDetail',
                'fileNo',
                'dateWiseData',
                'supplierData',
                'modifyDateWiseFile',
                'paymentVoucherNumberCollection'
            ));

        } catch (\Throwable $th) {
            report($th);
            return response()->json([
                'success' => false,
                'message' => "Something went wrong",
                'errors'  => $th->getMessage(),
            ], 500);
        }
    }

    public function holdBill(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:references,id'
        ]);

        try {
            $this->referenceService->holdBills($request->ids);
            return AjaxResponse::success(
                message: "Bills put on hold successfully",
                code: 200
            );
        } catch (\Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: "Something went wrong",
                code: 500,
                errors: $e->getMessage()
            );
        }
    }
}

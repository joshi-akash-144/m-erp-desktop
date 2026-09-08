<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Reference;
use App\Models\VoucherType;
use App\Services\MasterDataService;
use App\Services\ReferenceService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;


class ONACPaymentPayableController extends Controller
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

        return view('company.pages.onac-payment.index', compact('accounts', 'paymentVoucherSerial', 'banks', 'ledgerAccounts'));
    }


    public function getAdvanceList(Request $request)
    {
        $accountId = $request->account_id;
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = Reference::with([
            'account:id,name',
            'purchaseOrder:id,order_serial,order_date,total_quantity',
            'purchaseOrder.details:id,purchase_order_id,ordered_qty,received_qty'
        ])
        ->where('company_id', $companyId)
        ->where('financial_year_id', $financialYearId)
        ->where('reference_type', Reference::Advance)
        ->where('pending_amount', '>', 0)   
        ->whereNotNull('purchase_order_id');

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $references = $query->get()->sort(function ($a, $b) {
            $dateA = $a->reference_date ?? '9999-12-31';
            $dateB = $b->reference_date ?? '9999-12-31';
            
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }
            
            $poA = $a->purchaseOrder ? $a->purchaseOrder->order_serial : $a->purchase_order_number;
            $poB = $b->purchaseOrder ? $b->purchaseOrder->order_serial : $b->purchase_order_number;
            
            return $poA <=> $poB;
        });

        $data = [];
        $totalAmount = 0;

        foreach ($references as $ref) {
            $po = $ref->purchaseOrder;
            $poSerial = $po ? $po->order_serial : ($ref->purchase_order_number ?? '--');
            $poDate =($ref->reference_date ? Carbon::parse($ref->reference_date)->format('d/m/Y') : '');
            
            $qty = 0;
            $recQty = 0;
            
            if ($po) {
                if (isset($po->total_quantity) && $po->total_quantity > 0) {
                    $qty = $po->total_quantity;
                } else if ($po->details) {
                    $qty = $po->details->sum('ordered_qty');
                }
                
                if ($po->details) {
                    $recQty = $po->details->sum('received_qty');
                }
            }
            $remQty = $qty - $recQty;
            
            $amount = $ref->pending_amount > 0 ? $ref->pending_amount : $ref->amount;
            $totalAmount += $amount;

            $data[] = [
                'id' => $ref->id,
                'po_serial' => $poSerial,
                'account_name' => $ref->account->name ?? '',
                'po_date' => $poDate,
                'amount' => $amount,
                'direction' => ucfirst($ref->direction),
                'qty' => number_format($qty, 3, '.', ''),
                'rec_qty' => number_format($recQty, 3, '.', ''),
                'rem_qty' => number_format($remQty, 3, '.', ''),
            ];
        }

        return response()->json([
            'data' => $data,
            'totalAmount' => $totalAmount
        ]);
    }

    public function payables(Request $request, ReferenceService $refService)
    {
        $request->validate([
            'payment_voucher_date'  => 'date_format:Y-m-d',
            'account_id'            => 'nullable|exists:accounts,id',
        ]);

        try {
            $companyId = company_id();
            $financialYearId = financial_year_id();
            
            $advanceData = [];
            $advancePOIds = [];
            // Fetch selected advance records first (if any were chosen in the modal)
            if (!empty($request->reference_ids)) {
                $advanceFilter = [
                    'company_id'            => $companyId,
                    'financial_year_id'     => $financialYearId,
                    'account_id'            => $request->account_id,
                    'file_number'           => $request->file_number ?? null,
                    'on_advance'            => 1,
                    'filter_by'             => 'all', // Bypass DR/CR check, just get the specific selected references
                    'reference_ids'         => $request->reference_ids
                ];
                $advanceData = $refService->getPayable($advanceFilter);

                $advancePOIds = \App\Models\Reference::whereIn('id', $request->reference_ids)
                    ->whereNotNull('purchase_order_id')
                    ->pluck('purchase_order_id')
                    ->unique()
                    ->toArray();
            }

            // Fetch standard new_ref records (Purchase Invoices)
            $normalFilter = [
                'company_id'            => $companyId,
                'financial_year_id'     => $financialYearId,
                'account_id'            => $request->account_id,
                'file_number'           => $request->file_number ?? null,
                'on_advance'            => 0, // must be 0 to restrict to new_ref only
                'filter_by'             => 'all', // 'all' + on_advance=0 → new_ref records only (no advances)
            ];
            
            $normalData = $refService->getPayable($normalFilter);

            if (!empty($request->reference_ids) && !empty($advancePOIds)) {
                $validSourceIds = \App\Models\PurchaseInvoiceItem::whereIn('purchase_order_id', $advancePOIds)
                    ->pluck('purchase_invoice_id')
                    ->unique()
                    ->toArray();

                $validDebitNoteIds = \App\Models\DebitNote::whereIn('purchase_invoice_id', $validSourceIds)
                    ->pluck('id')
                    ->unique()
                    ->toArray();

                $normalData = array_filter($normalData, function ($item) use ($validSourceIds, $validDebitNoteIds) {
                    if ($item['source_type'] === \App\Models\Reference::PurchaseInvoice) {
                        return in_array($item['source_id'], $validSourceIds);
                    } elseif (in_array($item['source_type'], [\App\Models\Reference::PURCHASE_RETURN, \App\Models\Reference::DebitNote])) {
                        return in_array($item['source_id'], $validDebitNoteIds);
                    }
                    return false;
                });

                $normalData = array_values($normalData);
            }

            // Merge them: Advance first, then Normal
            $data = array_merge($advanceData, $normalData);

            $paymentDate = $request->payment_voucher_date ?? date('Y-m-d');
            $yearStartDate = financial_year_start();

            // Load Html of payment payable table based on filter and return
            $html = view('company.pages.payment-payable._transaction', compact('paymentDate','data','yearStartDate'))->render(); 

            return AjaxResponse::success(
                message: "Data Fetched Successfully",
                data: [
                    'reference' => $data,
                    'html' => $html,
                    'unique_request_id' => uuid()
                ],
                code: 200
            );
        } catch (\Throwable $th) {
            return AjaxResponse::error(
                message: $th->getMessage(),
                code: 500
            );
        }
    }


}

<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Account;
use App\Models\Company;
use App\Models\EInvoice;
use App\Models\EWayBill;
use App\Models\GstApiLog; // used by errorLogsJson
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\EInvoiceService;
use App\Services\EwayBillService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class GstPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                has_permission('e_invoice.manage') || has_permission('eway_bill.manage'),
                403
            );
            return $next($request);
        });
    }

    public function index(): View
    {
        $accounts = Account::where('company_id', company_id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('company.pages.gst-portal.index', compact('accounts'));
    }

    /*==========================================================================
    | E-Invoice listing
    ==========================================================================*/

    public function eInvoiceList(Request $request): JsonResponse
    {
        abort_unless(has_permission('e_invoice.manage'), 403);

        $page = (int) $request->input('page', 1);
        $size = (int) $request->input('size', 50);

        $query = SalesInvoice::query()
            ->select([
                'sales_invoices.id',
                'sales_invoices.reference_number',
                'sales_invoices.invoice_date',
                'sales_invoices.grand_total',
                'accounts.name as account_name',
                'e_invoices.id as e_invoice_id',
                'e_invoices.irn',
                'e_invoices.ack_no',
                'e_invoices.ack_dt',
                'e_invoices.doc_type',
                'e_invoices.status as e_invoice_status',
                'e_invoices.cancel_reason_code',
                'e_invoices.cancelled_at',
            ])
            ->join('accounts', 'accounts.id', '=', 'sales_invoices.account_id')
            ->leftJoin('e_invoices', 'e_invoices.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.company_id', company_id())
            ->where('sales_invoices.financial_year_id', financial_year_id())
            ->orderByDesc('sales_invoices.invoice_date');

        $grandTotal = (clone $query)->count();

        $this->applyCommonFilters($query, $request, 'e_invoices');

        $filteredTotal = (clone $query)->count();
        $paginator = $query->simplePaginate($size, ['*'], 'page', $page);

        return response()->json([
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $size),
            'current_page' => $page,
            'grand_total'  => $grandTotal,
        ]);
    }

    /*==========================================================================
    | E-Way Bill listing
    ==========================================================================*/

    public function eWayBillList(Request $request): JsonResponse
    {
        abort_unless(has_permission('eway_bill.manage'), 403);

        $page = (int) $request->input('page', 1);
        $size = (int) $request->input('size', 50);

        $query = SalesInvoice::query()
            ->select([
                'sales_invoices.id',
                'sales_invoices.reference_number',
                'sales_invoices.invoice_date',
                'sales_invoices.grand_total',
                'accounts.name as account_name',
                'e_way_bills.id as e_way_bill_id',
                'e_way_bills.ewb_no',
                'e_way_bills.ewb_date',
                'e_way_bills.valid_upto',
                'e_way_bills.vehicle_no',
                'e_way_bills.trans_mode',
                'e_way_bills.status as e_way_bill_status',
                'e_way_bills.e_invoice_id as linked_e_invoice_id',
            ])
            ->join('accounts', 'accounts.id', '=', 'sales_invoices.account_id')
            ->leftJoin('e_way_bills', 'e_way_bills.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.company_id', company_id())
            ->where('sales_invoices.financial_year_id', financial_year_id())
            ->orderByDesc('sales_invoices.invoice_date');

        $grandTotal = (clone $query)->count();

        $this->applyCommonFilters($query, $request, 'e_way_bills');

        $filteredTotal = (clone $query)->count();
        $paginator = $query->simplePaginate($size, ['*'], 'page', $page);

        return response()->json([
            'data'         => $paginator->items(),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $size),
            'current_page' => $page,
            'grand_total'  => $grandTotal,
        ]);
    }

    /*==========================================================================
    | Generate — unified E-WayBill / E-Invoice listing
    ==========================================================================*/

    public function generate(): View
    {
        $accounts = Account::where('company_id', company_id())->where('party_type', 'customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        $items = Item::where('company_id', company_id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('company.pages.gst-portal.generate', compact('accounts', 'items'));
    }

    public function salesBillsJson(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $size = (int) $request->input('size', 50);

        $query = SalesInvoice::query()
            ->select([
                'sales_invoices.id',
                'sales_invoices.reference_number',
                'sales_invoices.grn_number',
                'sales_invoices.invoice_date',
                'sales_invoices.last_invoice_date',
                'sales_invoices.vehicle_number',
                'sales_invoices.kms',
                'sales_invoices.grand_total',
                'sales_invoices.taxable_amount',
                'sales_invoices.gst_type',
                'accounts.name as customer_name',
                // EWayBill (active only)
                'ewb.id as ewb_id',
                'ewb.ewb_no',
                'ewb.status as ewb_status',
                // EInvoice (active only)
                'ei.id as ei_id',
                'ei.irn',
                'ei.ack_no',
                'ei.status as irn_status',
            ])
            ->join('accounts', 'accounts.id', '=', 'sales_invoices.account_id')
            ->leftJoin('e_way_bills as ewb', function ($join) {
                $join->on('ewb.sales_invoice_id', '=', 'sales_invoices.id')
                     ->where('ewb.status', EWayBill::STATUS_ACTIVE);
            })
            ->leftJoin('e_invoices as ei', function ($join) {
                $join->on('ei.sales_invoice_id', '=', 'sales_invoices.id')
                     ->where('ei.status', EInvoice::STATUS_ACTIVE);
            })
            ->where('sales_invoices.company_id', company_id())
            ->where('sales_invoices.financial_year_id', financial_year_id())
            ->whereNull('sales_invoices.deleted_at')
            ->orderByDesc('sales_invoices.invoice_date')
            ->orderByDesc('sales_invoices.id');

        $grandTotal = (clone $query)->toBase()->count();

        // Filters
        if ($date = $request->input('date')) {
            // Accept DD-MM-YYYY or YYYY-MM-DD
            try {
                $parsed = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
            } catch (\Throwable) {
                $parsed = $date;
            }
            $query->where('sales_invoices.invoice_date', $parsed);
        }
        if ($invNo = $request->input('inv_no')) {
            $query->where('sales_invoices.reference_number', 'like', "%{$invNo}%");
        }
        if ($grn = $request->input('grn')) {
            $query->where('sales_invoices.grn_number', 'like', "%{$grn}%");
        }
        if ($accountId = $request->input('account_id')) {
            $query->where('sales_invoices.account_id', $accountId);
        }
        if ($itemId = $request->input('item_id')) {
            $query->whereExists(function ($sub) use ($itemId) {
                $sub->select(DB::raw(1))
                    ->from('sales_invoice_items')
                    ->whereColumn('sales_invoice_items.sales_invoice_id', 'sales_invoices.id')
                    ->where('sales_invoice_items.item_id', $itemId);
            });
        }
        if ($status = $request->input('status')) {
            match ($status) {
                'both_pending'  => $query->whereNull('ewb.id')->whereNull('ei.id'),
                'ewb_pending'   => $query->whereNull('ewb.id'),
                'irn_pending'   => $query->whereNull('ei.id'),
                'ewb_generated' => $query->whereNotNull('ewb.id'),
                'irn_generated' => $query->whereNotNull('ei.id'),
                default         => null,
            };
        }

        // toBase() returns stdClass rows (not Eloquent models) so aliased join columns are accessible
        $filteredTotal = (clone $query)->toBase()->count();
        $paginator     = $query->toBase()->simplePaginate($size, ['*'], 'page', $page);
        $rows          = $paginator->items();

        // Enrich with first product name per invoice
        $invoiceIds = array_map(fn($r) => $r->id, $rows);
        $products = [];
        if ($invoiceIds) {
            $products = SalesInvoiceItem::whereIn('sales_invoice_items.sales_invoice_id', $invoiceIds)
                ->join('items', 'items.id', '=', 'sales_invoice_items.item_id')
                ->selectRaw('MIN(items.name) as product_name, sales_invoice_items.sales_invoice_id')
                ->groupBy('sales_invoice_items.sales_invoice_id')
                ->get()
                ->keyBy('sales_invoice_id')
                ->toArray();
        }

        $data = array_map(function ($row) use ($products) {
            $arr = (array) $row;
            $arr['product_name'] = $products[$row->id]['product_name'] ?? '—';
            return $arr;
        }, $rows);

        return response()->json([
            'data'         => array_values($data),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $size),
            'current_page' => $page,
            'grand_total'  => $grandTotal,
        ]);
    }

    public function bulkGenerate(Request $request): JsonResponse
    {
        // Allow long-running batch processing (200-300 invoices × 2 API calls each)
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.id'               => 'required|integer',
            'items.*.type'             => 'required|in:both,ewb,irn',
            'transport'                => 'nullable|array',
            'transport.trans_mode'     => 'nullable|string|in:1,2,3,4',
            'transport.vehicle_type'   => 'nullable|string|in:R,O',
            'transport.vehicle_no'     => 'nullable|string|max:15',
            'transport.distance'       => 'nullable|integer|min:0',
            'transport.transporter_id' => 'nullable|string|max:15',
            'transport.trans_doc_no'   => 'nullable|string|max:15',
            'transport.trans_doc_date' => 'nullable|string',
        ]);

        $transport = $validated['transport'] ?? [];
        $results   = [];

        $company = Company::with('state')->find(company_id());

        foreach ($validated['items'] as $item) {
            // Each invoice is independently isolated — an error on one NEVER stops others
            $invoiceId = (int) $item['id'];
            $type      = $item['type'];

            $result = [
                'id'      => $invoiceId,
                'ref'     => '',
                'type'    => $type,
                'success' => false,
                'message' => '',
            ];

            try {
                $invoice = SalesInvoice::with([
                    'account',
                    'account.preference:id,account_id,distance',
                    'account.taxDetail:id,gst_number,account_id',
                    'account.state',
                    'details',
                    'details.item:id,name,unit_id,hsn_sac_code',
                    'details.item.unit:id,name,uqc',
                    'billSundries'
                ])->where('company_id', company_id())
                  ->whereNull('deleted_at')
                  ->find($invoiceId);

                if (!$invoice) {
                    $result['message'] = "Invoice #{$invoiceId} not found or does not belong to this company.";
                    $results[] = $result;
                    continue;
                }

                $result['ref'] = $invoice->reference_number;

                // Tracks the active EInvoice record for this invoice (existing or newly created).
                // Used by the EWB section to generate from IRN per government mandate.
                $activeEInvoice = null;

                // ── E-Invoice (IRN) ──────────────────────────────────────────
                if (in_array($type, ['irn', 'both'])) {
                    $existingIrn = EInvoice::where('sales_invoice_id', $invoice->id)
                        ->where('status', EInvoice::STATUS_ACTIVE)
                        ->first();

                    if ($existingIrn) {
                        $activeEInvoice        = $existingIrn;
                        $result['irn']         = $existingIrn->irn;
                        $result['ack_no']      = $existingIrn->ack_no;
                        $result['irn_skipped'] = true;
                    } else {
                        $irnPayload  = $this->buildIrnPayload($invoice, $company);
                        
                        $einvService = app(EInvoiceService::class);
                        $apiRes      = $einvService->generateIrn($irnPayload);

                        $data = $apiRes['data'] ?? $apiRes;
                        $irn  = $data['Irn'] ?? $data['irn'] ?? '';

                        if (empty($irn)) {
                            $apiErr = $data['message'] ?? $data['Message'] ?? $data['error'] ?? $data['Error'] ?? 'No IRN returned from API';
                            throw new \RuntimeException("IRN failed: {$apiErr}");
                        }

                        $activeEInvoice = EInvoice::create([
                            'company_id'       => company_id(),
                            'sales_invoice_id' => $invoice->id,
                            'irn'              => $irn,
                            'ack_no'           => $data['AckNo'] ?? $data['ackNo'] ?? '',
                            'ack_dt'           => $data['AckDt'] ?? $data['ackDt'] ?? '',
                            'doc_type'         => EInvoice::DOC_TYPE_INVOICE,
                            'doc_no'           => $irnPayload['DocDtls']['No'],
                            'doc_date'         => $irnPayload['DocDtls']['Dt'],
                            'signed_qr_code'   => $data['SignedQRCode'] ?? $data['signedQRCode'] ?? '',
                            'status'           => EInvoice::STATUS_ACTIVE,
                            'environment'      => app()->environment('production') ? 'production' : 'sandbox',
                            'created_by'       => current_user()?->id,
                        ]);

                        $result['irn']    = $irn;
                        $result['ack_no'] = $data['AckNo'] ?? $data['ackNo'] ?? '';
                    }
                }

                // ── E-Way Bill ───────────────────────────────────────────────
                if (in_array($type, ['ewb', 'both'])) {
                    $existingEwb = EWayBill::where('sales_invoice_id', $invoice->id)
                        ->where('status', EWayBill::STATUS_ACTIVE)
                        ->first();

                    if ($existingEwb) {
                        $result['ewb_no']      = $existingEwb->ewb_no;
                        $result['ewb_skipped'] = true;
                    } else {
                        // For type=ewb, the IRN section didn't run — look up any existing active IRN
                        if ($type === 'ewb' && !$activeEInvoice) {
                            $activeEInvoice = EInvoice::where('sales_invoice_id', $invoice->id)
                                ->where('status', EInvoice::STATUS_ACTIVE)
                                ->first();
                        }

                        if ($activeEInvoice) {
                            // Government rule: when an IRN exists, EWB must be generated from it
                            $einvService = app(EInvoiceService::class);
                            $apiRes      = $einvService->generateEwayBillFromIrn(
                                $activeEInvoice->irn,
                                '1',
                                null,
                                'SELF',
                                $activeEInvoice->doc_no   ?? '',
                                $activeEInvoice->doc_date ? $activeEInvoice->doc_date : '',
                                $invoice->vehicle_number,
                                'R',
                                $invoice->account->preference->distance ?? 0    

                            );
                        } else {
                            // No IRN for this invoice — generate EWB independently
                            $ewbPayload = $this->buildEwbPayload($invoice, $company, $transport);
                            $ewbService = app(EwayBillService::class);
                            $apiRes     = $ewbService->generateEwayBill($ewbPayload);
                        }
                        $data  = $apiRes['data'] ?? $apiRes;
                        $ewbNo = (string) ($data['ewbNo'] ?? $data['EwbNo'] ?? '');

                        if (empty($ewbNo)) {
                            $apiErr = $data['message'] ?? $data['Message'] ?? $data['error'] ?? $data['Error'] ?? 'No EWB No returned from API';
                            throw new \RuntimeException("EWB failed: {$apiErr}");
                        }

                        EWayBill::create([
                            'company_id'       => company_id(),
                            'sales_invoice_id' => $invoice->id,
                            'e_invoice_id'     => $activeEInvoice?->id,
                            'ewb_no'           => $ewbNo,
                            'ewb_date'         => $data['EwbDt']   ?? $data['EwbDate']   ?? null,
                            'valid_upto'       => $data['EwbValidTill'] ?? $data['ValidUpto'] ?? null,
                            'trans_mode'       => $transport['trans_mode'] ?? '1',
                            'vehicle_no'       => strtoupper($transport['vehicle_no'] ?? $invoice->vehicle_number ?? ''),
                            'vehicle_type'     => $transport['vehicle_type'] ?? 'R',
                            'status'           => EWayBill::STATUS_ACTIVE,
                            'environment'      => app()->environment('production') ? 'production' : 'sandbox',
                            'created_by'       => current_user()?->id,
                        ]);

                        $result['ewb_no'] = $ewbNo;
                    }
                }

                $result['success'] = true;
                $result['message'] = 'Generated successfully.';

            } catch (Throwable $e) {
                $result['success'] = false;
                $result['message'] = $e->getMessage();
                // Continue to next invoice — do NOT re-throw
            }

            $results[] = $result;
        }

        $succeeded = count(array_filter($results, fn($r) => $r['success']));
        $failed    = count($results) - $succeeded;

        return AjaxResponse::success(
            "{$succeeded} generated" . ($failed > 0 ? ", {$failed} failed." : "."),
            ['results' => $results]
        );
    }

    public function refetchIrn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer',
        ]);

        try {
            $invoice = SalesInvoice::where('company_id', company_id())
                ->whereNull('deleted_at')
                ->findOrFail($validated['invoice_id']);

            $existingIrn = EInvoice::where('sales_invoice_id', $invoice->id)
                ->where('status', EInvoice::STATUS_ACTIVE)
                ->first();

            if ($existingIrn) {
                return AjaxResponse::error('Active IRN already exists for this invoice.');
            }

            $fyStart = financial_year_start();
            $year    = (int) date('Y', strtotime($fyStart));
            $fyShort = substr($year, -2) . substr($year + 1, -2);
            $docNo = 'C' . $invoice->company_id . $fyShort . 'S' . $invoice->invoice_serial;
            $docDate = Carbon::parse($invoice->invoice_date)->format('d/m/Y');

            $einvService = app(EInvoiceService::class);
            $apiRes      = $einvService->getIrnByDocDetails('INV', $docNo, $docDate);
            $data        = $apiRes['data'] ?? $apiRes;

            $irn = $data['Irn'] ?? $data['irn'] ?? '';

            if (empty($irn)) {
                return AjaxResponse::error('IRN not found on GST Portal.');
            }

            EInvoice::create([
                'company_id'       => company_id(),
                'sales_invoice_id' => $invoice->id,
                'irn'              => $irn,
                'ack_no'           => $data['AckNo'] ?? $data['ackNo'] ?? '',
                'ack_dt'           => $data['AckDt'] ?? $data['ackDt'] ?? '',
                'doc_type'         => EInvoice::DOC_TYPE_INVOICE,
                'doc_no'           => $docNo,
                'doc_date'         => $docDate,
                'signed_qr_code'   => $data['SignedQRCode'] ?? $data['signedQRCode'] ?? '',
                'status'           => EInvoice::STATUS_ACTIVE,
                'environment'      => app()->environment('production') ? 'production' : 'sandbox',
                'created_by'       => current_user()?->id,
            ]);

            return AjaxResponse::success('IRN refetched and saved successfully.');
        } catch (\Throwable $e) {
            return AjaxResponse::error($e->getMessage());
        }
    }

    /*==========================================================================
    | Payload builders
    ==========================================================================*/

    private function buildIrnPayload(SalesInvoice $invoice, Company $company): array
    {
        $account      = $invoice->account;
        $taxDetail    = $account->taxDetail;
        $isInterstate = $invoice->gst_type === SalesInvoice::TAX_INTERSTATE;
        $companyGstin = $company->gst_number ?? '';
        $buyerGstin   = $taxDetail->gst_number ?? 'URP';
        $companyState = (string) ($company->state->gst_code ?? '24');
        $buyerState   = (string) ($account->state->gst_code ?? '24');
        $sellerDetails = [];
        if (!app()->environment('production')) {
            $sellerDetails = [
                'Gstin' => '29AAGCB1286Q000',
                'LglNm' => 'ABC company pvt ltd',
                'TrdNm' => 'NIC Industries',
                'Addr1' => '5th block, kuvempu layout',
                'Addr2' => 'kuvempu layout',
                'Loc'   => 'GANDHINAGAR',
                'Pin'   => 560001,
                'Stcd'  => '29',
                'Ph'    => '9000000000',
                'Em'    => 'abc@gmail.com',
            ];
        } else {
            $sellerDetails = [
                'Gstin' => $companyGstin,
                'LglNm' => $company->legal_name ?? $company->name,
                'TrdNm' => $company->name,
                'Addr1' => $company->address_one ?? '',
                'Addr2' => $company->address_two ?? '',
                'Loc'   => $company->address_one ?? '',
                'Pin'   => (int) ($company->postal_code ?? 0),
                'Stcd'  => $companyState,
                'Ph'    => $company->mobile_number ?? '',
                'Em'    => $company->email ?? '',
            ];
        }

        $items = [];
        $slNo  = 1;
        foreach ($invoice->details as $detail) {
            $item    = $detail->item;
            $unit    = optional($item->unit);
            $gstRate = $isInterstate
                ? (float) $detail->igst_rate
                : ((float) $detail->cgst_rate + (float) $detail->sgst_rate);

            $items[] = [
                'SlNo'               => (string) $slNo++,
                'PrdDesc'            => $item->name,
                'IsServc'            => 'N',
                'HsnCd'              => $item->hsn_sac_code ?? '',
                'Qty'                => (float) $detail->quantity,
                'FreeQty'            => 0,
                'Unit'               => $unit->uqc ?? $unit->name ?? 'NOS',
                'UnitPrice'          => (float) $detail->rate,
                'TotAmt'             => (float) $detail->amount,
                'Discount'           => 0,
                'PreTaxVal'          => 0,
                'AssAmt'             => (float) $detail->amount,
                'GstRt'              => $gstRate,
                'IgstAmt'            => (float) $detail->igst_amount,
                'CgstAmt'            => (float) $detail->cgst_amount,
                'SgstAmt'            => (float) $detail->sgst_amount,
                'CesRt'              => 0,
                'CesAmt'             => 0,
                'CesNonAdvolAmt'     => 0,
                'StateCesRt'         => 0,
                'StateCesAmt'        => 0,
                'StateCesNonAdvolAmt'=> 0,
                'OthChrg'            => 0,
                'TotItemVal'         => (float) $detail->net_amount,
            ];
        }

        $details = $invoice->details;
        $fyStart = financial_year_start();
        $year    = (int) date('Y', strtotime($fyStart));

        $fyShort = substr($year, -2) . substr($year + 1, -2);

        $docNo = 'C' . $invoice->company_id . $fyShort . 'S' . $invoice->invoice_serial;

        $assVal    = round((float) $details->sum('amount'), 2);
        $cgstVal   = round((float) $details->sum('cgst_amount'), 2);
        $sgstVal   = round((float) $details->sum('sgst_amount'), 2);
        $igstVal   = round((float) $details->sum('igst_amount'), 2);
        $totInvVal = round((float) $invoice->grand_total, 2);
        // OthChrg = residual so NIC formula (AssVal + taxes + OthChrg = TotInvVal) always balances.
        // Avoids double-counting CGST/SGST bill sundries that are already in cgstVal/sgstVal.
        $othChrg   = max(0, round($totInvVal - $assVal - $cgstVal - $sgstVal - $igstVal, 2));

        return [
            'Version'  => '1.1',
            'TranDtls' => [
                'TaxSch'      => 'GST',
                'SupTyp'      => 'B2B',
                'RegRev'      => 'N',
                'IgstOnIntra' => 'N',
                'EcmGstin'    => null,
            ],
            'DocDtls'  => [
                'Typ' => 'INV',
                'No'  => $docNo,
                'Dt'  => Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
            ],
            'SellerDtls' => $sellerDetails,
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => $account->name,
                'TrdNm' => $account->name ?? $account->print_name,
                'Pos'   => $buyerState,
                'Addr1' => $account->address_one ?? '',
                'Addr2' => $account->address_two ?? '',
                'Loc'   => $account->city ?? '',
                'Pin'   => (int) ($account->postal_code ?? 0),
                'Stcd'  => $buyerState,
                'Ph'    => $account->mobile_number ?? '',
                'Em'    => $account->email ?? '',
            ],
            'ItemList' => $items,
            'ValDtls'  => [
                'AssVal'    => $assVal,
                'CgstVal'   => $cgstVal,
                'SgstVal'   => $sgstVal,
                'IgstVal'   => $igstVal,
                'CesVal'    => 0,
                'StCesVal'  => 0,
                'Discount'  => 0,
                'OthChrg'   => $othChrg,
                'RndOffAmt' => 0,
                'TotInvVal' => $totInvVal,
            ],
        ];
    }

    private function buildEwbPayload(SalesInvoice $invoice, Company $company, array $transport): array
    {
        $account      = $invoice->account;
        $taxDetail    = $account->taxDetail;
        $companyGstin = $company->gst_number ?? '';
        $buyerGstin   = $taxDetail->gst_number ?? 'URP';
        $companyState = (int) ($company->state->gst_code ?? 24);
        $buyerState   = (int) ($account->state->gst_code ?? 24);

        $items = [];
        $slNo  = 1;
        foreach ($invoice->details as $detail) {
            $item = $detail->item;
            $unit = optional($item->unit);

            $items[] = [
                'itemNo'        => $slNo++,
                'productName'   => $item->name ?? 'Product',
                'productDesc'   => $item->name ?? 'Product',
                'hsnCode'       => $item->hsn_sac_code ?? '',
                'quantity'      => (float) $detail->quantity,
                'qtyUnit'       => $unit->code ?? $unit->name ?? 'NOS',
                'cgstRate'      => (float) $detail->cgst_rate,
                'sgstRate'      => (float) $detail->sgst_rate,
                'igstRate'      => (float) $detail->igst_rate,
                'cessRate'      => 0,
                'cessAdvol'     => 0,
                'taxableAmount' => (float) $detail->taxable_amount,
            ];
        }

        $details = $invoice->details;
        $vehicleNo = strtoupper($transport['vehicle_no'] ?? $invoice->vehicle_number ?? '');

        return [
            'supplyType'            => 'O',
            'subSupplyType'         => '1',
            'subSupplyDesc'         => '',
            'docType'               => 'INV',
            'docNo'                 => $invoice->reference_number,
            'docDate'               => Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
            'fromGstin'             => $companyGstin,
            'fromTrdName'           => $company->name,
            'fromAddr1'             => $company->address_one ?? '',
            'fromAddr2'             => $company->address_two ?? '',
            'fromPlace'             => $company->address_one ?? '',
            'fromPincode'           => 0,
            'fromStateCode'         => $companyState,
            'toGstin'               => $buyerGstin,
            'toTrdName'             => $account->name,
            'toAddr1'               => $account->address_one ?? '',
            'toAddr2'               => $account->address_two ?? '',
            'toPlace'               => $account->city ?? '',
            'toPincode'             => (int) ($account->postal_code ?? 0),
            'toStateCode'           => $buyerState,
            'transactionType'       => 1,
            'dispatchFromGSTIN'     => $companyGstin,
            'dispatchFromTradeName' => $company->name,
            'shipToGSTIN'           => $buyerGstin,
            'shipToTradeName'       => $account->name,
            'totalValue'            => (float) $details->sum('taxable_amount'),
            'cgstValue'             => (float) $details->sum('cgst_amount'),
            'sgstValue'             => (float) $details->sum('sgst_amount'),
            'igstValue'             => (float) $details->sum('igst_amount'),
            'cessValue'             => 0,
            'cessNonAdvolValue'     => 0,
            'otherValue'            => 0,
            'totInvValue'           => (float) $invoice->grand_total,
            'transMode'             => $transport['trans_mode'] ?? '1',
            'transDistance'         => (int) ($transport['distance'] ?? $invoice->kms ?? 0),
            'vehicleNo'             => $vehicleNo,
            'vehicleType'           => $transport['vehicle_type'] ?? 'R',
            'transporterName'       => '',
            'transporterId'         => $transport['transporter_id'] ?? '',
            'transDocNo'            => $transport['trans_doc_no'] ?? '',
            'transDocDate'          => $transport['trans_doc_date'] ?? '',
            'itemList'              => $items,
        ];
    }

    /*==========================================================================
    | GST Error Logs
    ==========================================================================*/

    public function errorLogs(): View
    {
        return view('company.pages.gst-portal.error_logs');
    }

    public function errorLogsJson(Request $request): JsonResponse
    {
        $page    = (int) $request->input('page', 1);
        $size    = (int) $request->input('size', 50);
        $service = $request->input('service', 'e_invoice');

        $query = GstApiLog::with('creator')
            ->where('company_id', company_id())
            ->where('service', $service)
            ->where('is_success', false)
            ->orderByDesc('id');

        $grandTotal = (clone $query)->count();

        if ($ref = $request->input('ref')) {
            // Search across all possible bill-number fields in the JSON payload
            $query->where('request_payload', 'like', "%{$ref}%");
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $filteredTotal = (clone $query)->count();
        $paginator     = $query->simplePaginate($size, ['*'], 'page', $page);

        $data = array_map(function (GstApiLog $log) {
            $payload = $log->request_payload ?? [];

            // E-Invoice payload uses DocDtls.No / DocDtls.Dt; EWB uses docNo / docDate
            $refNo = $payload['DocDtls']['No'] ?? $payload['docNo'] ?? $payload['reference_number'] ?? '—';
            $rawDt = $payload['DocDtls']['Dt'] ?? $payload['docDate'] ?? $payload['bill_date'] ?? null;
            if ($rawDt) {
                try {
                    $billDate = \Carbon\Carbon::createFromFormat('d/m/Y', $rawDt)->format('d-m-Y');
                } catch (\Throwable) {
                    try { $billDate = \Carbon\Carbon::parse($rawDt)->format('d-m-Y'); } catch (\Throwable) { $billDate = '—'; }
                }
            } else {
                $billDate = '—';
            }

            return [
                'id'                => $log->id,
                'reference_number'  => $refNo,
                'bill_date'         => $billDate,
                'error_code'        => $this->extractErrorCode($log),
                'error_description' => $log->error_message ?? '—',
                'user_name'         => $log->creator?->name ?? '—',
                'created_at'        => $log->created_at?->format('d-m-Y H:i:s'),
                'request_payload'   => $log->request_payload,
                'response_payload'  => $log->response_payload,
            ];
        }, $paginator->items());

        return response()->json([
            'data'         => array_values($data),
            'total'        => $filteredTotal,
            'last_page'    => (int) ceil($filteredTotal / $size),
            'current_page' => $page,
            'grand_total'  => $grandTotal,
        ]);
    }

    private function extractErrorCode(GstApiLog $log): string
    {
        // E-Invoice: error codes are in response_payload.status_desc (JSON-encoded array)
        $desc = $log->response_payload['status_desc'] ?? null;
        if (is_string($desc) && str_starts_with(trim($desc), '[')) {
            $errors = json_decode($desc, true);
            if (is_array($errors) && !empty($errors[0]['ErrorCode'])) {
                return $errors[0]['ErrorCode'];
            }
        }
        // EWB / fallback: extract numeric code from error_message text
        preg_match('/\b(\d{3,5})\b/', $log->error_message ?? '', $m);
        return $m[1] ?? '—';
    }

    /*==========================================================================
    | E-Way Bill Print
    ==========================================================================*/

    public function printEwb(string $ewbNo): View
    {
        $ewb = EWayBill::with('eInvoice')
            ->where('ewb_no', $ewbNo)
            ->where('company_id', company_id())
            ->firstOrFail();

        $invoice = SalesInvoice::with([
            'account',
            'account.state',
            'account.taxDetail',
            'details',
            'details.item',
            'details.item.unit',
        ])->find($ewb->sales_invoice_id);

        $company = Company::with('state')->find(company_id());

        return view('company.pages.gst-portal.print_ewb', compact('ewb', 'invoice', 'company'));
    }

    /*==========================================================================
    | Shared filter helper
    ==========================================================================*/

    private function applyCommonFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request, string $gstTable): void
    {
        if ($startDate = $request->input('start_date')) {
            $query->where('sales_invoices.invoice_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('sales_invoices.invoice_date', '<=', $endDate);
        }
        if ($accountId = $request->input('account_id')) {
            $query->where('sales_invoices.account_id', $accountId);
        }
        if ($status = $request->input('gst_status')) {
            if ($status === 'generated') {
                $query->whereNotNull("{$gstTable}.id");
            } elseif ($status === 'not_generated') {
                $query->whereNull("{$gstTable}.id");
            } elseif ($status === 'cancelled') {
                $query->where("{$gstTable}.status", 'cancelled');
            }
        }
    }
}

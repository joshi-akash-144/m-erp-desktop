<?php

namespace App\Http\Controllers;

use App\Models\DriverExpense;
use App\Models\Reference;
use App\Models\Account;
use App\Services\MasterDataService;
use App\Services\TransportReferencePaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TransportReferencePaymentController extends Controller
{
    protected MasterDataService $masterDataService;
    protected TransportReferencePaymentService $paymentService;

    public function __construct(MasterDataService $masterDataService, TransportReferencePaymentService $paymentService)
    {
        $this->masterDataService = $masterDataService;
        $this->paymentService    = $paymentService;
    }

    public function index()
    {
        $accounts = $this->masterDataService->getCreditorAndDebtor(company_id());
        $banks    = $this->masterDataService->banks(company_id());
        $ledgerAccounts = $this->masterDataService->get('accounts', company_id());
        $cashAccount = Account::where('code', '12000')->where('company_id', company_id())->first();

        if ($cashAccount) {
            $banks->push($cashAccount);
        }

        return view('company.pages.transport-reference-payment.index', compact('accounts', 'banks', 'ledgerAccounts', 'cashAccount'));
    }

    public function list(Request $request)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = Reference::with('account', 'creator')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereIn('source_type', [Reference::Journal, Reference::Payment])
            ->where('is_closed', 0)
            ->where('is_hold', 0)
            ->where('pending_amount', '>', 0);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $references = $query->orderBy('reference_number', 'asc')->get();

        $data = $references->map(function ($ref) {
            return [
                'id'               => $ref->id,
                'reference_number' => $ref->reference_number,
                'reference_date'   => $ref->reference_date ? date('d-m-Y', strtotime($ref->reference_date)) : '',
                'pending_amount'   => number_format((float) $ref->pending_amount, 2, '.', ''),
                'particular'       => $ref->account->name ?? '',
                'city'             => $ref->account->city ?? '',
                'account_id'       => $ref->account_id,
                'created_by'       => $ref->creator->name ?? '',
                'direction'        => $ref->direction ?? '',
                'amount'           => $ref->amount,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function detail(int $id)
    {
        $ref = Reference::with('account', 'creator', 'voucher')->findOrFail($id);

        $expense = DriverExpense::with([
            'driver',
            'vehicle',
            'creator',
            'voucher',
            'items.expenseAccount',
            'items.fromDestination',
            'items.toDestination',
            'items.item',
        ])->where('voucher_id', $ref->voucher_id)->first();

        $items = [];
        if ($expense) {
            foreach ($expense->items as $item) {
                $items[] = [
                    'billing_date'   => $item->billing_date ? date('d-m-Y', strtotime($item->billing_date)) : '',
                    'expense_name'   => $item->expenseAccount->name ?? '',
                    'from'           => $item->fromDestination->name ?? '',
                    'to'             => $item->toDestination->name ?? '',
                    'product'        => $item->item->name ?? '',
                    'dc_lr'          => $item->dc_lr ?? '',
                    'bags'           => number_format((float)$item->bags, 2, '.', ''),
                    'weight'         => number_format((float)$item->weight, 2, '.', ''),
                    'trips'          => (int)$item->trips,
                    'amount'         => number_format((float)$item->amount, 2, '.', ''),
                    'remark'         => $item->remark ?? '',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'reference_number' => $ref->reference_number,
                'reference_date'   => $ref->reference_date ? date('d-m-Y', strtotime($ref->reference_date)) : '',
                'pending_amount'   => number_format((float)$ref->pending_amount, 2, '.', ''),
                'amount'           => number_format((float)$ref->amount, 2, '.', ''),
                'voucher_number'   => $ref->voucher->voucher_number ?? '',
                'voucher_date'     => $expense?->voucher_date ? date('d-m-Y', strtotime($expense->voucher_date)) : '',
                'driver'           => $expense?->driver->name ?? ($expense?->driverAccount->name ?? ''),
                'vehicle'          => $expense?->vehicle->name ?? '',
                'narration'        => $expense?->narration ?? '',
                'expense_total'    => number_format((float)($expense?->expense_total ?? 0), 2, '.', ''),
                'created_by'       => $expense?->creator->name ?? ($ref->creator->name ?? ''),
                'items'            => $items,
            ],
        ]);
    }

    public function register()
    {
        $banks = $this->masterDataService->banks(company_id());
        return view('company.pages.transport-reference-payment.register', compact('banks'));
    }

    public function registerList(Request $request)
    {
        $filters = $request->only('from_date', 'to_date', 'bank_id');
        $page    = (int) ($request->input('page', 1));
        $size    = (int) ($request->input('size', 50));

        $result = $this->paymentService->getRegisterList(
            company_id(),
            financial_year_id(),
            $filters,
            $page,
            $size
        );

        return response()->json([
            'data'      => $result['data'],
            'last_page' => $result['last_page'],
            'total'     => $result['total'],
        ]);
    }

    public function registerDetail(int $id)
    {
        try {
            $detail = $this->paymentService->getRegisterDetail($id, company_id());
            return response()->json(['success' => true, 'data' => $detail]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function registerPrint(int $id)
    {
        try {
            $printData = $this->paymentService->getRegisterPrintData($id, company_id());
            $company   = \App\Models\Company::find(company_id());

            $html = view(
                'company.pages.transport-reference-payment.register-print',
                array_merge($printData, ['company' => $company])
            )->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'uuid'         => 'nullable|string|uuid',
            'bank_id'      => 'required|integer|exists:accounts,id',
            'payment_date' => 'required|date',
            'ref_ids'       => 'required|array|min:1',
            'ref_ids.*'     => 'required|integer|exists:references,id',
            'pay_amounts'   => 'required|array',
            'pay_amounts.*' => 'required|numeric|min:0',
            'ledger_id'               => 'nullable|integer|exists:accounts,id',
            'ledger_transaction_type' => 'nullable|string|in:cr,dr',
            'ledger_amount'           => 'nullable|numeric|min:0',
            'narration'               => 'nullable|string|max:500',
        ]);

        try {
            $release = $this->paymentService->store(
                $request->only('uuid', 'bank_id', 'payment_date', 'ref_ids', 'pay_amounts', 'ledger_id', 'ledger_transaction_type', 'ledger_amount', 'narration'),
                company_id(),
                financial_year_id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully.',
                'release_id' => $release->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function holdRegister()
    {
        $accounts = $this->masterDataService->getCreditorAndDebtor(company_id());
        return view('company.pages.transport-reference-payment.hold-register', compact('accounts'));
    }

    public function holdRegisterList(Request $request)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = Reference::with('account', 'creator')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('source_type', Reference::Journal)
            ->where('is_closed', 0)
            ->where('is_hold', 1);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $references = $query->orderBy('reference_number', 'asc')->get();

        $data = $references->map(function ($ref) {
            return [
                'id'               => $ref->id,
                'reference_number' => $ref->reference_number,
                'reference_date'   => $ref->reference_date ? date('d-m-Y', strtotime($ref->reference_date)) : '',
                'pending_amount'   => number_format((float) $ref->pending_amount, 2, '.', ''),
                'particular'       => $ref->account->name ?? '',
                'city'             => $ref->account->city ?? '',
                'account_id'       => $ref->account_id,
                'created_by'       => $ref->creator->name ?? '',
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function releaseHold(Request $request)
    {
        $request->validate([
            'ref_ids'   => 'required|array|min:1',
            'ref_ids.*' => 'required|integer|exists:references,id',
        ]);

        try {
            Reference::whereIn('id', $request->ref_ids)
                ->where('company_id', company_id())
                ->update(['is_hold' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'References released from hold successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function hold(Request $request)
    {
        $request->validate([
            'ref_ids'   => 'required|array|min:1',
            'ref_ids.*' => 'required|integer|exists:references,id',
        ]);

        try {
            Reference::whereIn('id', $request->ref_ids)
                ->where('company_id', company_id())
                ->update(['is_hold' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'References put on hold successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(int $id)
    {
        try {
            $reference = Reference::where('company_id', company_id())->findOrFail($id);
            $reference->deleted_by = current_user_id();
            $reference->save();
            $reference->delete();
            return response()->json(['success' => true, 'message' => 'Reference deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function voucherPrint(int $id)
    {
        try {
            $printData = $this->paymentService->getVoucherPrintData($id, company_id());
            $company = \App\Models\Company::find(company_id());

            $html = view(
                'company.pages.transport-reference-payment.print',
                array_merge($printData, ['company' => $company])
            )->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}

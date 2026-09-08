<?php

namespace App\Http\Controllers;

use App\Enums\TdsEntryType;
use App\Models\TdsCategory;
use App\Models\TdsEntry;
use App\Models\VoucherType;
use App\Models\TdsCategoryDetail;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TdsEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tds_entry.list')->only('index');

        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $tdsCategories = TdsCategory::select('id', 'section', 'category_name')
            ->where('company_id', company_id())
            ->orderBy('section')
            ->get();

        $voucherTypes = VoucherType::whereIn('id', [
            VoucherType::JOURNAL,
            VoucherType::PAYMENT,
            VoucherType::PURCHASE_INVOICE,
            VoucherType::SALE_INVOICE
        ])->orderBy('name')->get();

        return view('company.pages.fas.tds-entries.index', compact('tdsCategories', 'voucherTypes'));
    }

    public function list(Request $request): JsonResponse
    {
        $companyId      = company_id();
        $financialYearId = financial_year_id();

        $query = TdsEntry::with(['account:id,name', 'tdsCategory:id,section,category_name', 'voucherType:id,name'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        // Category filter
        if ($request->filled('tds_category_id')) {
            $query->where('tds_category_id', $request->tds_category_id);
        }

        // Voucher type filter
        if ($request->filled('voucher_type_id')) {
            $query->where('voucher_type_id', $request->voucher_type_id);
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', \Carbon\Carbon::createFromFormat('d-m-Y', $request->from_date)->format('Y-m-d'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', \Carbon\Carbon::createFromFormat('d-m-Y', $request->to_date)->format('Y-m-d'));
        }

        // PAN search
        if ($request->filled('pan_no')) {
            $query->where('pan_no', 'like', '%' . $request->pan_no . '%');
        }


        $summaryQuery = clone $query;
        $summary = [
            'total_payment_amount' => $summaryQuery->sum('payment_amount'),
            'total_tds_amount'     => $summaryQuery->sum('tds_amount'),
            'total_records'        => $summaryQuery->count(),
        ];

        $limit = $request->get('size', 100);
        $entries = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate($limit);

        $entries->getCollection()->transform(function($e) {
            return [
                'id'             => $e->id,
                'date'           => $e->payment_date?->format('d-m-Y'),
                'reference_number'=> $e->reference_no,
                'deductee_name'  => $e->deductee_name,
                'pan_no'         => $e->pan_no,
                'payment_amount' => $e->payment_amount,
                'tds_rate'       => $e->tds_rate,
                'tds_amount'     => $e->tds_amount,
                'voucher_type'   => $e->voucherType?->name,
                'section'        => $e->tdsCategory?->section,
                'remarks'        => $e->remarks,
            ];
        });

        return response()->json([
            'last_page' => $entries->lastPage(),
            'data'      => $entries->items(),
            'summary'   => $summary,
        ]);
    }

    public function checkThreshold(Request $request): JsonResponse
    {
        $request->validate([
            'party_id'          => 'required|exists:accounts,id',
            'expense_id'        => 'required|exists:accounts,id',
            'tds_category_id'   => 'required|exists:tds_categories,id',
            'payee_category_id' => 'required|exists:payee_categories,id',
            'amount'            => 'required|numeric|min:0',
        ]);

        $companyId = company_id();
        $financialYearId = financial_year_id();

        // Get the specific TDS rule for this category & payee combination
        $tdsRule = TdsCategoryDetail::where('tds_category_id', $request->tds_category_id)
            ->where('payee_category_id', $request->payee_category_id)
            ->first();

        if (!$tdsRule) {
            return response()->json([
                'is_applicable' => false,
                'message'       => 'No TDS rule found for this category and payee combination.'
            ]);
        }

        // Determine if party has PAN to apply the correct rate
        $party = Account::with('taxDetail')->find($request->party_id);
        $hasPan = $party && $party->taxDetail && !empty($party->taxDetail->pan);
        $percentage = $hasPan ? $tdsRule->tds_with_pan : $tdsRule->tds_without_pan;

        // Calculate total previously credited amount to this party for this TDS section in the current FY.
        // We sum the Expense's debit amount across all vouchers that contain an expense with this TDS category and credit to this party.
        $previousAmount = \App\Models\VoucherTransaction::where('debit', '>', 0)
                        ->where('account_id', $request->party_id)
            // ->whereHas('account', function ($q) use ($request) {
            //     $q->whereHas('taxDetail', function ($q2) use ($request) {
            //         $q2->where('tds_category_id', $request->tds_category_id);
            //     });
            // })
            // ->whereHas('voucher', function ($q) use ($companyId, $financialYearId, $request) {
            //     $q->where('company_id', $companyId)
            //       ->where('financial_year_id', $financialYearId)
            //       ->whereHas('details', function ($q2) use ($request) {
            //           $q2->where('account_id', $request->party_id)
            //              ->where('credit', '>', 0);
            //       });
            // })
            ->sum('debit');

        $totalAmount = $previousAmount + $request->amount;

        // Check against threshold
        $isApplicable = $totalAmount >= $tdsRule->threshold_limit;

        $tdsCategory = TdsCategory::with('defaultAccount:id,name')->find($request->tds_category_id);
        
        $defaultAccount = $tdsCategory && $tdsCategory->defaultAccount ? [
            'id' => $tdsCategory->defaultAccount->id,
            'name' => $tdsCategory->defaultAccount->name,
        ] : null;
       
        return response()->json([
            'is_applicable'   => $isApplicable,
            'percentage'      => $isApplicable ? $percentage : 0,
            'previous_amount' => $previousAmount,
            'total_amount'    => $totalAmount,
            'threshold_limit' => $tdsRule->threshold_limit,
            'has_pan'         => $hasPan,
            'default_account' => $defaultAccount
        ]);
    }
}

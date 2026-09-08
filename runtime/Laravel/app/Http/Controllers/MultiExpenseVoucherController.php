<?php

namespace App\Http\Controllers;

use App\Models\MultiExpenseVoucher;
use App\Services\JournalVoucherService;
use App\Services\MultiExpenseService;
use Illuminate\Http\Request;
use App\Services\MasterDataService;

class MultiExpenseVoucherController extends Controller
{
    protected MasterDataService $masterDataService;
    protected MultiExpenseService $multiExpenseService;
    protected JournalVoucherService $journalVoucherService;

    public function __construct(MasterDataService $masterDataService, MultiExpenseService $multiExpenseService, JournalVoucherService $journalVoucherService)
    {
        $this->masterDataService = $masterDataService;
        $this->multiExpenseService = $multiExpenseService;
        $this->journalVoucherService = $journalVoucherService;
    }




    /**
     * Display a listing of the resource.
     */
    public function index(MasterDataService $masterDataService, MultiExpenseService $multiExpenseService)
    {
        $companyId = company_id();

        $accounts = $masterDataService->getCreditorAndDebtorAndBank($companyId);
        $expenseAccounts = $multiExpenseService->getExpenseAccounts($companyId);
        $vouchers = $multiExpenseService->voucherSerials($companyId, financial_year_id());

        return view('company.pages.multi-expense-voucher.index', compact('accounts', 'expenseAccounts', 'vouchers'));
    }

    /**
     * Return paginated JSON list for the register.
     */
    public function list(Request $request, MultiExpenseService $multiExpenseService)
    {
        $request->validate([
            'from_date'           => ['nullable', 'date_format:Y-m-d'],
            'to_date'             => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'account_id'          => ['nullable', 'integer', 'exists:accounts,id'],
            'expense_account_id'  => ['nullable', 'integer', 'exists:accounts,id'],
            'voucher_serial'      => ['nullable', 'string', 'max:50'],
        ]);

        $result = $multiExpenseService->getRegisterList([
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'from_date'          => $request->input('from_date'),
            'to_date'            => $request->input('to_date'),
            'account_id'         => $request->input('account_id'),
            'expense_account_id' => $request->input('expense_account_id'),
            'voucher_serial'     => $request->input('voucher_serial'),
            'page'               => $request->input('page', 1),
            'size'               => $request->input('size', 50),
        ]);

        return response()->json($result);
    }

    /**
     * Print the full (filtered) register.
     */
    public function registerPrint(Request $request, MultiExpenseService $multiExpenseService)
    {
        $result = $multiExpenseService->getRegisterList([
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'from_date'          => $request->input('from_date'),
            'to_date'            => $request->input('to_date'),
            'account_id'         => $request->input('account_id'),
            'expense_account_id' => $request->input('expense_account_id'),
            'voucher_serial'     => $request->input('voucher_serial'),
            'page'               => 1,
            'size'               => 5000,
        ]);

        $company = \App\Models\Company::find(company_id());
        $rows = $result['data'];
        $financialYear = \App\Models\FinancialYear::find(financial_year_id());
        $fromDate = $request->input('from_date') ?? $financialYear?->start_date;
        $toDate   = $request->input('to_date') ?? $financialYear?->end_date;

        $html = view('company.pages.multi-expense-voucher.register-print', compact('rows', 'company', 'fromDate', 'toDate'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterDataService $masterDataService, MultiExpenseService $multiExpenseService, JournalVoucherService $journalVoucherService)
    {
        $accounts = $masterDataService->getCreditorAndDebtorAndBank(company_id());
        $expenseAccounts = $multiExpenseService->getExpenseAccounts(company_id());
        $nextVoucherNumber = $journalVoucherService->getNextVoucherNumber(company_id(), financial_year_id())->serial;
        $uuid = uuid();

        return view('company.pages.multi-expense-voucher.create', compact('accounts', 'expenseAccounts', 'nextVoucherNumber', 'uuid'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'uuid'               => ['required', 'string'],
            'voucher_date'       => ['required', 'date'],
            'day_for'            => ['nullable', 'string', 'max:20'],
            'account_id'         => ['required', 'integer', 'exists:accounts,id'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:account_id'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.vehicle_id'          => ['required', 'integer', 'exists:vehicles,id'],
            'items.*.bill_no'             => ['nullable', 'string', 'max:100'],
            'items.*.challan_number'      => ['nullable', 'string', 'max:100'],
            'items.*.bill_date'           => ['nullable', 'date'],
            'items.*.amount'              => ['required', 'numeric', 'min:0'],
            'items.*.remark'              => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = $this->findItemRowError($data['items'])) {
            return response()->json(['message' => $error], 422);
        }

        $expense = $this->multiExpenseService->store($data, company_id(), financial_year_id());

        $firstItem = $expense->items->first();
        $voucherSerial = $firstItem ? ($firstItem->voucher?->voucher_serial ?? '') : '';

        return response()->json([
            'success'        => true,
            'message'        => 'Multi Expense Voucher saved successfully.',
            'id'             => $expense->id,
            'voucher_serial' => $voucherSerial,
        ]);
    }

    /**
     * Validate row-level rules shared by store() and update(): a "dirty" row
     * (any field filled) requires vehicle_id + amount, and bill_no must not repeat.
     */
    private function findItemRowError(array $items): ?string
    {
        $seenBillNos = [];
        foreach ($items as $index => $item) {
            $isDirty = !empty($item['vehicle_id']) || !empty($item['amount'])
                || !empty($item['bill_no']) || !empty($item['bill_date']) || !empty($item['remark']);

            if (!$isDirty) continue;

            $rowNum = $index + 1;
            if (empty($item['vehicle_id'])) {
                return "Row {$rowNum}: Vehicle is required.";
            }
            if (empty($item['amount']) || (float) $item['amount'] <= 0) {
                return "Row {$rowNum}: Amount is required.";
            }
            if (!empty($item['bill_no'])) {
                $billNoKey = strtolower(trim($item['bill_no']));
                if (isset($seenBillNos[$billNoKey])) {
                    return "Row {$rowNum}: Bill No \"{$item['bill_no']}\" is already used in row {$seenBillNos[$billNoKey]}.";
                }
                $seenBillNos[$billNoKey] = $rowNum;
            }
        }

        return null;
    }

    /**
     * Print a single multi expense voucher.
     */
    public function printVoucher(MultiExpenseVoucher $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $expense = MultiExpenseVoucher::with([
            'account:id,name',
            'expenseAccount:id,name',
            'items.vehicle:id,name',
            'items.voucher:id,voucher_serial,voucher_date',
        ])->findOrFail($multiExpenseVoucher->id);

        $company = \App\Models\Company::find(company_id());

        $html = view('company.pages.multi-expense-voucher.print', compact('expense', 'company'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Get pending diesel items (challans) for a specific account.
     */
    public function getPendingChallans(Request $request)
    {
        $accountId = $request->input('account_id');
        $voucherId = $request->input('voucher_id');

        if (!$accountId) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $challans = \App\Models\DieselItem::where(function ($q) use ($voucherId) {
            $q->where('is_closed', 0);
            if ($voucherId) {
                $voucherIds = \App\Models\MultiExpenseVoucherItem::where('multi_expense_voucher_id', $voucherId)
                    ->pluck('voucher_id');
                if ($voucherIds->isNotEmpty()) {
                    $q->orWhereIn('closed_by_voucher_id', $voucherIds);
                }
            }
        })
            ->whereNotNull('challan_number')
            ->whereHas('diesel', function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id());
            })
            ->with(['vehicle:id,name', 'diesel:id,diesel_rate'])
            ->get(['diesel_items.id', 'diesel_items.diesel_id', 'diesel_items.challan_number', 'diesel_items.amount', 'diesel_items.vehicle_id', 'diesel_items.today_date', 'diesel_items.diesel as liter', 'diesel_items.rate']);

        $challans = $challans->map(function ($ch) {
            $itemRate = (float) $ch->rate;
            $dieselRate = $itemRate > 0 ? $itemRate : $ch->diesel?->diesel_rate;

            return [
                'id' => $ch->id,
                'challan_number' => $ch->challan_number,
                'amount' => $ch->amount,
                'vehicle_id' => $ch->vehicle_id,
                'today_date' => $ch->today_date,
                'liter' => $ch->liter,
                'diesel_rate' => $dieselRate,
                'vehicle' => $ch->vehicle ? ['name' => $ch->vehicle->name] : null,
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $challans
        ]);
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
    public function edit(MultiExpenseVoucher $multiExpenseVoucher, MasterDataService $masterDataService, MultiExpenseService $multiExpenseService)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);


        $accounts = $masterDataService->getCreditorAndDebtorAndBank(company_id());
        $expenseAccounts = $multiExpenseService->getExpenseAccounts(company_id());

        return view('company.pages.multi-expense-voucher.edit', compact('multiExpenseVoucher', 'accounts', 'expenseAccounts'));
    }

    /**
     * Return header + items as JSON for the edit form.
     */
    public function getData(MultiExpenseVoucher $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $multiExpenseVoucher->load([
            'items.vehicle:id,name',
            'items.voucher:id,voucher_serial,voucher_date',
        ]);

        $items = $multiExpenseVoucher->items->map(fn($item) => [
            'id'             => $item->id,
            'voucher_serial' => $item->voucher?->voucher_serial ?? '',
            'bill_no'        => $item->bill_no ?? '',
            'bill_date'      => $item->bill_date ? \Carbon\Carbon::parse($item->bill_date)->format('d-m-Y') : '',
            'vehicle_id'     => $item->vehicle_id,
            'vehicle_name'   => $item->vehicle?->name ?? '',
            'amount'         => (string) ($item->amount ?? '0.00'),
            'challan_number' => $item->challan_number ?? '',
            'remark'         => $item->remark ?? '',
        ])->values();

        return response()->json([
            'id'                 => $multiExpenseVoucher->id,
            'voucher_serial'     => '',
            'voucher_date'       => $multiExpenseVoucher->voucher_date?->format('d-m-Y') ?? '',
            'day_for'            => $multiExpenseVoucher->day_for ?? '',
            'account_id'         => $multiExpenseVoucher->account_id,
            'expense_account_id' => $multiExpenseVoucher->expense_account_id,
            'narration'          => $multiExpenseVoucher->narration ?? '',
            'total_amount'       => (string) ($multiExpenseVoucher->total_amount ?? '0.00'),
            'items'              => $items,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MultiExpenseVoucher $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $data = $request->validate([
            'voucher_date'       => ['required', 'date'],
            'day_for'            => ['nullable', 'string', 'max:20'],
            'account_id'         => ['required', 'integer', 'exists:accounts,id'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:account_id'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.id'                  => ['nullable', 'integer'],
            'items.*.vehicle_id'          => ['required', 'integer', 'exists:vehicles,id'],
            'items.*.bill_no'             => ['nullable', 'string', 'max:100'],
            'items.*.challan_number'      => ['nullable', 'string', 'max:100'],
            'items.*.bill_date'           => ['nullable', 'date'],
            'items.*.amount'              => ['required', 'numeric', 'min:0'],
            'items.*.remark'              => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = $this->findItemRowError($data['items'])) {
            return response()->json(['message' => $error], 422);
        }

        $expense = $this->multiExpenseService->update($data, $multiExpenseVoucher);

        $voucher = $expense->voucher;

        return response()->json([
            'success'        => true,
            'message'        => 'Multi Expense Voucher updated successfully.',
            'id'             => $expense->id,
            'voucher_serial' => $voucher?->voucher_serial ?? '',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

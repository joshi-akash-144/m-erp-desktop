<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Services\JournalVoucherService;
use App\Services\SalaryModuleService;
use Illuminate\Http\Request;
use App\Services\MasterDataService;

class SalaryModuleController extends Controller
{
    protected MasterDataService $masterDataService;
    protected SalaryModuleService $salaryModuleService;
    protected JournalVoucherService $journalVoucherService;

    public function __construct(MasterDataService $masterDataService, SalaryModuleService $salaryModuleService, JournalVoucherService $journalVoucherService)
    {
        $this->masterDataService = $masterDataService;
        $this->salaryModuleService = $salaryModuleService;
        $this->journalVoucherService = $journalVoucherService;
    }




    /**
     * Display a listing of the resource.
     */
    public function index(MasterDataService $masterDataService, SalaryModuleService $salaryModuleService)
    {
        $companyId = company_id();

        $expenseAccounts = $salaryModuleService->getExpenseAccounts($companyId);
        $vouchers = $salaryModuleService->voucherSerials($companyId, financial_year_id());

        return view('company.pages.salary-module.index', compact('expenseAccounts', 'vouchers'));
    }

    /**
     * Return paginated JSON list for the register.
     */
    public function list(Request $request, SalaryModuleService $salaryModuleService)
    {
        $request->validate([
            'from_date'           => ['nullable', 'date_format:Y-m-d'],
            'to_date'             => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'expense_account_id'  => ['nullable', 'integer', 'exists:accounts,id'],
            'voucher_serial'      => ['nullable', 'string', 'max:50'],
        ]);

        $result = $salaryModuleService->getRegisterList([
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'from_date'          => $request->input('from_date'),
            'to_date'            => $request->input('to_date'),
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
    public function registerPrint(Request $request, SalaryModuleService $salaryModuleService)
    {
        $result = $salaryModuleService->getRegisterList([
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'from_date'          => $request->input('from_date'),
            'to_date'            => $request->input('to_date'),
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

        $html = view('company.pages.salary-module.register-print', compact('rows', 'company', 'fromDate', 'toDate'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterDataService $masterDataService, SalaryModuleService $salaryModuleService, JournalVoucherService $journalVoucherService)
    {
        $accounts = $masterDataService->getCreditorAndDebtor(company_id());
        $expenseAccounts = $salaryModuleService->getExpenseAccounts(company_id());
        $nextVoucherNumber = $journalVoucherService->getNextVoucherNumber(company_id(), financial_year_id())->serial;
        $uuid = uuid();

        return view('company.pages.salary-module.create', compact('accounts', 'expenseAccounts', 'nextVoucherNumber', 'uuid'));
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
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'month'              => ['nullable', 'string', 'max:20'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.account_id'          => ['required', 'integer', 'exists:accounts,id'],
            'items.*.vehicle_id'          => ['nullable', 'integer', 'exists:vehicles,id'],
            'items.*.bill_no'             => ['nullable', 'string', 'max:100'],
            'items.*.bill_date'           => ['nullable', 'date'],
            'items.*.amount'              => ['nullable', 'numeric', 'min:0'],
            'items.*.remark'              => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = $this->findItemRowError($data['items'])) {
            return response()->json(['message' => $error], 422);
        }

        $expense = $this->salaryModuleService->store($data, company_id(), financial_year_id());

        $voucher = $expense->voucher;

        return response()->json([
            'success'        => true,
            'message'        => 'Salary Voucher saved successfully.',
            'id'             => $expense->id,
            'voucher_serial' => $voucher?->voucher_serial ?? '',
        ]);
    }

    /**
     * Validate row-level rules shared by store() and update(): a "dirty" row
     * (any field filled) requires vehicle_id + amount, and bill_no must not repeat.
     */
    private function findItemRowError(array $items, $month = null, $companyId = null, $financialYearId = null, $ignoreUuid = null): ?string
    {
        $seenAccounts = [];
        foreach ($items as $index => $item) {
            $isDirty = !empty($item['vehicle_id']) || !empty($item['amount'])
                || !empty($item['bill_no']) || !empty($item['bill_date']) || !empty($item['remark']) || !empty($item['account_id']);

            if (!$isDirty) continue;

            $rowNum = $index + 1;
            if (empty($item['account_id'])) {
                return "Row {$rowNum}: Account is required.";
            }
            if (isset($seenAccounts[$item['account_id']])) {
                return "Row {$rowNum}: Same account name not valid in same month.";
            }
            $seenAccounts[$item['account_id']] = $rowNum;

            if (empty($item['vehicle_id'])) {
                return "Row {$rowNum}: Vehicle is required.";
            }
            if (empty($item['amount']) || (float) $item['amount'] <= 0) {
                return "Row {$rowNum}: Amount is required.";
            }
        }

        if ($month && $companyId && $financialYearId) {
            $accountIds = array_keys($seenAccounts);
            if (!empty($accountIds)) {
                $query = \App\Models\SalaryDetail::whereIn('account_id', $accountIds)
                    ->whereHas('salary', function ($q) use ($month, $companyId, $financialYearId, $ignoreUuid) {
                        $q->where('company_id', $companyId)
                          ->where('financial_year_id', $financialYearId)
                          ->where('month', $month);
                        if ($ignoreUuid) {
                            $q->where('uuid', '!=', $ignoreUuid);
                        }
                    });
                
                if ($query->exists()) {
                    $duplicate = $query->first();
                    $acc = \App\Models\Account::find($duplicate->account_id);
                    $accName = $acc ? $acc->name : 'Unknown Account';
                    return "Account '{$accName}' already has a salary entry in month {$month}. Same account name not valid in same month.";
                }
            }
        }

        return null;
    }

    /**
     * Print a single multi expense voucher.
     */
    public function printVoucher(Salary $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $expense = Salary::with([
            'account:id,name',
            'expenseAccount:id,name',
            'voucher:id,voucher_serial,voucher_date',
            'items.vehicle:id,name',
            'items.account:id,name',
        ])->findOrFail($multiExpenseVoucher->id);

        $expense->setRelation('items', $expense->items->sortBy(function ($item) {
            return $item->account?->name;
        })->values());

        $company = \App\Models\Company::find(company_id());

        $html = view('company.pages.salary-module.print', compact('expense', 'company'))->render();

        return response()->json(['success' => true, 'html' => $html]);
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
    public function edit(Salary $multiExpenseVoucher, MasterDataService $masterDataService, SalaryModuleService $salaryModuleService)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $multiExpenseVoucher->load(['voucher:id,voucher_serial,voucher_date']);

        $accounts = $masterDataService->getCreditorAndDebtor(company_id());
        $expenseAccounts = $salaryModuleService->getExpenseAccounts(company_id());

        return view('company.pages.salary-module.edit', compact('multiExpenseVoucher', 'accounts', 'expenseAccounts'));
    }

    /**
     * Return header + items as JSON for the edit form.
     */
    public function getData(Salary $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $multiExpenseVoucher->load([
            'voucher:id,voucher_serial,voucher_date',
            'details.vehicle:id,name',
            'details.account:id,name',
        ]);

        $items = $multiExpenseVoucher->details->map(fn($item) => [
            'voucher_serial' => $multiExpenseVoucher->voucher?->voucher_serial ?? '',
            'bill_no'      => $item->ref_no ?? '',
            'bill_date'    => '',
            'vehicle_id'   => $item->vehicle_id,
            'account_id'   => $item->account_id,
            'account_name' => $item->account?->name ?? '',
            'vehicle_name' => $item->vehicle?->name ?? '',
            'amount'       => (string) ($item->amount ?? '0.00'),
            'remark'       => $item->remark ?? '',
        ])->values();

        return response()->json([
            'id'                 => $multiExpenseVoucher->id,
            'voucher_serial'     => $multiExpenseVoucher->voucher?->voucher_serial ?? '',
            'voucher_date'       => $multiExpenseVoucher->voucher_date?->format('d-m-Y') ?? '',
            'day_for'            => $multiExpenseVoucher->day_for ?? '',
            'month'              => $multiExpenseVoucher->month ?? '',
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
    public function update(Request $request, Salary $multiExpenseVoucher)
    {
        abort_unless($multiExpenseVoucher->company_id === company_id(), 403);

        $data = $request->validate([
            'voucher_date'       => ['required', 'date'],
            'day_for'            => ['nullable', 'string', 'max:20'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'month'              => ['nullable', 'string', 'max:20'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.account_id'          => ['required', 'integer', 'exists:accounts,id'],
            'items.*.vehicle_id'          => ['nullable', 'integer', 'exists:vehicles,id'],
            'items.*.bill_no'             => ['nullable', 'string', 'max:100'],
            'items.*.bill_date'           => ['nullable', 'date'],
            'items.*.amount'              => ['nullable', 'numeric', 'min:0'],
            'items.*.remark'              => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = $this->findItemRowError($data['items'])) {
            return response()->json(['message' => $error], 422);
        }

        $expense = $this->salaryModuleService->update($data, $multiExpenseVoucher);

        $voucher = $expense->voucher;

        return response()->json([
            'success'        => true,
            'message'        => 'Salary Voucher updated successfully.',
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

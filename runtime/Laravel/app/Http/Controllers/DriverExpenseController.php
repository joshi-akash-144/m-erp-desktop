<?php

namespace App\Http\Controllers;

use App\Exports\DriverExpenseSummaryExport;
use App\Models\Account;
use App\Models\DairyImportItem;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\DriverExpense;
use App\Models\Reference;
use App\Models\Vehicle;
use App\Models\VoucherType;
use App\Services\DriverExpenseService;
use App\Services\MasterDataService;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class DriverExpenseController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(MasterDataService $masterService)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $vehicles = Vehicle::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        $drivers = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();

        $vouchers = DriverExpense::where('driver_expenses.company_id', $companyId)
            ->where('driver_expenses.financial_year_id', $financialYearId)
            ->whereNotNull('driver_expenses.voucher_id')
            ->join('vouchers', 'vouchers.id', '=', 'driver_expenses.voucher_id')
            ->orderByDesc(\DB::raw('CAST(vouchers.voucher_serial AS UNSIGNED)'))
            ->pluck('vouchers.voucher_serial');

        return view('company.pages.driver-expense.index', compact('vehicles', 'drivers', 'vouchers'));
    }

    /**
     * Return paginated JSON list for the register.
     */
    public function list(Request $request, DriverExpenseService $service)
    {
        $request->validate([
            'from_date'      => ['nullable', 'date_format:Y-m-d'],
            'to_date'        => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'vehicle_id'     => ['nullable', 'integer', 'exists:vehicles,id'],
            'account_id'     => ['nullable', 'integer', 'exists:accounts,id'],
            'voucher_serial' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $service->getRegisterList([
            'company_id'        => company_id(),
            'financial_year_id' => financial_year_id(),
            'from_date'         => $request->input('from_date'),
            'to_date'           => $request->input('to_date'),
            'vehicle_id'        => $request->input('vehicle_id'),
            'account_id'        => $request->input('account_id'),
            'voucher_serial'    => $request->input('voucher_serial'),
            'page'              => $request->input('page', 1),
            'size'              => $request->input('size', 50),
        ]);

        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterDataService $masterService)
    {
        $companyId = company_id();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $drivers = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();
        $accounts  = $masterService->get('accounts', $companyId);

        $serialInfo = $this->voucherService->getNextVoucherNumber(
            VoucherType::JOURNAL,
            $companyId,
            financial_year_id()
        );

        $voucherNo = $serialInfo->serial;
        $uuid = uuid();

        return view(
            'company.pages.driver-expense.create',
            compact('vehicles', 'drivers', 'accounts', 'voucherNo', 'uuid')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DriverExpenseService $service)
    {
        $data = $request->validate([
            'uuid'               => ['required', 'string'],
            'voucher_date'       => ['required', 'date'],
            'vehicle_id'         => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id'          => ['required', 'integer', 'exists:accounts,id'],
            'expense_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'start_kms'          => ['nullable', 'numeric', 'min:0'],
            'end_kms'            => ['nullable', 'numeric', 'min:0'],
            'total_kms'          => ['nullable', 'numeric', 'min:0'],
            'start_diesel'       => ['nullable', 'numeric', 'min:0'],
            'end_diesel'         => ['nullable', 'numeric', 'min:0'],
            'diesel_average'     => ['nullable', 'numeric', 'min:0'],
            'idle_days'          => ['nullable', 'integer', 'min:0'],
            'idle_day_wage'      => ['nullable', 'numeric', 'min:0'],
            'idle_day_wage_amount' => ['nullable', 'numeric', 'min:0'],
            'expense_total'      => ['nullable', 'numeric', 'min:0'],
            'items'                        => ['required', 'array', 'min:1'],
        ]);


        $expense = $service->store($data, company_id(), financial_year_id());

        $voucher = $expense->voucher;

        return response()->json([
            'success'        => true,
            'message'        => 'Driver Expense saved successfully.',
            'id'             => $expense->id,
            'voucher_serial' => $voucher?->voucher_serial ?? '',
        ]);
    }

    /**
     * Fetch DairyImportItem records matching vehicle + billing date for auto-fill.
     */
    public function getDairyImportData(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'date'       => ['required', 'string'],
            'vehicle_id' => ['required', 'integer'],
        ]);


        $d = \DateTime::createFromFormat('d-m-Y', $request->date);
        if (!$d) {
            return response()->json(['found' => false, 'items' => []]);
        }

        $billingDate = $d->format('Y-m-d');
        $vehicleId   = (int) $request->vehicle_id;
        $companyId   = company_id();

      $rows = DairyImportItem::with(['destination', 'product'])
            ->whereHas('dairyImport', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                ->whereNull('product_id');
            })
            ->where('vehicle_id', $vehicleId)
            ->where('billing_date', $billingDate)
            ->get();
        
        if ($rows->isEmpty()) {
            return response()->json(['found' => false, 'items' => []]);
        }

        // Load "from" destinations in one query (field name is 'from')
        $fromIds   = $rows->pluck('from')->filter()->unique()->values()->all();
        $fromNames = $fromIds
            ? Destination::whereIn('id', $fromIds)->pluck('name', 'id')->all()
            : [];

        $fixFormEntry = Destination::where('company_id', $companyId)->where('name', 'SABARDAN')->first();
        $fixExpenseAccount = Account::where('company_id', $companyId)->where('code', 42002)->first();

        $items = $rows->map(fn($item) => [
            'id'                     => $item->id,
            'to_id'                  => $item->to,
            'expense_account_id'     => $fixExpenseAccount->id ?? '',
            'expense_account_name'   => $fixExpenseAccount->name ?? '',
            'to_name'                => $item->destination?->name ?? '',
            'from_id'                => $item->from ?? $fixFormEntry->id,
            'from_name'              => $item->from ? ($fromNames[$item->from] ?? '') : $fixFormEntry->name,
            'product_id'             => $item->product_id,
            'product_name'           => $item->product?->name ?? '',
            'quantity'               => 0,
            'bags'                   => $item->quantity ?? 0,
            'rate'                   => 3,
            'dc_number'              => $item->dc_number ?? '',
            'lr_number'              => $item->lr_number ?? '',
        ])->values();

        return response()->json(['found' => true, 'items' => $items]);
    }

    /**
     * Print a single driver expense voucher.
     */
    public function printVoucher(DriverExpense $driverExpense, DriverExpenseService $service)
    {
        abort_unless($driverExpense->company_id === company_id(), 403);

        $expense = DriverExpense::with([
            'vehicle:id,name',
            'account:id,name',
            'voucher:id,voucher_serial,voucher_date',
            'items.expenseAccount:id,name',
            'items.fromDestination:id,name',
            'items.toDestination:id,name',
            'items.item:id,name',
        ])->findOrFail($driverExpense->id);
        

        $company = \App\Models\Company::find(company_id());

        $html = view('company.pages.driver-expense.print', compact('expense', 'company'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function registerPrint(Request $request, DriverExpenseService $service)
    {
        if (!$request->filled('from_date') || !$request->filled('to_date')) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a date range to print this register.'
            ]);
        }

        $result = $service->getRegisterList([
            'company_id'        => company_id(),
            'financial_year_id' => financial_year_id(),
            'from_date'         => $request->input('from_date'),
            'to_date'           => $request->input('to_date'),
            'vehicle_id'        => $request->input('vehicle_id'),
            'account_id'        => $request->input('account_id'),
            'voucher_serial'    => $request->input('voucher_serial'),
            'page'              => 1,
            'size'              => 5000,
        ]);

        $company       = \App\Models\Company::find(company_id());
        $rows          = $result['data'];
        $financialYear = \App\Models\FinancialYear::find(financial_year_id());
        $fromDate      = $request->input('from_date') ?? $financialYear?->start_date;
        $toDate        = $request->input('to_date')   ?? $financialYear?->end_date;

        $html = view('company.pages.driver-expense.register-print', compact('rows', 'company', 'fromDate', 'toDate'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    /**
     * Display the specified resource.
     */
    public function show(DriverExpense $driverExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DriverExpense $driverExpense, MasterDataService $masterService)
    {
        abort_unless($driverExpense->company_id === company_id(), 403);

        $driverExpense->load([
            'vehicle:id,name',
            'account:id,name',
            'voucher:id,voucher_serial,voucher_date',
            'items.expenseAccount:id,name',
            'items.fromDestination:id,name',
            'items.toDestination:id,name',
            'items.item:id,name',
        ]);

        $isLocked = $driverExpense->voucher_id
            ? Reference::where('voucher_id', $driverExpense->voucher_id)
                ->where('settled_amount', '>', 0)
                ->exists()
            : false;

        $companyId = company_id();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $drivers   = Driver::select('drivers.*')
            ->join('accounts', 'accounts.id', '=', 'drivers.account_id')
            ->where('drivers.company_id', $companyId)
            ->orderBy('accounts.name')
            ->with('account:id,name')
            ->get();

        return view('company.pages.driver-expense.edit', compact(
            'driverExpense', 'vehicles', 'drivers', 'isLocked'
        ));
    }

    /**
     * Return expense header + items as JSON for the edit form.
     */
    public function getData(DriverExpense $driverExpense): \Illuminate\Http\JsonResponse
    {
        abort_unless($driverExpense->company_id === company_id(), 403);

        $driverExpense->load([
            'voucher:id,voucher_serial,voucher_date',
            'items.expenseAccount:id,name',
            'items.fromDestination:id,name',
            'items.toDestination:id,name',
            'items.item:id,name',
        ]);

        $items = $driverExpense->items->map(fn($item) => [
            'date'                 => $item->billing_date
                ? \Carbon\Carbon::parse($item->billing_date)->format('d-m-Y')
                : '',
            'expense_account_id'   => $item->expense_account_id,
            'expense_account_name' => $item->expenseAccount?->name ?? '',
            'from_id'              => $item->from_destination_id,
            'from_name'            => $item->fromDestination?->name ?? '',
            'to_id'                => $item->to_destination_id,
            'to_name'              => $item->toDestination?->name ?? '',
            'dc_lr'                => $item->dc_lr ?? '',
            'item_id'              => $item->item_id,
            'item_name'            => $item->item?->name ?? '',
            'rate'                 => (string) ($item->rate ?? ''),
            'bags'                 => (string) ($item->bags ?? '0'),
            'weight'               => (string) ($item->weight ?? '0'),
            'trips'                => (string) ($item->trips ?? ''),
            'amount'               => (string) ($item->amount ?? '0.00'),
            'remark'               => $item->remark ?? '',
        ])->values();

        return response()->json([
            'id'            => $driverExpense->id,
            'voucher_serial'=> $driverExpense->voucher?->voucher_serial ?? '',
            'voucher_date'  => $driverExpense->voucher_date?->format('d-m-Y') ?? '',
            'vehicle_id'    => $driverExpense->vehicle_id,
            'driver_id'     => $driverExpense->account_id,
            'narration'     => $driverExpense->narration ?? '',
            'start_kms'     => (string) ($driverExpense->start_kms ?? '0.00'),
            'end_kms'       => (string) ($driverExpense->end_kms ?? '0.00'),
            'total_kms'     => (string) ($driverExpense->total_kms ?? '0.00'),
            'expense_total' => (string) ($driverExpense->expense_total ?? '0.00'),
            'items'         => $items,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DriverExpense $driverExpense, DriverExpenseService $service)
    {
        abort_unless($driverExpense->company_id === company_id(), 403);

        $data = $request->validate([
            'voucher_date'         => ['required', 'date'],
            'vehicle_id'           => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id'            => ['required', 'integer', 'exists:accounts,id'],
            'expense_account_id'   => ['nullable', 'integer', 'exists:accounts,id'],
            'narration'            => ['nullable', 'string', 'max:500'],
            'start_kms'            => ['nullable', 'numeric', 'min:0'],
            'end_kms'              => ['nullable', 'numeric', 'min:0'],
            'total_kms'            => ['nullable', 'numeric', 'min:0'],
            'start_diesel'         => ['nullable', 'numeric', 'min:0'],
            'end_diesel'           => ['nullable', 'numeric', 'min:0'],
            'diesel_average'       => ['nullable', 'numeric', 'min:0'],
            'idle_days'            => ['nullable', 'integer', 'min:0'],
            'idle_day_wage'        => ['nullable', 'numeric', 'min:0'],
            'idle_day_wage_amount' => ['nullable', 'numeric', 'min:0'],
            'expense_total'        => ['nullable', 'numeric', 'min:0'],
            'items'                       => ['required', 'array', 'min:1'],
            // 'items.*.date'                => ['required', 'date'],
            // 'items.*.expense_account_id'  => ['required', 'integer', 'exists:accounts,id'],
            // 'items.*.from_id'             => ['nullable', 'integer'],
            // 'items.*.to_id'               => ['nullable', 'integer'],
            // 'items.*.dc_lr'               => ['nullable', 'string', 'max:100'],
            // 'items.*.item_id'             => ['nullable', 'integer'],
            // 'items.*.rate'                => ['nullable', 'numeric', 'min:0'],
            // 'items.*.bags'                => ['nullable', 'numeric', 'min:0'],
            // 'items.*.weight'              => ['nullable', 'numeric', 'min:0'],
            // 'items.*.trips'               => ['nullable', 'integer', 'min:0'],
            // 'items.*.amount'              => ['required', 'numeric', 'min:0.01'],
            // 'items.*.remark'              => ['nullable', 'string', 'max:255'],
        ]);

        $expense = $service->update($data, $driverExpense);
        $voucher = $expense->voucher;

        return response()->json([
            'success'        => true,
            'message'        => 'Driver Expense updated successfully.',
            'id'             => $expense->id,
            'voucher_serial' => $voucher?->voucher_serial ?? '',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DriverExpense $driverExpense)
    {
        //
    }

    public function exportSummary(Request $request)
    {
        $from = $request->input('from_no');
        $to = $request->input('to_no');
        $companyId = company_id();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new DriverExpenseSummaryExport($from, $to, $companyId),
            'driver_expense_summary.xlsx'
        );
    }
}

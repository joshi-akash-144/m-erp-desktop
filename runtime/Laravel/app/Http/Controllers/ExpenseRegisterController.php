<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Vehicle;
use App\Services\ExpenseRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Exports\ExpenseRegisterExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Company;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;

class ExpenseRegisterController extends Controller
{
    protected ExpenseRegisterService $service;

    public function __construct(ExpenseRegisterService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filterData = $this->service->getFilterData(company_id());

        return view('company.pages.expense-register.index', $filterData);
    }

    public function list(Request $request): JsonResponse
    {
        $filters = [
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'start_date'         => $request->input('start_date') ?: financial_year_start(),
            'end_date'           => $request->input('end_date') ?: financial_year_end(),
            'vehicle_id'         => $request->input('vehicle_id'),
            'expense_account_id' => $request->input('expense_account_id'),
            'voucher_serial'     => $request->input('voucher_serial'),
            'party_id'           => $request->input('party_id'),
            'reference_number'   => $request->input('reference_number'),
            'page'               => $request->input('page', 1),
            'size'               => $request->input('size', 50),
        ];

        $response = $this->service->getRegisterList($filters);

        return response()->json($response);
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $reqFilters = $request->input('currentFilter') ?? $request->all();
        $filters = [
            'company_id'         => company_id(),
            'financial_year_id'  => financial_year_id(),
            'start_date'         => ($reqFilters['start_date'] ?? null) ?: financial_year_start(),
            'end_date'           => ($reqFilters['end_date'] ?? null) ?: financial_year_end(),
            'vehicle_id'         => $reqFilters['vehicle_id'] ?? null,
            'expense_account_id' => $reqFilters['expense_account_id'] ?? null,
            'voucher_serial'     => isset($reqFilters['voucher_serial']) ? $reqFilters['voucher_serial'] : null,
            'party_id'           => $reqFilters['party_id'] ?? null,
            'reference_number'   => $reqFilters['reference_number'] ?? null,
            'page'               => 1,
            'size'               => 'all',
        ];

        $response = $this->service->getRegisterList($filters);
        $expenseData = $response['data'];

        $company = Company::find(company_id());

        $tableConfig = [
            "columns" => [
                ["label" => "Date", "class" => "text-start", "width" => "8%"],
                ["label" => "Voucher No.", "class" => "text-center", "width" => "10%"],
                ["label" => "Party Name", "class" => "text-start", "width" => "30%"],
                ["label" => "Ref No.", "class" => "text-center", "width" => "15%"],
                ["label" => "Vehicle / Account", "class" => "text-start", "width" => "17%"],
                ["label" => "Expense Account", "class" => "text-start", "width" => "15%"],
                ["label" => "Amount", "class" => "text-end", "width" => "10%"],
            ],
        ];

        $expenseAccountName = null;
        if (!empty($filters['expense_account_id'])) {
            $acc = Account::find($filters['expense_account_id']);
            $expenseAccountName = $acc ? $acc->name : null;
        }        

        $data = [
            'company'             => $company,
            'tableConfig'         => $tableConfig,
            'expenseData'         => $expenseData,
            'filters'             => $filters,
            'expenseAccountName'  => $expenseAccountName,            
            'orientation'         => $request->input('orientation', 'landscape'),
        ];

        $html = view('company.pages.expense-register.print', $data)->render();

        return AjaxResponse::success(
            message: 'Expense Register printed successfully.',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {
        try {
            $reqFilters = $request->input('currentFilter') ?? $request->all();
            $filters = [
                'company_id'         => company_id(),
                'financial_year_id'  => financial_year_id(),
                'start_date'         => ($reqFilters['start_date'] ?? null) ?: financial_year_start(),
                'end_date'           => ($reqFilters['end_date'] ?? null) ?: financial_year_end(),
                'vehicle_id'         => $reqFilters['vehicle_id'] ?? null,
                'expense_account_id' => $reqFilters['expense_account_id'] ?? null,
                'voucher_serial'     => $reqFilters['voucher_serial'] ?? null,
                'party_id'           => $reqFilters['party_id'] ?? null,
                'reference_number'   => $reqFilters['reference_number'] ?? null,
                'page'               => 1,
                'size'               => 'all',
            ];

            $response = $this->service->getRegisterList($filters);
            $expenseAccountName = null;
            if (!empty($filters['expense_account_id'])) {
                $acc = Account::find($filters['expense_account_id']);
                $expenseAccountName = $acc ? $acc->name : null;
            }             

            $expenseData = collect($response['data']);
            $expenseData = $expenseData->map(function($item) use ($expenseAccountName) {
                $item['expense_account_name'] = $expenseAccountName;
                return $item;
            });

            $company = Company::find(company_id());

            $startDate = !empty($filters['start_date']) 
                ? Carbon::parse($filters['start_date'])->format('d-m-Y')
                : Carbon::now()->startOfMonth()->format('d-m-Y');
            
            $endDate = !empty($filters['end_date']) 
                ? Carbon::parse($filters['end_date'])->format('d-m-Y')
                : Carbon::now()->format('d-m-Y');
            
            $datePeriod = "$startDate to $endDate";

            $headings = ['Date', 'Voucher No.', 'Party Name', 'Ref No.', 'Vehicle / Account', 'Expense Account', 'Amount'];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name ?? $company->name, '_');
            $fileName = "{$companyNameSlug}_expense_register_" . now()->format('d_m_Y_His') . ".xlsx";

            Excel::store(
                new ExpenseRegisterExport($company, $expenseData, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Expense Register Exported successfully (xlsx)", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);

        } catch (Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }
}

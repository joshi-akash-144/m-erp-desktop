<?php

namespace App\Http\Controllers;

use App\Exports\AccountExport;
use App\Models\VoucherType;
use App\Repositories\AccountRepository;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class AccountExportController extends Controller
{
    protected AccountRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(AccountRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:account.print')->only('print');
        $this->middleware('permission:account.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


        // $this->middleware(function ($request, $next) {
        //     $this->companyId = session('company_id');
        //     return $next($request);
        // });
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());

        $filters = $request->currentFilter;

        $accounts = $this->repository->all(
            columns: ['*'],
            with: ['accountGroup'],
            filters: ['company_id' => company_id(), 'is_hidden' => false],
            scopes: [
                fn($query) => $query->status('active'),
                function ($query) use ($filters) {
                    if (!empty($filters['filter_type_id'])) {
                        $query->where('party_type', $filters['filter_type_id']);
                    }
                    if (!empty($filters['account_group_id'])) {
                        $query->where('account_group_id', $filters['account_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],
            orderBy: 'name'
        );

        $obMap = $this->buildObMap($accounts->pluck('id')->toArray());

        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Name", "class" => "text-start ps-2","width"=>"48%"],
                ["label" => "Parent Group", "class" => "text-start","width"=>"18%"],
                ["label" => "Ope. Bal.(Dr)", "class" => "text-end","width"=>"15%"],
                ["label" => "Ope. Bal.(Cr)", "class" => "text-end","width"=>"15"],
            ],
        ];

        $data = [
            'company'     => $company,
            'accounts'    => $accounts,
            'obMap'       => $obMap,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.account.print', $data)->render();

        return AjaxResponse::success(
            message: 'Account Group fetched successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'csv');
    }

    private function buildObMap(array $accountIds): \Illuminate\Support\Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        return DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->select('vt.account_id', 'vt.debit', 'vt.credit')
            ->whereIn('vt.account_id', $accountIds)
            ->where('v.company_id', company_id())
            ->where('v.financial_year_id', financial_year_id())
            ->where('v.voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('v.is_opening', true)
            ->whereNull('v.deleted_at')
            ->get()
            ->keyBy('account_id');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $filters = $request->currentFilter;
        
        $accounts = $this->repository->all(
            columns: ['*'],
            with: ['accountGroup', 'bankDetail', 'taxDetail', 'preference', 'state', 'country'],
            filters: ['company_id' => company_id(), 'is_hidden' => false],
            scopes: [
                fn($query) => $query->status('active'),
                function ($query) use ($filters) {
                    if (!empty($filters['filter_type_id'])) {
                        $query->where('party_type', $filters['filter_type_id']);
                    }
                    if (!empty($filters['account_group_id'])) {
                        $query->where('account_group_id', $filters['account_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ],
            orderBy: 'name'
        );

        $obMap = $this->buildObMap($accounts->pluck('id')->toArray());


        $headings = ['Name',
                    'Group Name',
                    'Opening Balance(Dr)',
                    'Opening Balance(Cr)',
                    'Address Line 1',
                    'Address Line 2',
                    'Country',
                    'State',
                    'City',
                    'Dealer Type',
                    'Fill.Freq.',                   
                    'Tax Type',
                    'GST Type',
                    'GST No.',
                    'Mobile No.',
                    'WhatsApp No.',
                    'IT PAN',
                    'TIN',
                    'Email',
                    'Station',
                    'PIN',
                    'Distance',
                    'Contact Person',
                    'Transport',
                    'Transport Mode',
                    'Bank Name',
                    'Branch Name',
                    'Account Number',
                    'IFSC Code',
                    'Bill By Bill',
                    'HSN/SAC Code',
                    'ITC Eligibility',
                    'RCM Nature'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_accounts_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new AccountExport($company, $accounts, $headings, $obMap),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );


            return AjaxResponse::success(
                message: 'Accounts  Exported successfully ({$format})',
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Export failed',
                errors: [$e->getMessage()],
            );
        }
    }
}

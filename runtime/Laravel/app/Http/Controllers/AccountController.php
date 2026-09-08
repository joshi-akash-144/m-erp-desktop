<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Repositories\AccountRepository;
use App\Repositories\CommonRepository;
use App\Services\DataTables\AccountDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use App\Services\OpeningBalanceService;
use App\Services\AuditService;
use App\Models\AuditTrail;
use App\Enums\SourceType;

use function Laravel\Prompts\error;

class AccountController extends Controller
{
    protected AccountRepository $repository;
    protected CommonRepository $commonRepository;
    protected AccountDataTable $dataTable;
    protected OpeningBalanceService $openingBalanceService;
    protected AuditService $auditService;
    protected int $companyId;
    protected int $financialYearId;

    public function __construct(AccountRepository $repository, AccountDataTable $dataTable, CommonRepository $commonRepository, OpeningBalanceService $openingBalanceService, AuditService $auditService)
    {
        // Permissions
        $this->middleware('permission:account.list')->only(['index']);
        $this->middleware('permission:account.create')->only(['create', 'store']);
        $this->middleware('permission:account.update')->only(['edit', 'update']);
        $this->middleware('permission:account.delete')->only(['destroy']);
        $this->middleware('permission:account.restore')->only(['restore']);

        $this->repository = $repository;
        $this->commonRepository = $commonRepository;
        $this->dataTable  = $dataTable;
        $this->openingBalanceService = $openingBalanceService;
        $this->auditService = $auditService;
    }

    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query()->where('company_id', company_id())->orderBy('name', 'asc');
            return $this->dataTable->getData($query);
        }
        $accountGroups = $this->commonRepository->getAccountGroups(company_id(), ['name', 'id', 'is_party_group']);
        // $partyTypes  =  Account::where('company_id', company_id())
        //     ->selectRaw('MIN(id) as id, party_type')
        //     ->groupBy('party_type')
        //     ->get();
        $partyTypes = ['customer' => 'Customer', 'supplier' => 'Supplier', 'account' => 'Account'];
        return view('company.pages.masters.account.index', compact('partyTypes', 'accountGroups'));
    }
    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $accountGroups = $this->commonRepository->getAccountGroups(company_id(), ['name', 'id', 'is_party_group']);
        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $taxCategories = $this->commonRepository->getTaxCategories(company_id(), ['name', 'id']);
        $cheques = $this->commonRepository->getCheque(company_id());
        $rtgs = $this->commonRepository->getRtgs();

        $accountGroupsForFieldFV = $this->commonRepository->accountGroupsForFieldValidation(company_id());

        $modalData = [
            'title'         => __('titles.account.add'),
            'uuid'          => Str::uuid(),
            'data'          => null,
            'form_mode'     => 'create',
            'accountGroups' => $accountGroups,
            'accountGroupsFV' => $accountGroupsForFieldFV,
            'countries' => $countries,
            'states' => $states,
            'taxCategories' => $taxCategories,
            'cheques' => $cheques,
            'rtgs' => $rtgs,
            'tdsCategories' => \App\Models\TdsCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'payeeCategories' => \App\Models\PayeeCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'is_tds_applicable' => \App\Models\Company::find(company_id())->tds_applicable ?? 0,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.account._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Store new account */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['financial_year_id'] = financial_year_id();
            $validated['financial_year_start'] = financial_year_start();

            $account = DB::transaction(function () use ($validated) {
                $account = $this->repository->create($validated);

                $validated['account_id'] = $account->id;

                $this->openingBalanceService
                    ->createOpeningBalanceEntry($account->id, $validated);

                return $account; // ✅ return from transaction
            });
            
            $this->logAudit(AuditTrail::ACTION_CREATE, $account, [], $this->getAccountState($account));

            return AjaxResponse::success(
                message: __('messages.account.created'),
                data: $account,
                code: 201
            );
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }


    /** Load View Modal */
    public function show(Request $request, Account $account): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $account->load(['bankDetail', 'taxDetail', 'preference']);

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $balanceInfo =  $this->openingBalanceService
            ->getOpeningBalanceForAccountId($companyId, $financialYearId, $account->id);
        $account->opening_balance = $balanceInfo['opening_balance'];
        $account->opening_type = $balanceInfo['opening_type'];


        $accountGroups = $this->commonRepository->getAccountGroups(company_id(), ['name', 'id', 'is_party_group']);
        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $taxCategories = $this->commonRepository->getTaxCategories(company_id(), ['name', 'id']);
        $accountGroupsForFieldFV = $this->commonRepository->accountGroupsForFieldValidation(company_id(), ['name', 'id']);
        $cheques = $this->commonRepository->getCheque(company_id());
        $rtgs = $this->commonRepository->getRtgs();

        $modalData = [
            'title'         => __('titles.account.view'),
            'uuid'          => null,
            'data'          => $account,
            'form_mode'     => 'view',
            'accountGroups' => $accountGroups,
            'accountGroupsFV' => $accountGroupsForFieldFV,
            'countries' => $countries,
            'states' => $states,
            'taxCategories' => $taxCategories,
            'cheques' => $cheques,
            'rtgs' => $rtgs,
            'tdsCategories' => \App\Models\TdsCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'payeeCategories' => \App\Models\PayeeCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'is_tds_applicable' => \App\Models\Company::find(company_id())->tds_applicable ?? 0,
        ];

        // dd($modalData);
        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.account._modal', compact('modalData'))->render()
        ]);;
    }

    /** Load Edit Modal */
    public function edit(Request $request, Account $account): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $account->load(['bankDetail', 'taxDetail', 'preference']);

        $companyId = company_id();
        $financialYearId = financial_year_id();

        $balanceInfo =  $this->openingBalanceService
            ->getOpeningBalanceForAccountId($companyId, $financialYearId, $account->id);
        $account->opening_balance = $balanceInfo['opening_balance'];
        $account->opening_type = $balanceInfo['opening_type'];


        $accountGroups = $this->commonRepository->getAccountGroups(company_id(), ['name', 'id', 'is_party_group']);
        $countries = $this->commonRepository->getCountries(['name', 'id']);
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $taxCategories = $this->commonRepository->getTaxCategories(company_id(), ['name', 'id']);
        $accountGroupsForFieldFV = $this->commonRepository->accountGroupsForFieldValidation(company_id(), ['name', 'id']);
        $cheques = $this->commonRepository->getCheque(company_id());
        $rtgs = $this->commonRepository->getRtgs();

        $modalData = [
            'title'         => __('titles.account.edit'),
            'uuid'         => $account->uuid,
            'data'          => $account,
            'form_mode'     => 'edit',
            'accountGroups' => $accountGroups,
            'accountGroupsFV' => $accountGroupsForFieldFV,
            'countries' => $countries,
            'states' => $states,
            'taxCategories' => $taxCategories,
            'cheques' => $cheques,
            'rtgs' => $rtgs,
            'tdsCategories' => \App\Models\TdsCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'payeeCategories' => \App\Models\PayeeCategory::where('company_id', company_id())->where('is_active', 1)->get(),
            'is_tds_applicable' => \App\Models\Company::find(company_id())->tds_applicable ?? 0,
        ];


        return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
            'html' => view('company.pages.masters.account._modal', compact('modalData'))->render()
        ]);
    }

    /** Update account */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        try {
            $oldState = $this->getAccountState($account);
            
            $validated = $request->validated();
            $validated['company_id']          = company_id();
            $validated['financial_year_id']   = financial_year_id();
            $validated['financial_year_start'] = financial_year_start();
            $validated['account_id']          = $account->id;
            $validated['rtgs_form_view_id'] = $validated['rtgs_id'] ?? null;

            $updated = DB::transaction(function () use ($validated, $account) {
                $updated = $this->repository->update($account, $validated);
                $this->openingBalanceService->updateOpeningBalanceEntry($account->id, $validated);
                return $updated;
            });
            
            $this->logAudit(AuditTrail::ACTION_UPDATE, $updated, $oldState, $this->getAccountState($updated));

            return AjaxResponse::success(message: __('messages.account.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Delete account */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            // if ($this->repository->hasTransactions($account)) {
            //     return AjaxResponse::error(__('messages.account.has_transactions'));
            // }
            
            $oldState = $this->getAccountState($account);
            
            $account->deleted_by = current_user_id();
            $account->save();
            $this->repository->delete($account);
            
            $this->logAudit(AuditTrail::ACTION_DELETE, $account, $oldState, []);

            return AjaxResponse::success(message: __('messages.account.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    private function getAccountState(Account $account): array
    {
        $account->loadMissing(['bankDetail', 'taxDetail', 'preference']);
        $data = $account->toArray();
        
        // Remove unwanted fields to keep audit clean
        \Illuminate\Support\Arr::forget($data, [
            'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by',
            'bank_detail.id', 'bank_detail.account_id', 'bank_detail.created_at', 'bank_detail.updated_at',
            'tax_detail.id', 'tax_detail.account_id', 'tax_detail.created_at', 'tax_detail.updated_at',
            'preference.id', 'preference.account_id', 'preference.created_at', 'preference.updated_at',
        ]);
        return $data;
    }

    private function logAudit(string $action, Account $account, array $oldValues = [], array $newValues = []): void
    {
        if (!isAuditLog()) return;

        $this->auditService->log([
            'company_id'        => $account->company_id,
            'financial_year_id' => financial_year_id(),
            'action'            => $action,
            'module'            => SourceType::ACCOUNT,
            'record_type'       => AuditTrail::RECORD_TYPE_MASTER,
            'model_name'        => Account::class,
            'source_id'         => $account->id,
            'voucher_id'        => $account->id, 
            'reference_number'  => $account->code,
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ]);
    }
}

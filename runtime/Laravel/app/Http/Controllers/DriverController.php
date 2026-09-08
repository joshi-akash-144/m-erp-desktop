<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Account;
use App\Services\DataTables\DriverDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use App\Models\Vehicle;
use App\Models\AccountGroup;
use App\Repositories\CommonRepository;
use App\Services\OpeningBalanceService;

class DriverController extends Controller
{
    protected DriverDataTable $dataTable;
    protected CommonRepository $commonRepository;
    protected OpeningBalanceService $openingBalanceService;

    protected int $companyId;

    public function __construct(DriverDataTable $dataTable, CommonRepository $commonRepository, OpeningBalanceService $openingBalanceService)
    {
        $this->middleware('permission:driver.list')->only(['index']);
        $this->middleware('permission:driver.create')->only(['create', 'store']);
        $this->middleware('permission:driver.update')->only(['edit', 'update']);
        $this->middleware('permission:driver.delete')->only('destroy');
        
        $this->dataTable = $dataTable;
        $this->commonRepository = $commonRepository;
        $this->openingBalanceService = $openingBalanceService;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Driver::query()
                ->where('company_id', company_id())
                ->orderBy(Account::select('name')->whereColumn('accounts.id', 'drivers.account_id'));
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.driver.index');
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $cheques = $this->commonRepository->getCheque();
        $accountGroups = $this->getDebtorAccountGroups();

        $modalData = [
            'title'         => 'Add Driver',
            'uuid'          => Str::uuid(),
            'data'          => null,
            'form_mode'     => 'create',
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'vehicles'      => Vehicle::select('name', 'id')->where('company_id', session('company_id'))->get(),
            'cheques'       => $cheques,
            'accountGroups' => $accountGroups,
        ];       
         return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.driver._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {        
        try {
            $validated = $request->validated();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id(); 

            if(empty($validated['account_id'])) {
                $nextCode = (Account::where('company_id', company_id())->max('code') ?? 0) + 1;                
                $account = Account::create([
                    'uuid' => $request->uuid,
                    'code' => $nextCode,
                    'name' => $validated['name'],    
                    'print_name' => $validated['name'],                                               
                    'account_group_id' =>  $validated['account_group_id'] ?? 8,
                    'mobile_number' => $validated['mobile_number'] ?? null,                    
                    'postal_code' => $validated['postal_code'] ?? null,
                    'address_one' => $validated['address_one'] ?? null,
                    // 'party_type' => $validated['party_type'] ?? 'driver',
                    'is_billwise' => true,
                    'company_id' => company_id(),
                    'created_by' => current_user_id(),
                ]);
               
                $validated['account_id'] = $account->id;
            } else {
                $account = Account::find($validated['account_id']);
            }
            
            // Store bank details on the linked Account
            if ($account) {
                $account->bankDetail()->create(                   
                    [
                        'bank_name'             => $validated['bank_name'] ?? null,
                        'bank_branch_name'      => $validated['bank_branch_name'] ?? null,
                        'bank_account_number'   => $validated['bank_account_number'] ?? null,
                        'bank_ifsc'             => $validated['bank_ifsc'] ?? null,
                        'cheque_id'             => null,
                    ]
                );
            
                $account->taxDetail()->create(  [
                    'pan'     => $validated['pan'] ?? null,                        
                ]);

                $validated['financial_year_id'] = financial_year_id();
                $validated['financial_year_start'] = financial_year_start();
                $validated['opening_type'] = $validated['opening_type'] ?? 'D';
                $this->openingBalanceService->createOpeningBalanceEntry($account->id, $validated);
            }

            $driver = Driver::create($validated);

            return AjaxResponse::success(message: 'Driver created successfully!', data: $driver, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function show(Request $request, Driver $driver): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $driver->load('account.bankDetail', 'account.bankDetail:id,account_id,bank_name,bank_branch_name,bank_account_number,bank_ifsc');       
        $cheques = $this->commonRepository->getCheque();       
        $accountGroups = $this->getDebtorAccountGroups
();
        
        if ($driver->account) {
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $balanceInfo =  $this->openingBalanceService
                        ->getOpeningBalanceForAccountId($companyId, $financialYearId, $driver->account->id);
            $driver->account->opening_balance = $balanceInfo['opening_balance'];
            $driver->account->opening_type = $balanceInfo['opening_type'];
        }

        $modalData = [
            'title'         => 'View Driver',
            'uuid'          => $driver->uuid,
            'data'          => $driver,
            'form_mode'     => 'view',
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'vehicles'      => Vehicle::select('name', 'id')->where('company_id', session('company_id'))->get(),
            'cheques'       => $cheques,
            'accountGroups' => $accountGroups,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.driver._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function edit(Request $request, Driver $driver): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $driver->load('account.bankDetail', 'account.bankDetail:id,account_id,bank_name,bank_branch_name,bank_account_number,bank_ifsc');    
        // dd($driver->toArray());
        $cheques = $this->commonRepository->getCheque();
        $accountGroups = $this->getDebtorAccountGroups
();

        if ($driver->account) {
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $balanceInfo =  $this->openingBalanceService
                        ->getOpeningBalanceForAccountId($companyId, $financialYearId, $driver->account->id);
            $driver->account->opening_balance = $balanceInfo['opening_balance'];
            $driver->account->opening_type = $balanceInfo['opening_type'];
        }

        $modalData = [
            'title'         => 'Edit Driver',
            'uuid'          => $driver->uuid,
            'data'          => $driver,
            'form_mode'     => 'edit',
            'accounts'      => Account::where('company_id', session('company_id'))->where('party_type', 'account')->get(),
            'vehicles'      => Vehicle::select('name', 'id')->where('company_id', session('company_id'))->get(),
            'cheques'       => $cheques,
            'accountGroups' => $accountGroups,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.driver._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = current_user_id();

            // Update the driver's name on the linked Account
            if ($driver->account_id) {
                $account = Account::find($driver->account_id);
                if ($account) {
                    $account->update([
                        'name' => $validated['name'] ?? $account->name,
                        'party_type' => $validated['party_type'] ?? $account->party_type,
                        'account_group_id' => $validated['account_group_id'] ?? $account->account_group_id,
                        'mobile_number' => $validated['mobile_number'] ?? $account->mobile_number,                        
                        'postal_code' => $validated['postal_code'] ?? $account->postal_code,
                        'address_one' => $validated['address_one'] ?? $account->address_one,
                        'is_billwise' => true,
                    ]);
                    $account->bankDetail()->update(                        
                        [
                            'bank_name'             => $validated['bank_name'] ?? null,
                            'bank_branch_name'      => $validated['bank_branch_name'] ?? null,
                            'bank_account_number'   => $validated['bank_account_number'] ?? null,
                            'bank_ifsc'             => $validated['bank_ifsc'] ?? null,
                            // 'cheque_id'             => $validated['cheque_id'] ?? null,
                        ]
                    );
                    $account->taxDetail()->update(  [
                            'pan'     => $validated['pan'] ?? null,                         
                    ]); 

                    $validated['company_id']          = company_id();
                    $validated['financial_year_id']   = financial_year_id();
                    $validated['financial_year_start'] = financial_year_start();
                    $validated['opening_type'] = $validated['opening_type'] ?? 'D';
                    $this->openingBalanceService->updateOpeningBalanceEntry($account->id, $validated);
                }
            }

            $driver->update($validated);

            return AjaxResponse::success(message: 'Driver updated successfully!', data: $driver, code: 200);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function destroy(Request $request, Driver $driver): JsonResponse
    {
        dd('working...');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $driver->delete();
            return AjaxResponse::success(message: 'Driver deleted successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function getDebtorAccountGroups()
    {
        $companyId = company_id();
        $creditorGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '270')
            ->first();
        
        if (!$creditorGroup) {
            return collect(); 
        }
        
        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $creditorGroup->id)
            ->pluck('id')
            ->toArray();       
        $allGroupIds = array_merge([$creditorGroup->id], $childGroups);
           
        return AccountGroup::whereIn('id', $allGroupIds)->get(['id', 'name']);
    }
}

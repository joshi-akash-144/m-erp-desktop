<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\AjaxResponse;
use App\Models\BankConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;
use App\Repositories\CommonRepository;
use App\Repositories\BankConfigurationRepository;
use App\Http\Requests\StoreBankConfigurationRequest;
use App\Http\Requests\UpdateBankConfigurationRequest;
use App\Services\DataTables\BankConfigurationDataTable;

class BankConfigurationController extends Controller
{
  protected BankConfigurationRepository $repository;
  protected CommonRepository $commonRepository;
  protected BankConfigurationDataTable $dataTable;
  protected int $companyId;

  public function __construct(BankConfigurationRepository $repository, BankConfigurationDataTable $dataTable, CommonRepository $commonRepository)
  {
    // Apply middleware for permissions
    $this->middleware('permission:bank_configuration.list')->only(['index']);
    $this->middleware('permission:bank_configuration.create')->only(['create', 'store']);
    $this->middleware('permission:bank_configuration.update')->only(['edit', 'update']);
    $this->middleware('permission:bank_configuration.delete')->only('destroy');

    $this->repository = $repository;
    $this->dataTable = $dataTable;
    $this->commonRepository = $commonRepository;
  }

  /** Show index page */
  public function index(Request $request): View | JsonResponse
  {
      if($request->ajax()){
          $query = $this->repository->query()->where('company_id', company_id());
          return $this->dataTable->getData($query);
      }

      $groups  =  BankConfiguration::select('bank_id', 'id')->where('company_id', company_id())->get();

      return view('company.pages.masters.bank-configuration.index', compact('groups'));
  }

  /** Load Create Modal */
  public function create(Request $request): JsonResponse
  {
    if (!$request->ajax()) {
      return AjaxResponse::error(__('messages.request.type'));
    }
    // $accounts = Account::with(['bankDetail:id,bank_name,account_id'])->get();
    $accounts = $this->commonRepository->getBankDetails();
    // var_dump((array) $accounts);
    // dd($accounts);
    // dd($accounts->toArray());
    $modalData = [
      'title'     => __('titles.bank_configuration.add'),
      'uuid'      => Str::uuid(),
      'data'      => null,
      'form_mode' => 'create',
      'bank_detail'=>$accounts,
    ];
    return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bank-configuration._modal', compact('modalData'))->render()],
            code: 200
        );
  }

  /** Store */
  public function store(StoreBankConfigurationRequest $request): JsonResponse
  {
    try {
      $validated = $request->validated();
      $validated['uuid'] = $request->uuid;
      $validated['company_id'] = company_id();
      $validated['created_by'] = current_user_id();
      // dd($validated);
      $bankConfig = $this->repository->create($validated);
      return AjaxResponse::success(__('messages.bank_configuration.created'), $bankConfig, 201);
    } catch (Exception $e) {
      return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
    }
  }
  /** Load View Modal */
  public function show(Request $request, BankConfiguration $bankConfiguration): JsonResponse
  {
    if (!$request->ajax()) {
      return AjaxResponse::error(__('messages.request.type'));
    }
    $accounts = $this->commonRepository->getBankDetails();

    $modalData = [
      'title'     => __('titles.bank_configuration.view'),
      'uuid'      => $bankConfiguration->uuid,
      'data'      => $bankConfiguration,
      'form_mode' => 'view',
      'bank_detail' => $accounts,
    ];
    // dd($modalData);
    return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
      'html' => view('company.pages.masters.bank-configuration._modal', compact('modalData'))->render()
    ]);
  }
  /** Load Edit Modal */
  public function edit(Request $request, BankConfiguration $bankConfiguration): JsonResponse
  {
    if (!$request->ajax()) {
      return AjaxResponse::error(__('messages.request.type'));
    }
    $accounts = $this->commonRepository->getBankDetails();

    $modalData = [
      'title'     => __('titles.bank_configuration.edit'),
      'uuid'      => $bankConfiguration->uuid,
      'data'      => $bankConfiguration,
      'form_mode' => 'edit',
      'bank_detail' => $accounts,
    ];

    return AjaxResponse::success(__('messages.modal.load_success'), [
      'html' => view('company.pages.masters.bank-configuration._modal', compact('modalData'))->render()
    ]);
  }

  /** Update */
  public function update(UpdateBankConfigurationRequest $request, BankConfiguration $bankConfiguration): JsonResponse
  {
    try {
      $validated = $request->validated();
      $validated['updated_by'] = current_user_id();
      $updated = $this->repository->update($bankConfiguration, $validated);

      return AjaxResponse::success(__('messages.bank_configuration.updated'), $updated);
    } catch (Exception $e) {
      return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
    }
  }

  /** Delete */
  public function destroy(Request $request, BankConfiguration $bankConfiguration): JsonResponse
  {
    dd('hi...');
    if (!$request->ajax()) {
      return AjaxResponse::error(__('messages.request.type'));
    }

    try {
      $this->repository->delete($bankConfiguration);
      return AjaxResponse::success(__('messages.bank_configuration.deleted'));
    } catch (Exception $e) {
      return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
    }
  }

  /** Restore */
  public function restore(Request $request, string $uuid): JsonResponse
  {
    if (!$request->ajax()) {
      return AjaxResponse::error(__('messages.request.type'));
    }

    try {
      $this->repository->restoreByUuid($uuid);
      return AjaxResponse::success(__('messages.bank_configuration.restored'));
    } catch (Exception $e) {
      return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
    }
  }
}

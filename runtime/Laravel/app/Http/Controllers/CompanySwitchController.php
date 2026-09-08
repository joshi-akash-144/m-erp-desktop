<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Models\UserLoginSession;
use App\Repositories\CommonRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CompanySwitchController extends Controller
{
    protected CommonRepository $repository;

    public function __construct(CommonRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Block access if user already has an active company session
        if (session()->has('company_id') && session()->has('financial_year_id')) {
            return redirect()->route('dashboard')
                             ->with('info', 'You are already in a company. Exit first to switch.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $companies = $user->companies()->get();

        return view('company-selector.pages.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'string',
                'uuid',
                'exists:companies,uuid',
            ],
        ]);
        
        $company = Company::where('uuid', $validated['company_id'])->select('id', 'name', 'state_id','company_type')->first();

        // Check if company exists and has current financial year
        if (!$company) {
            return AjaxResponse::error('Invalid company.');
        }

        if (!$company->currentFinancialYear) {
            return AjaxResponse::error('Financial year not found for this company.');
        }

        session([
            'company_id'           => $company->id,
            'financial_year_id'    => $company->currentFinancialYear->id ?? null,
            'company_name'         => $company->name,
            'financial_year_name'  => $company->currentFinancialYear->name ?? null,
            'financial_year_start' => $company->currentFinancialYear->start_date ?? null,
            'financial_year_end'   => $company->currentFinancialYear->end_date ?? null,
            'company_state_id'     => $company->state_id,
            'company_uuid'         => $company->uuid,
            'company_type'         => $company->company_type
        ]);

        UserLoginSession::where('user_id', Auth::id())
            ->whereNull('logout_at')
            ->update([
                'company_name'        => $company->name,
                'financial_year_name' => $company->currentFinancialYear->name ?? null,
            ]);

        return AjaxResponse::success('Company and financial year switched successfully.', [
            'company_id'        => $company->id,
            'financial_year_id' => $company->currentFinancialYear->id,
            'url'               => route('dashboard'),
            'uuid'              => $company->uuid,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $companies = $user->companies()->get();

        $selectCompanyModal = view('pages.company-selection.modal-company-selection', compact('companies'))->render();

        return AjaxResponse::success('Company selector modal loaded successfully.', [
            'html' => $selectCompanyModal,
        ]);
    }


    public function exit(): JsonResponse
    {
        try {
            $companyId = session('company_id');

            // Forget all company-related session values
            session()->forget([
                'company_id',
                'financial_year_id',
                'company_name',
                'financial_year_name',
                'financial_year_start',
                'financial_year_end',
                'company_state_id',
                'company_uuid',
                'file_number',
                'company_type',
                'rtgs_payable_amt_' . $companyId
            ]);

            return AjaxResponse::success(
                message: 'Company exited successfully.',
                data: ['url' => route('company-selection.index')]
            );
        } catch (Throwable $e) {
            report($e);

            // Return a structured error response
            return AjaxResponse::error(
                message: 'Something went wrong while exiting the company. Please try again later.',
                errors: []
            );
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

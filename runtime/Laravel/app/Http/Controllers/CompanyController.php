<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Repositories\CommonRepository;
use App\Repositories\CompanyRepository;
use App\Services\CompanyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;; 
use Illuminate\Routing\Controller;
use App\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{
    protected CompanyRepository $repository;
    protected CompanyService $service;
    protected CommonRepository $commonRepo;
    // protected TaxCategoryDataTable $dataTable;
    protected int $companyId;

    public function __construct(CompanyRepository $repository,CommonRepository $commonRepo, CompanyService $service
    // , TaxCategoryDataTable $dataTable
    )
    {
        // Apply middleware for permissions
        $this->middleware('permission:company.list')->only(['index', 'list']);
        $this->middleware('permission:company.create')->only(['create', 'store']);
        $this->middleware('permission:company.update')->only(['edit', 'update']);
        $this->middleware('permission:company.delete')->only('destroy');
        $this->middleware('permission:company.restore')->only('restore');

        $this->repository = $repository;
        $this->service = $service;
        $this->commonRepo = $commonRepo;
        // $this->dataTable = $dataTable;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        $states = $this->commonRepo->getStates();
        $countries = $this->commonRepo->getCountries();
        return view('company-selector.pages.company.create', compact('states','countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        try {
            $validated = $request->validated();
            $company = $this->service->createCompany($validated);

            return AjaxResponse::success(message: __('messages.company.created'), data: $company, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
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
    public function edit(string $id)
    {
        try {
            $company = Company::find($id); 
            $states = $this->commonRepo->getStates();
            $countries = $this->commonRepo->getCountries();

            return view('company-selector.pages.company.edit', compact('company','states','countries'));
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, string $id)
    {
        try {
            $validated = $request->validated();
            $company = Company::find($id);
           
            $updated = $this->repository->update($company, $validated);

            if($updated->tds_applicable){
                 $this->syncTds($updated->id);  
            }

            return AjaxResponse::success(message: __('messages.company.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function syncTds($companyId) {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $payeeCategories = [
            ['payee_category' => 'Individual - Residents'],
            ['payee_category' => 'Individual - Non Residents'],
            ['payee_category' => 'Domestic Company'],
            ['payee_category' => 'Foreign Company'],
            ['payee_category' => 'Hindu Undivided Family'],
            ['payee_category' => 'Partnership Firm'],
            ['payee_category' => 'Association of Persons'],
            ['payee_category' => 'Body of Individuals'],
            ['payee_category' => 'Co-operative Society'],
            ['payee_category' => 'Trust'],
        ];   

        $payeeCode = 401;
        foreach ($payeeCategories as $category) {
            \App\Models\PayeeCategory::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'payee_category' => $category['payee_category']
                ],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'code' => $payeeCode++,
                    'status' => 1,
                    'is_active' => 1
                ]
            );
        }

        $tdsCategories = [
            ['section' => '192', 'category_name' => 'Salary'],
            ['section' => '192A', 'category_name' => 'EPF Withdrawal'],
            ['section' => '193', 'category_name' => 'Interest on Securities'],
            ['section' => '194', 'category_name' => 'Dividend'],
            ['section' => '194A', 'category_name' => 'Bank/Post Office Interest'],
            ['section' => '194B', 'category_name' => 'Lottery Winnings'],
            ['section' => '194BB', 'category_name' => 'Horse Race Winnings'],
            ['section' => '194C', 'category_name' => 'Contractor/ Sub-contractor'],
            ['section' => '194D', 'category_name' => 'Insurance Commission'],
            ['section' => '194DA', 'category_name' => 'Life Insurance Maturity'],
            ['section' => '194E', 'category_name' => 'Non-resident Entertainers'],
            ['section' => '194H', 'category_name' => 'Commission or Brokerage'],
            ['section' => '194I', 'category_name' => 'Rent (Land/Building)'],
            ['section' => '194IA', 'category_name' => 'Property Sale >= ₹50L'],
            ['section' => '194J', 'category_name' => 'Professional Services'],
            ['section' => '194K', 'category_name' => 'Mutual Fund Income'],
            ['section' => '194N', 'category_name' => 'Cash Withdrawal > ₹1 Cr'],
            ['section' => '194O', 'category_name' => 'E-commerce Operator'],
            ['section' => '194Q', 'category_name' => 'Purchase of Goods'],
            ['section' => '206AB', 'category_name' => 'Higher TDS for Non-Filers'],
            ['section' => '206C', 'category_name' => 'TCS (Various)'],
        ];

        $tdsCode = 501;
        foreach ($tdsCategories as $category) {
            $tdsCategory = \App\Models\TdsCategory::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'section' => $category['section']
                ],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'category_name' => $category['category_name'],
                    'code' => $tdsCode++,
                    'status' => 1,
                    'is_active' => 1
                ]
            );

            if($tdsCategory->code == 519){
                \App\Models\TdsEntry::where('company_id', $companyId)->update([
                    'tds_category_id' => $tdsCategory->id,
                    'section_code' => $tdsCategory->code
                ]);
            }

        }

        $payeeCatMap = \App\Models\PayeeCategory::where('company_id', $companyId)->pluck('id', 'payee_category');
        $tdsCatMap = \App\Models\TdsCategory::where('company_id', $companyId)->pluck('id', 'section');

        $tdsCategoryDetails = [
            // 192 - Salary
            ['section' => '192', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 0, 'tds_with_pan' => 0, 'tds_without_pan' => 0],
            // 192A - EPF Withdrawal
            ['section' => '192A', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 50000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 193 - Interest on Securities
            ['section' => '193', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 10000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            ['section' => '193', 'payee_category' => 'Domestic Company', 'threshold_limit' => 10000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194 - Dividend
            ['section' => '194', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 5000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            ['section' => '194', 'payee_category' => 'Domestic Company', 'threshold_limit' => 5000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194A - Bank Interest
            ['section' => '194A', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 40000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            ['section' => '194A', 'payee_category' => 'Domestic Company', 'threshold_limit' => 40000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194B - Lottery
            ['section' => '194B', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 10000, 'tds_with_pan' => 30.00, 'tds_without_pan' => 30.00],
            // 194BB - Horse Race
            ['section' => '194BB', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 10000, 'tds_with_pan' => 30.00, 'tds_without_pan' => 30.00],
            // 194C - Contractors
            ['section' => '194C', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 100000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 20.00],
            ['section' => '194C', 'payee_category' => 'Hindu Undivided Family', 'threshold_limit' => 100000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 20.00],
            ['section' => '194C', 'payee_category' => 'Domestic Company', 'threshold_limit' => 100000, 'tds_with_pan' => 2.00, 'tds_without_pan' => 20.00],
            ['section' => '194C', 'payee_category' => 'Partnership Firm', 'threshold_limit' => 100000, 'tds_with_pan' => 2.00, 'tds_without_pan' => 20.00],
            // 194D - Insurance Comm
            ['section' => '194D', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 15000, 'tds_with_pan' => 5.00, 'tds_without_pan' => 20.00],
            ['section' => '194D', 'payee_category' => 'Domestic Company', 'threshold_limit' => 15000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194DA - Life Ins Maturity
            ['section' => '194DA', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 100000, 'tds_with_pan' => 5.00, 'tds_without_pan' => 20.00],
            // 194E - Non-resident Ent
            ['section' => '194E', 'payee_category' => 'Individual - Non Residents', 'threshold_limit' => 0, 'tds_with_pan' => 20.00, 'tds_without_pan' => 20.00],
            // 194H - Brokerage
            ['section' => '194H', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 15000, 'tds_with_pan' => 5.00, 'tds_without_pan' => 20.00],
            ['section' => '194H', 'payee_category' => 'Domestic Company', 'threshold_limit' => 15000, 'tds_with_pan' => 5.00, 'tds_without_pan' => 20.00],
            // 194I - Rent
            ['section' => '194I', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 240000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            ['section' => '194I', 'payee_category' => 'Domestic Company', 'threshold_limit' => 240000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194IA - Prop Sale
            ['section' => '194IA', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 5000000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 20.00],
            ['section' => '194IA', 'payee_category' => 'Domestic Company', 'threshold_limit' => 5000000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 20.00],
            // 194J - Prof Services
            ['section' => '194J', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 30000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            ['section' => '194J', 'payee_category' => 'Domestic Company', 'threshold_limit' => 30000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194K - Mutual Fund
            ['section' => '194K', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 5000, 'tds_with_pan' => 10.00, 'tds_without_pan' => 20.00],
            // 194N - Cash Withdraw
            ['section' => '194N', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 10000000, 'tds_with_pan' => 2.00, 'tds_without_pan' => 20.00],
            ['section' => '194N', 'payee_category' => 'Domestic Company', 'threshold_limit' => 10000000, 'tds_with_pan' => 2.00, 'tds_without_pan' => 20.00],
            // 194O - E-commerce
            ['section' => '194O', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 500000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 5.00],
            ['section' => '194O', 'payee_category' => 'Domestic Company', 'threshold_limit' => 500000, 'tds_with_pan' => 1.00, 'tds_without_pan' => 5.00],
            // 194Q - Purchase Goods
            ['section' => '194Q', 'payee_category' => 'Domestic Company', 'threshold_limit' => 5000000, 'tds_with_pan' => 0.10, 'tds_without_pan' => 5.00],
            ['section' => '194Q', 'payee_category' => 'Partnership Firm', 'threshold_limit' => 5000000, 'tds_with_pan' => 0.10, 'tds_without_pan' => 5.00],
            ['section' => '194Q', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 5000000, 'tds_with_pan' => 0.10, 'tds_without_pan' => 5.00],
            // 206AB - Higher TDS
            ['section' => '206AB', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 0, 'tds_with_pan' => 5.00, 'tds_without_pan' => 5.00],
            ['section' => '206AB', 'payee_category' => 'Domestic Company', 'threshold_limit' => 0, 'tds_with_pan' => 5.00, 'tds_without_pan' => 5.00],
            // 206C - TCS
            ['section' => '206C', 'payee_category' => 'Individual - Residents', 'threshold_limit' => 0, 'tds_with_pan' => 0, 'tds_without_pan' => 0],
            ['section' => '206C', 'payee_category' => 'Domestic Company', 'threshold_limit' => 0, 'tds_with_pan' => 0, 'tds_without_pan' => 0],
        ];

        foreach ($tdsCategoryDetails as $detail) {
            $tdsId = $tdsCatMap[$detail['section']] ?? null;
            $payeeId = $payeeCatMap[$detail['payee_category']] ?? null;

            if ($tdsId && $payeeId) {
                \App\Models\TdsCategoryDetail::updateOrCreate(
                    [
                        'tds_category_id' => $tdsId,
                        'payee_category_id' => $payeeId,
                    ],
                    [
                        'threshold_limit' => $detail['threshold_limit'],
                        'tds_with_pan' => $detail['tds_with_pan'],
                        'tds_without_pan' => $detail['tds_without_pan'],
                    ]
                );
            }

            
        }

        

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error in syncTds: ' . $e->getMessage());
        }
    }
}

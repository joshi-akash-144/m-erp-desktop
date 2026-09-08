<?php

namespace App\Http\Controllers;

use App\Models\DairyAnalysis;
use App\Services\DairyAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Services\SalesInvoiceService;
use App\Services\CompanyService;
use App\Models\Account;
use App\Models\Destination;
use App\Services\MasterDataService;
use App\Services\LookupService;
use App\Repositories\CommonRepository;
use Illuminate\Routing\Controller;

class DairyAnalysisController extends Controller
{
    protected DairyAnalysisService $dairyAnalysisService;
    protected $company;

    protected $companyService;
        
    protected MasterDataService $masterService;
    
    protected LookupService $lookupService;

    protected CommonRepository $commonRepository;

   public function __construct(DairyAnalysisService $dairyAnalysisService, MasterDataService $masterService, LookupService $lookupService, CompanyService $companyService , CommonRepository $commonRepository)
    {
        $this->middleware('permission:dairy_analysis.index')->only(['index']);
        $this->middleware('permission:dairy_analysis.edit')->only(['edit', 'getAnalysisById']);
        $this->middleware('permission:dairy_analysis_report.register')->only(['register']);
        $this->middleware('permission:dairy_analysis_report.rebate_pending_report')->only(['dairyRebatePending']);
        $this->dairyAnalysisService = $dairyAnalysisService;
        $this->masterService = $masterService;       
        $this->commonRepository = $commonRepository;
        $this->companyService = $companyService;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {                  
         if ($request->ajax()) {

            $filters = $request->only(
                'start_date',
                'end_date',
                'account_id',
                'supplier_id',
                'destination_id',
                'payment_status',
                'size'
            );

            $data = $this->dairyAnalysisService->getAnalysisList($filters, company_id(), financial_year_id());
            return response()->json($data);
        }

        $masterData = $this->masterService->dairyAnalysisMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.dairy-analysis.index', compact('customers', 'suppliers', 'destinations'));
    }
    public function create(SalesInvoiceService $salesInvoiceService)
    {
        $invoiceSerials = $salesInvoiceService->getInvoiceSerial(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        return view('company.pages.dairy-analysis.create', compact('invoiceSerials'));
    }

    public function edit()
    {
        $analysisList = $this->dairyAnalysisService->getAnalysisDropdownList(company_id(), financial_year_id());
        return view('company.pages.dairy-analysis.edit', compact('analysisList'));
    }

    public function getAnalysisById(Request $request)
    {
        $id = (int)$request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Analysis ID is required.']);
        }

        $data = $this->dairyAnalysisService->getAnalysisDataById($id, company_id(), financial_year_id());

        if (isset($data['message'])) {
            return response()->json(['success' => false, 'message' => $data['message']]);
        }

        return response()->json(array_merge(['success' => true], $data));
    }

    public function getSalesInv(Request $request)
    {
        $invoiceSerial = $request->input('invoice_serial');
        $data = $this->dairyAnalysisService->getInvoiceData(invoiceSerial: $invoiceSerial,companyId: company_id(),financialYearId: financial_year_id());

        if(isset($data['is_duplicate']) && !empty($data['is_duplicate'])){
            
        }
        
        if (empty($data) || isset($data['message'])) { 
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'No data found for the given Sales Bill Number.'
            ]);
        }

        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_bill_id'     => 'required|exists:sales_invoices,id',
            'purchase_bill_id'  => 'required|exists:purchase_invoices,id',
            'uuid'              => 'required',
            'items'             => 'required|array',
        ]);
        
        try {
            $analysis = $this->dairyAnalysisService->storeAnalysis($request->all(), company_id(), financial_year_id());
            return response()->json([
                'success' => true, 
                'message' => 'DairyAnalysis saved successfully',
                'id' => $analysis->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {        
        $request->validate([
            'sales_bill_id'     => 'required|exists:sales_invoices,id',
            'purchase_bill_id'  => 'required|exists:purchase_invoices,id',
            'uuid'              => 'required',
            'items'             => 'required|array',
        ]);
        
        try {
            $this->dairyAnalysisService->updateAnalysis($id, $request->all());
            return response()->json([
                'success' => true, 
                'message' => 'Dairy Analysis updated successfully',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function register(Request $request) 
    {
        if ($request->ajax()) {

            $filters = $request->only(
                'start_date',
                'end_date',
                'account_id',
                'supplier_id',
                'destination_id',
                'payment_status',
                'size'
            );

            $data = $this->dairyAnalysisService->getAnalysisList($filters, company_id(), financial_year_id());
            return response()->json($data);
        }

        $masterData = $this->masterService->dairyAnalysisMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.dairy-analysis.list', compact('customers', 'suppliers', 'destinations'));
    }
    
    
    public function printAnalysisReport(DairyAnalysis $dairyAnalysis)
    {
        $analysis = $dairyAnalysis->load(['details.element', 'purchaseInvoice.details.item', 'purchaseInvoice.details.destination', 'purchaseInvoice.details.condition', 'purchaseInvoice.account']);  
        $companyName = $this->companyService->current(company_id())->name;  
        $companyAddress = $this->companyService->current(company_id())->address_one ?? $this->companyService->current(company_id())->address_two ?? '';
        $mobileNo = $this->companyService->current(company_id())->mobile_number ?? '';
        $postalCode = $this->companyService->current(company_id())->postal_code ?? '';
        // calculate percentage in 
        $analysis = $this->dairyAnalysisService->preparePrintReportData($analysis);
    
        return view('company.pages.dairy-analysis.payment_difference_print', compact('analysis', 'companyName','companyAddress', 'mobileNo','postalCode'));    
    }

 
    public function dairyRebatePending(Request $request) 
    {

        if ($request->ajax()) {
            $rebateStatus = $request->input('rebate_status');

            if ($rebateStatus == '' || $rebateStatus === null) {
                // Single paginated DB query — repository skips status filter when none set
                $data = $this->dairyAnalysisService->allRebateStatuses($request);
                return response()->json($data);
            }

            $data = [];

            if ($rebateStatus == 1) {
                $data = $this->dairyAnalysisService->rebatePending($request);
            } elseif ($rebateStatus == 2) {
                $data = $this->dairyAnalysisService->paymentClearRebatePending($request);
            } elseif ($rebateStatus == 3) {
                $data = $this->dairyAnalysisService->salesBillMapRemaining($request);
            }

            return response()->json($data);
        }

        $masterData = $this->masterService->dairyAnalysisMasterData(company_id());  
        extract($this->extractMasterData($masterData));  
        
        return view('company.pages.dairy-analysis.rebate_pending', compact('customers', 'suppliers', 'destinations','items'));
    }

   private function extractMasterData(array $masterData): array
    {
        return [
            'customers'    => $masterData['customers'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
            'suppliers' => $masterData['suppliers'] ?? [],
        ];
    }

}
<?php

namespace App\Http\Controllers;

use App\Models\GodownAnalysis;
use App\Models\Grn;
use App\Services\GodownAnalysisService;
use App\Services\CompanyService;
use App\Services\MasterDataService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GodownAnalysisController extends Controller
{
    protected GodownAnalysisService $godownAnalysisService;
    protected MasterDataService $masterDataService;
    protected CompanyService $companyService;

    public function __construct(
        GodownAnalysisService $godownAnalysisService,
        MasterDataService $masterDataService,
        CompanyService $companyService
    ) {
        $this->godownAnalysisService = $godownAnalysisService;
        $this->masterDataService = $masterDataService;
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
                'grn_id',
                'size'
            );
            
            $data = $this->godownAnalysisService->getAnalysisList($filters, company_id(), financial_year_id());
            return response()->json($data);
        }
        $suppliers = $this->masterDataService->get('suppliers', company_id());
        
        $grns = Grn::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where('is_skip_serial_generation', false)
            ->orderBy('grn_serial', 'desc')
            ->get(['id', 'grn_serial']);

        return view("company.pages.godown-analysis.index", compact('suppliers', 'grns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $grnNumbers = Grn::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where('is_skip_serial_generation',false)
            ->orderBy('grn_serial','desc')
            ->pluck('grn_serial', 'id')
            ->toArray();
    
        return view("company.pages.godown-analysis.create",compact('grnNumbers'));
    }

    public function getGrnData(Request $request)
    {
        $grnSerial = $request->input('grn_serial');
        $data = $this->godownAnalysisService->getGrnData(
            $grnSerial, 
            company_id(), 
            financial_year_id()
        );
        
        if (empty($data) || isset($data['message'])) { 
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'No data found for the given GRN Number.'
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
            'grn_id'     => 'required|exists:grns,id',
            'items'      => 'required|array',
        ]);

        try {
            $this->godownAnalysisService->storeAnalysis($request->all(), company_id(), financial_year_id());

            return response()->json([
                'success' => true,
                'message' => 'Godown Analysis saved successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Godown Analysis Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the analysis.'
            ], 500);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'items'     => 'required|array',
        ]);
        try {
            $analysis = GodownAnalysis::findOrFail($id);
            $data = $request->all();

            $this->godownAnalysisService->updateAnalysis($analysis, $data);

            return response()->json([
                'success' => true,
                'message' => 'Godown Analysis updated successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Godown Analysis Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the analysis.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $analysis = GodownAnalysis::findOrFail($id);
            $analysis->deleted_by = current_user_id();
            $analysis->save();
            $analysis->delete();
            return response()->json([
                'success' => true,
                'message' => 'Godown Analysis deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Godown Analysis Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the analysis.'
            ], 500);
        }
    }

    public function printAnalysisReport(GodownAnalysis $godownAnalysis): JsonResponse
    {
        $analysis = $godownAnalysis->load(['details.element', 'grn.details.item', 'grn.details.destination', 'grn.details.condition', 'grn.account']);  
        
        $companyName = $this->companyService->current(company_id())->name ?? '';  
        $companyAddress = $this->companyService->current(company_id())->address_one ?? $this->companyService->current(company_id())->address_two ?? '';
        $mobileNo = $this->companyService->current(company_id())->mobile_number ?? '';
        $postalCode = $this->companyService->current(company_id())->postal_code ?? '';

        $html = view('company.pages.godown-analysis.pds-print', compact('analysis', 'companyName','companyAddress', 'mobileNo','postalCode'))->render();    

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
}

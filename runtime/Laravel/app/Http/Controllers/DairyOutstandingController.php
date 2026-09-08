<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\FinancialYear;
use App\Models\PartyMaster;
use App\Services\AccountBalanceService;
use Illuminate\Http\Request;

class DairyOutstandingController extends Controller
{

    protected AccountBalanceService $accountBalanceService;

    public function __construct(AccountBalanceService $accountBalanceService)
    {
        $this->accountBalanceService = $accountBalanceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $finalArray = $this->processData();
        return view('company-selector.pages.dairy-outstanding.index', compact('finalArray'));
    }

    private function processData(){
        $partyMaster = PartyMaster::with('details')->get();
        $finalArray = [];

        foreach ($partyMaster as $party) {

            $partyTotal = 0;

            $finalArray[$party->id] = [
                'party_name'    => $party->name,
                'total_balance' => 0,
                'companies'     => [],
            ];

            foreach ($party->details as $detail) {

                $activeFinancialYearId = FinancialYear::where('company_id', $detail->company_id)
                    ->where('is_current', 1)
                    ->value('id');

                $result = $this->accountBalanceService
                    ->getClosingBalance(
                        $detail->company_id,
                        $activeFinancialYearId,
                        [$detail->account_id]
                    );

                $balance = $result[$detail->account_id]['closing'] ?? 0;

                $partyTotal += $balance;

                if(round($balance, 2) == 0) continue;

                $finalArray[$party->id]['companies'][] = [
                    'company_id'   => $detail->company_id,
                    'company_name' => $detail->company->name ?? '',
                    'balance' => round($balance, 2),

                ];
            }

            $finalArray[$party->id]['total_balance'] = round($partyTotal, 2);
        }
        $finalArray = array_values($finalArray);
        return $finalArray;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function print(Request $request)
    {
        if(!$request->ajax()){
             return AjaxResponse::error(
                message: 'Invalid Request!',
                errors: [],
                code: 400
            );
        }

        $finalArray = $this->processData();

        $html =  view('company-selector.pages.dairy-outstanding.print', compact('finalArray'))->render();

        return AjaxResponse::success(message: 'Success!', data: [
            'success' => true,
            'html' => $html,
            'message' => 'Print preview generated successfully.',
        ], code: 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        
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

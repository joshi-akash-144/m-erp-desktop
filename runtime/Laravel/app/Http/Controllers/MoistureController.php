<?php

namespace App\Http\Controllers;

use App\Services\GodownModuleService;
use App\Services\MasterDataService;
use App\Models\Grn;
use App\Models\DeliveryChallan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controller;

class MoistureController extends Controller
{
    protected MasterDataService $masterService;
    protected GodownModuleService $godownService;

    public function __construct(MasterDataService $masterService, GodownModuleService $godownService)
    {
        $this->masterService = $masterService;
        $this->godownService = $godownService;
    }

    public function index(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();
        $filters = $request->all();

        if ($request->ajax()) {
            $response = $this->godownService->getGodowns($companyId, $financialYearId, $filters);
            return response()->json([
                'data' => $response['godownData'],
                'permissions' => $response['permissions'],
            ]);
        }

        $data = [
            'filters'      => $filters,
            'accounts'     => $this->masterService->getCreditors($companyId)->merge($this->masterService->getDebtors($companyId)),
            'items'        => $this->masterService->get('items', $companyId),
            'destinations' => $this->masterService->get('destinations', $companyId),
            'godownUnits'  => $this->masterService->get('godown_units', $companyId),
        ];

        return view('company.pages.moisture.index', $data);
    }
}


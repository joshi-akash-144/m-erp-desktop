<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBagChallanLabourRequest;
use App\Services\BagChallanLabourService;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BagChallanLabourController extends Controller
{
    protected BagChallanLabourService $bagChallanLabourService;
    protected MasterDataService $masterDataService;

    public function __construct(BagChallanLabourService $bagChallanLabourService, MasterDataService $masterDataService)
    {
        // Apply middleware for permissions
        $this->middleware('permission:bag_challan_labour.list')->only(['index']);
        $this->middleware('permission:bag_challan_labour.create')->only(['create', 'store']);
        $this->middleware('permission:bag_challan_labour.update')->only(['edit', 'update']);
        $this->middleware('permission:bag_challan_labour.delete')->only('destroy');
        $this->middleware('permission:bag_challan_labour.restore')->only('restore');
        $this->middleware('permission:bag_challan_labour.print')->only('print');

        $this->bagChallanLabourService = $bagChallanLabourService;
        $this->masterDataService = $masterDataService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only(['start_date', 'end_date']);
            $page    = (int) $request->get('page', 1);
            $size    = (int) $request->get('size', 50);

            $result = $this->bagChallanLabourService->getBagChallanLabourList($filters, $page, $size);

            return response()->json($result);
        }

        return view('company.pages.godown.bag-challan-labour.index');
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only(['start_date', 'end_date', 'item_id']);
            
            $data = $this->bagChallanLabourService->getPendingBagEntryDataForGrid($filters);
            
            return response()->json([
                'data' => $data['data'],
                'permissions' => $data['permissions'],

            ]);
        }

        $items = $this->masterDataService->get('items', company_id());
        $uuid = Str::uuid();

        return view('company.pages.godown.bag-challan-labour.create', [
            'items' => $items,
            'uuid'  => $uuid,
        ]);
    }

    public function store(StoreBagChallanLabourRequest $request)
    {
        try {
            $data = $request->validated();
            
            $this->bagChallanLabourService->store($data);

            return response()->json([
                'success' => true,
                'message' => 'Bag Challan Labour created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function print(Request $request)
    {
        $id = $request->bagsChallanLabour_id;
        $data = $this->bagChallanLabourService->getPrintData($id);        
        // dd(json_encode($data, JSON_PRETTY_PRINT));
        $html = view('company.pages.godown.bag-challan-labour.print', $data)->render();

        return response()->json([
            'success' => true,
            'data'    => ['html' => $html],
        ]);
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

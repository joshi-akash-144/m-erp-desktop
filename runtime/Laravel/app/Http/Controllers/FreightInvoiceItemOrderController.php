<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\FreightInvoiceItemOrder;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FreightInvoiceItemOrderController extends Controller
{
    protected MasterDataService $masterDataService;

    public function __construct(MasterDataService $masterDataService) {
        $this->middleware('permission:freight_invoice.reorder');
        $this->masterDataService = $masterDataService;
    }


    public function saveItemOrderConfig(Request $request)
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer', 'exists:items,id'],
        ]);

        $companyId = company_id();
        $itemIds = $request->input('item_ids');

        // Delete existing configuration for this company
        FreightInvoiceItemOrder::where('company_id', $companyId)->delete();

        // Insert new order
        $insertData = [];
        foreach ($itemIds as $index => $itemId) {
            $insertData[] = [
                'company_id' => $companyId,
                'item_id' => $itemId,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        FreightInvoiceItemOrder::insert($insertData);

        return AjaxResponse::success('Item order saved successfully.');
    }

    /**
     * Reset the item order config to default
     */
    public function resetItemOrderConfig(MasterDataService $masterService)
    {
        $companyId = company_id();
        FreightInvoiceItemOrder::where('company_id', $companyId)->delete();
        
        $items = collect($masterService->get('items', $companyId))
            ->sort(function($a, $b) {
                $nameA = is_array($a) ? $a['name'] : $a->name;
                $nameB = is_array($b) ? $b['name'] : $b->name;
                return strcasecmp($nameA, $nameB);
            })
            ->values();

        $insertData = [];
        foreach ($items as $index => $item) {
            $itemId = is_array($item) ? $item['id'] : $item->id;
            $insertData[] = [
                'company_id' => $companyId,
                'item_id' => $itemId,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        FreightInvoiceItemOrder::insert($insertData);

        return AjaxResponse::success('Item order reset to default successfully.');
    }

}

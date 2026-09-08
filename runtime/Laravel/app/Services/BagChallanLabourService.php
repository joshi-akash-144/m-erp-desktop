<?php

namespace App\Services;

use App\Models\BagChallanLabour;
use App\Models\Item;
use App\Repositories\BagChallanLabourRepository;
use App\Models\GodownModule;
use Carbon\Carbon;

class BagChallanLabourService
{
    protected $repository;
     protected CompanyService $companyService;

    public function __construct(BagChallanLabourRepository $repository, CompanyService $companyService)
    {
        $this->repository = $repository;
        $this->companyService = $companyService;
    }

    /**
     * Map pending GRNs to format expected by the frontend Tabulator grid
     */
    public function getPendingBagEntryDataForGrid($filters)
    {
        $grns = $this->repository->getPendingBagEntryGrns($filters);

        $mappedData = $grns->map(function ($grn) {
            $fromDestination = '-';
            $toDestination = '-';
            $fromDestinationId = null;
            $toDestinationId = null;

            $inOutStatus = $grn->godownModule->in_out_status ?? null;

            if ($inOutStatus === GodownModule::PRODUCT_IN) {
                // If "in": from = account city, to = godown
                $fromDestination = $grn->account->city ?? '-';
                $toDestination = $grn->godownModule->godown->name ?? '-';
                $toDestinationId = $grn->godownModule->godown_id ?? null;
            } elseif ($inOutStatus === GodownModule::PRODUCT_OUT) {
                // If "out": from = godown, to = party_destination
                $fromDestination = $grn->godownModule->godown->name ?? '-';
                $fromDestinationId = $grn->godownModule->godown_id ?? null;
                $toDestination = $grn->godownModule->partyDestination->name ?? '-';
                $toDestinationId = $grn->godownModule->party_destination_id ?? null;
            } else {
                // Fallback if no godownModule is attached
                $fromDestination = $grn->account->city ?? '-';
                $toDestination = $grn->details->pluck('destination.name')->filter()->unique()->implode(', ') ?: '-';
                $toDestinationId = $grn->details->pluck('destination_id')->filter()->first() ?? null;
            }

            return [
                'id' => $grn->id,
                'date' => $grn->grn_date ? Carbon::parse($grn->grn_date)->format('d-m-Y') : '-',
                'vehicle_number' => $grn->vehicle_number,
                'from_destination' => $fromDestination,
                'from_destination_id' => $fromDestinationId,
                'to_destination' => $toDestination,
                'to_destination_id' => $toDestinationId,
                'bag_count' => $grn->bag_count,
                'item' => [
                    'name' => $grn->details->pluck('item.name')->filter()->unique()->implode(', ')
                ]
            ];
        });



        $permissions = userPermissions([
            'bag_challan_labour.view',
            'bag_challan_labour.update',
            'bag_challan_labour.delete',
        ], true);

        return [
            'data' => $mappedData,
            'permissions' => $permissions
        ];
    }

    /**
     * Store Bag Challan Labour entry
     */
    public function store(array $data)
    {
        // Calculate bag_amount if not already done, though it's usually rate * bags
        $data['bag_amount'] = $data['rate'] * $data['bags'];
        
        return $this->repository->store($data);
    }

     public function getBagChallanLabourList(array $filters, int $page, int $size)
    {
        $result  = $this->repository->getList($filters, $page, $size);
        
        $mapped = collect($result['data'])->map(function ($record) {
            return [
                'id'             => $record->id,                                
                'bags'           => number_format((float) $record->bags, 2, '.', ''),
                'rate'           => number_format((float) $record->rate, 2, '.', ''),
                'bag_amount'     => number_format((float) $record->bag_amount, 2, '.', ''),
                'loading_amount' => number_format((float) $record->loading_amount, 2, '.', ''),
                'total_amount'   => number_format((float) $record->total_amount, 2, '.', ''),
            ];
        });
    
        return [
            'data'      => $mapped,
            'total'     => $result['total'],
            'last_page' => $result['last_page'],
        ];
    }

    public function getPrintData(string $id): array
    {
        $record = BagChallanLabour::with([
                'items:id,bag_challan_labour_id,grn_id,bags',
                'items.grn:id,grn_serial,vehicle_number,grn_date',
                'items.grn.details:id,grn_id,item_id',
                'items.grn.details.item:id,name',
            ])
            ->select(['id', 'rate', 'bag_amount', 'loading_amount', 'total_amount'])
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->findOrFail($id);
        
        $company = $this->companyService->current(company_id());                    
        return [
            'record'    => $record,           
            'company'   => $company->print_name,
            'gstNumber' => $company->gst_number,
        ];
    }
}

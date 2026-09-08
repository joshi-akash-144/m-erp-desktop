<?php

namespace App\Repositories;

use App\Models\Grn;
use App\Models\BagChallanLabour;
use App\Models\BagChallanLabourItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BagChallanLabourRepository
{
    /**
     * Get GRNs that need bag entry from Godown
     */
    public function getPendingBagEntryGrns($filters)
    {
        $query = Grn::with(['details.item', 'details.destination', 'account', 'godownModule.godown', 'godownModule.partyDestination'])
            ->where('entry_from', Grn::ENTRY_FROM_GODOWN)
            ->where('is_bag_entry', true);
                
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            try {
                $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->startOfDay();
                $end = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->endOfDay();
                $query->whereBetween('grn_date', [$start, $end]);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        if (!empty($filters['item_id'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('item_id', $filters['item_id']);
            });
        }

        return $query->latest('id')->get();
    }

    /**
     * Store the Bag Challan Labour and its items inside a transaction.
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $bagChallanLabour = BagChallanLabour::create([
                'uuid'           => $data['uuid'],
                'bags'           => $data['bags'],
                'rate'           => $data['rate'],
                'bag_amount'     => $data['bag_amount'],
                'loading_amount' => $data['loading_amount'] ?? 0,
                'total_amount'   => $data['total_amount'],
                'status'         => 1,
                'created_by'     => current_user_id(),
                'company_id'     => company_id(),
                'financial_year_id' => financial_year_id(),
            ]);

            $grnIds = [];
            foreach ($data['items'] as $item) {
                BagChallanLabourItem::create([
                    'bag_challan_labour_id' => $bagChallanLabour->id,
                    'grn_id'                => $item['grn_id'],
                    'bags'                  => $item['bags'],
                ]);
                $grnIds[] = $item['grn_id'];
            }

            if (!empty($grnIds)) {
                Grn::whereIn('id', $grnIds)->update(['is_bag_entry' => 1]);
            }

            return $bagChallanLabour;
        });
    }

    public function getList(array $filters, int $page = 1, int $size = 50)
    {
        $query = BagChallanLabour::with(['items.grn:id,grn_serial'])
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id());

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            try {
                $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->startOfDay();
                $end   = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $total = $query->count();
        $records = $query->latest('id')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        return [
            'data'      => $records,
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ];
    }
}

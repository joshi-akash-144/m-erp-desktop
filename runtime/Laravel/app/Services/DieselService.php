<?php

namespace App\Services;

use App\Models\Diesel;
use App\Models\DieselItem;
use App\Models\VoucherType;
use App\Models\JournalVoucher;
use App\Models\Reference;
use App\Enums\SourceType;
use Illuminate\Support\Facades\DB;

class DieselService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    public function store(array $data, int $companyId, int $financialYearId): Diesel
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {

            // Skip empty rows
            $rows = array_filter($data['items'] ?? [], function ($item) {
                return !empty($item['vehicle_id']) || !empty($item['driver_id']) || !empty($item['diesel']) || !empty($item['amount']) || !empty($item['challan_number']);
            });

            if (empty($rows)) {
                throw new \Exception('Please enter at least one valid row.');
            }

            $totalAmount = collect($rows)->sum(function ($row) {
                $rate = (float) ($row['rate'] ?? 0);
                $dieselQty = (float) ($row['diesel'] ?? 0);
                $amount = $rate * $dieselQty;
                return $amount > 0 ? round($amount, 2) : 0;
            });


            // $totalAmount = collect($rows)->sum('amount');
            $noOfVehicles = collect($rows)->where('vehicle_id', '!=', '')->count();


            $diesel = Diesel::create([
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'voucher_date'       => $data['voucher_date'],
                'account_id'         => $data['account_id'],

                // 'diesel_rate'        => $data['diesel_rate'] ?? 0,
                'diesel_rate'        => 0,
                'no_of_vehicles'     => $noOfVehicles,
                'total_amount'       => $totalAmount,
                'narration'          => $data['narration'] ?? null,
                'created_by'         => current_user_id(),
            ]);

            $itemsData = [];
            foreach ($rows as $row) {
                $rate = (float) ($row['rate'] ?? 0);
                $dieselQty = (float) ($row['diesel'] ?? 0);
                $amount = $rate * $dieselQty;
                $amount = $amount > 0 ? round($amount, 2) : 0;

                $itemsData[] = [
                    'diesel_id'      => $diesel->id,
                    'challan_number' => $row['challan_number'] ?? null,
                    'vehicle_id'     => $row['vehicle_id'] ?? null,
                    'driver_id'      => $row['driver_id'] ?? null,
                    'last_date'      => !empty($row['last_date']) ? format_date($row['last_date'], 'Y-m-d') : null,
                    'today_date'     => !empty($row['today_date']) ? format_date($row['today_date'], 'Y-m-d') : null,
                    'rate'           => $rate,
                    'diesel'         => $dieselQty,
                    'old_km'         => $row['old_km'] ?? 0,
                    'new_km'         => $row['new_km'] ?? 0,
                    'amount'         => $amount,
                    'diff'           => $row['diff'] ?? 0,
                    'average'        => $row['average'] ?? 0,
                    'remark'         => $row['remark'] ?? null,
                    'is_closed'      => false,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            DieselItem::insert($itemsData);


            return $diesel;
        });
    }

    public function update(Diesel $diesel, array $data): Diesel
    {
        return DB::transaction(function () use ($diesel, $data) {
            
            // Skip empty rows
            $rows = array_filter($data['items'] ?? [], function ($item) {
                return !empty($item['vehicle_id']) || !empty($item['driver_id']) || !empty($item['diesel']) || !empty($item['amount']) || !empty($item['challan_number']);
            });

            if (empty($rows)) {
                throw new \Exception('Please enter at least one valid row.');
            }

            $diesel->update([
                'voucher_date'       => $data['voucher_date'],
                'account_id'         => $data['account_id'],
                'narration'          => $data['narration'] ?? null,
                'updated_by'         => current_user_id(),
            ]);

            // Collect IDs of existing items sent from the frontend (null = new row)
            $submittedIds = collect($rows)
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

            // Delete existing items whose ID is NOT in the submitted list AND are not closed
            $diesel->details()
                ->whereNotIn('id', $submittedIds)
                   ->where('is_closed', 0)
                   ->delete();

            // Key existing items by ID using a FRESH query (avoid stale eager-load cache)
            $existingItems = $diesel->details()->get()->keyBy('id');

            foreach ($rows as $row) {
                $rate = (float) ($row['rate'] ?? 0);
                $dieselQty = (float) ($row['diesel'] ?? 0);
                $amount = $rate * $dieselQty;
                $amount = $amount > 0 ? round($amount, 2) : 0;

                $detailId = !empty($row['id']) ? (int) $row['id'] : null;
                $existing = $detailId ? $existingItems->get($detailId) : null;

                if ($existing) {
                    if ($existing->is_closed) {
                        // Skip updating closed items to preserve data integrity
                        continue;
                    }
                    // Update existing unclosed item
                    $existing->update([
                        'challan_number' => $row['challan_number'] ?? $existing->challan_number,
                        'vehicle_id'     => $row['vehicle_id'] ?? null,
                        'driver_id'      => $row['driver_id'] ?? null,
                        'last_date'      => !empty($row['last_date']) ? format_date($row['last_date'], 'Y-m-d') : null,
                        'today_date'     => !empty($row['today_date']) ? format_date($row['today_date'], 'Y-m-d') : null,
                        'rate'           => $rate,
                        'diesel'         => $dieselQty,
                        'old_km'         => $row['old_km'] ?? 0,
                        'new_km'         => $row['new_km'] ?? 0,
                        'amount'         => $amount,
                        'diff'           => $row['diff'] ?? 0,
                        'average'        => $row['average'] ?? 0,
                        'remark'         => $row['remark'] ?? null,
                    ]);
                } else {
                    // Create new item (no id = brand new row added by user)
                    $diesel->details()->create([
                        'challan_number' => $row['challan_number'] ?? null,
                        'vehicle_id'     => $row['vehicle_id'] ?? null,
                        'driver_id'      => $row['driver_id'] ?? null,
                        'last_date'      => !empty($row['last_date']) ? format_date($row['last_date'], 'Y-m-d') : null,
                        'today_date'     => !empty($row['today_date']) ? format_date($row['today_date'], 'Y-m-d') : null,
                        'rate'           => $rate,
                        'diesel'         => $dieselQty,
                        'old_km'         => $row['old_km'] ?? 0,
                        'new_km'         => $row['new_km'] ?? 0,
                        'amount'         => $amount,
                        'diff'           => $row['diff'] ?? 0,
                        'average'        => $row['average'] ?? 0,
                        'remark'         => $row['remark'] ?? null,
                        'is_closed'      => false,
                    ]);
                }
            }

            // Recalculate totals from DB to ensure accuracy
            $freshItems = $diesel->details()->get();
            $diesel->update([
                'total_amount'   => $freshItems->sum('amount'),
                'no_of_vehicles' => $freshItems->where('vehicle_id', '!=', null)->count(),
            ]);

            return $diesel;
        });
    }

    public function getRegisterList(array $filters): array
    {
        $query = Diesel::with([
            'account:id,name',
            'creator:id,name',
            'updater:id,name',
            'details.vehicle:id,name',
            'details.driver.account:id,name',
        ])
            ->where('diesels.company_id', $filters['company_id'])
            ->where('diesels.financial_year_id', $filters['financial_year_id']);

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['vehicle_id'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('vehicle_id', $filters['vehicle_id']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('voucher_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('voucher_date', '<=', $filters['to_date']);
        }


        $page     = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['size'] ?? 50);
        $total    = $query->count();

        $records = $query->orderByDesc('diesels.id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();



        $flatRows = [];
        $offset   = ($page - 1) * $pageSize;

        foreach ($records as $idx => $expense) {
            $items     = $expense->details;
            $firstItem = $items->first();
            $vSerial   = '-';
            if ($firstItem && $firstItem->closed_by_voucher_id) {
                $voucher = \App\Models\Voucher::find($firstItem->closed_by_voucher_id);
                if ($voucher) {
                    $vSerial = $voucher->voucher_serial;
                }
            }
            $isLocked  = $items->isNotEmpty() && $items->every(function($item) { return $item->is_closed == 1; });
            $clearCount = $items->where('is_closed', 1)->count();
            $pendingCount = $items->where('is_closed', 0)->count();

            $flatRows[] = [
                'row_type'             => 'parent',
                'id'                   => $expense->id,
                'row_num'              => $offset + $idx + 1,
                'voucher_serial'       => $vSerial,
                'voucher_date'         => $expense->voucher_date ? \Carbon\Carbon::parse($expense->voucher_date)->format('d/m/Y') : '-',
                'account_name'         => $expense->account?->name ?? '-',
                'created_by'           => $expense->creator?->name ?? '-',
                'updated_by'           => $expense->updater?->name ?? '-',
                'challan_number'       => $firstItem?->challan_number ?? '-',
                'bill_no'              => $firstItem?->reference_number ?? '-',
                'vehicle_name'         => $firstItem?->vehicle?->name ?? '-',
                'driver_name'          => $firstItem?->driver?->account?->name ?? '-',
                'remark'               => $firstItem?->remark ?? '-',
                'diesel'               => (float) ($firstItem?->diesel ?? 0),
                'amount'               => (float) ($firstItem?->amount ?? 0),
                'is_locked'            => $isLocked,
                'clear_count'          => $clearCount,
                'pending_count'        => $pendingCount,
                // 'rate'                 => (float) $expense->diesel_rate,
                'rate'                 => (float) ($firstItem?->rate ?? 0),
                'total_diesel'         => (float) $items->sum('diesel'),
                'total_vehicle'        => $expense->no_of_vehicles,
                'total_amount'         => (float) $expense->total_amount,
                
                // Keep the creator and updater objects for the JS custom formatter
                'creator'              => $expense->creator,
                'updater'              => $expense->updater,
            ];
        }

        $permissions = [
            'update' => true,
            'print'  => true,
            'view'   => true,
            'delete' => true,
        ];

        return [
            'data'        => $flatRows,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'last_page'   => (int) ceil($total / max($pageSize, 1)),
            'permissions' => $permissions,
        ];
    }
}

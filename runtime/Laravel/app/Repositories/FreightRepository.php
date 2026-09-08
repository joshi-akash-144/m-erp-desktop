<?php

namespace App\Repositories;

use App\Models\Freight;
use App\Models\GodownModule;
use App\Models\Grn;

class FreightRepository extends BaseRepository
{
    public function __construct(Freight $freight)
    {
        parent::__construct($freight);
    }


    public function getGrnBySerial(int $grnSerial, int $companyId, int $financialYearId): ?GodownModule
    {
        return GodownModule::query()
            ->where('company_id', $companyId)
            ->whereHas('grn', function ($query) use ($grnSerial) {
                $query->where('grn_serial', $grnSerial);
            })
            ->with([
                'grn.account:id,name,city', 
                'grn.broker:id,name',
                'grn.details.item:id,name',
                'grn.details.destination:id,name',
                'grn.details.purchaseOrder:id,destination_id',
                'grn.details.purchaseOrder.destination:id,name',
                'godown:id,name',
                'transporter:id,name'
            ])
            ->latest('id')
            ->first();
    }

    public function getByLrNumber(string $lrNumber, int $companyId, int $financialYearId): ?GodownModule
    {
        $lrNumber = trim($lrNumber);
        return GodownModule::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('lr_number', $lrNumber)
            ->with([
                'grn.account:id,name,city',
                'grn.broker:id,name',
                'grn.details.item:id,name',
                'grn.details.destination:id,name',
                'grn.details.purchaseOrder:id,destination_id',
                'grn.details.purchaseOrder.destination:id,name',
                'godown:id,name',
                'transporter:id,name'
            ])
            ->latest('id')
            ->first();
    }

    public function checkDuplicateLRNumber(string $lrNumber, int $companyId, int $financialYearId, $excludeId = null): bool
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('lr_number', $lrNumber)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists();
    }

    public function getNextBillSerial(int $billedToCompanyId, int $financialYearId): int
    {
        $bill = Freight::where("company_id", $billedToCompanyId)
            ->where("financial_year_id", $financialYearId)
            ->max("invoice_serial");
            
        return (int) $bill;
    }

    /**
     * Get the last invoice_serial for a specific prefix,
     * so each prefix has its own independent counter.
     */
    public function getNextBillSerialByPrefix(int $companyId, int $financialYearId, int $accountId): int
    {
   
        $last = Freight::where('account_id', $accountId)
    ->where('company_id', 9)
    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(reference_number, '-', -1) AS UNSIGNED)) as max_ref_no")
    ->value('max_ref_no') ?? 0;

        return (int) $last;
    }

    public function buildQuery(array $filters)
    {
        $query = $this->model->query()
            ->with([
                'account:id,name',
                'vehicle:id,name',
                'fromDestination:id,name',
                'toDestination:id,name',
                'items.item:id,name',
                'items.zone:id,name',
                'consignor:id,name',
                'consignee:id,name',
            ]);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (isset($filters['financial_year_id'])) {
            $query->where('financial_year_id', $filters['financial_year_id']);
        }
        if (isset($filters['entry_from'])) {
            $query->where('entry_from', $filters['entry_from']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('invoice_date', '>=', date('Y-m-d', strtotime($filters['start_date'])));
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('invoice_date', '<=', date('Y-m-d', strtotime($filters['end_date'])));
        }
        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }
        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }
        if (!empty($filters['grn_id'])) {
            $query->where('grn_serial', $filters['grn_id']);
        }
        if (!empty($filters['lr_number_id'])) {
            $query->where('lr_number', $filters['lr_number_id']);
        }
        if (!empty($filters['consignor_id'])) {
            $query->where('consignor_id', $filters['consignor_id']);
        }
        if (!empty($filters['consignee_id'])) {
            $query->where('consignee_id', $filters['consignee_id']);
        }
        if (!empty($filters['from_destination_id'])) {
            $query->where('from_destination_id', $filters['from_destination_id']);
        }
        if (!empty($filters['to_destination_id'])) {
            $query->where('to_destination_id', $filters['to_destination_id']);
        }
        if (!empty($filters['bill_id'])) {
            $query->where('id', $filters['bill_id']);
        }
        if (!empty($filters['invoice_serial'])) {
            $query->where('invoice_serial', "Like", "%" . $filters['invoice_serial'] . "%");
        }
        if (!empty($filters['item_id'])) {
            $itemIds = is_array($filters['item_id']) ? $filters['item_id'] : explode(',', $filters['item_id']);
            $query->whereHas('items', function ($q) use ($itemIds) {
                $q->whereIn('item_id', $itemIds);
            });
        }

        $query->orderBy('id', 'desc');

        return $query;
    }

    public function list(array $filters)
    {
        $query = $this->buildQuery($filters);
        $size = $filters['size'] ?? 50;
        return $query->paginate($size);
    }
    public function countAll(int $companyId, int $financialYearId, string $entryFrom = null): int
    {
        $query = $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);
            
        if ($entryFrom) {
            $query->where('entry_from', $entryFrom);
        }
        
        return $query->count();
    }
}

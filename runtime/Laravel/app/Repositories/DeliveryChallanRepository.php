<?php

namespace App\Repositories;

use App\Models\DeliveryChallan;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DeliveryChallanRepository extends BaseRepository
{
    public function __construct(DeliveryChallan $deliveryChallan)
    {
        parent::__construct($deliveryChallan);
    }

    public function getNextVoucherSerial(int $companyId, int $financialYearId): int
    {
        $lastChallan = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('challan_serial')
            ->first();

        return $lastChallan ? $lastChallan->challan_serial : 0;
    }

    public function existsByReferenceNumberAndAccount(int $companyId, int $financialYearId, string $referenceNumber, int $accountId, ?int $id = null): bool
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('reference_number', $referenceNumber)
            ->where('account_id', $accountId)
            ->when($id, function ($query) use ($id) {
                return $query->where('id', '!=', $id);
            })
            ->exists();
    }

    public function findByReferenceNumberAndAccount(array $filters): ?DeliveryChallan
    {
        return $this->model
            ->where($filters)
            ->first();
    }

    public function getChallanSerial(int $companyId, int $financialYearId): Collection
    {
        return $this->model->query()
            ->where(['company_id' => $companyId, 'financial_year_id' => $financialYearId])
            ->orderBy('id', 'desc')
            ->get(['challan_serial', 'challan_number', 'id']);
    }

    public function getEditData(int $challanId, ?int $companyId, ?int $financialYearId): ?DeliveryChallan
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $challanId)
            ->with([
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'details:id,delivery_challan_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,sales_order_id,sales_order_item_id,sales_order_serial',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'challan_serial', 'challan_date', 'challan_in_date', 'challan_out_date', 'contract_number', 'reference_number', 'account_id', 'broker_id', 'total_quantity', 'sub_total as total_amount', 'challan_status', 'gst_type', 'vehicle_number', 'remarks', 'gross_weight', 'tare_weight', 'net_weight', 'bag_type', 'bag_count', 'net_weight_wt_bag'])
            ->first();
    }

    public function getViewData(int $challanId, int $companyId, int $financialYearId): ?DeliveryChallan
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $challanId)
            ->with([
                'account:id,city,name',
                'broker:id,city,name',
                'details:id,delivery_challan_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,sales_order_id,sales_order_item_id,sales_order_serial',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'challan_serial', 'challan_date', 'challan_in_date', 'challan_out_date', 'contract_number', 'reference_number', 'account_id', 'broker_id', 'total_quantity', 'sub_total as total_amount', 'challan_status', 'gst_type', 'vehicle_number', 'remarks', 'gross_weight', 'tare_weight', 'net_weight', 'bag_type', 'bag_count', 'net_weight_wt_bag'])
            ->first();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = [])
    {
        $mainSelect = ['id', 'challan_number', 'challan_serial', 'account_id', 'broker_id', 'contract_number', 'challan_date', 'challan_in_date', 'challan_out_date', 'reference_number', 'total_quantity', 'sub_total as total_amount', 'gst_type', 'vehicle_number', 'remarks', 'qc_status', 'status', 'updated_by', 'created_by', 'challan_status'];
        $detailsSelect = ['delivery_challan_id', 'sales_order_id', 'sales_order_serial', 'item_id', 'destination_id', 'condition_id', 'quantity', 'party_quantity', 'rate', 'inclusive_rate'];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);
        return $query->simplePaginate(
            $filters['size'] ?? 10,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    public function listAll(int $companyId, int $financialYearId, ?array $filters = [])
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->get();
    }

    public function getFilteredQuery(
        int $companyId,
        int $financialYearId,
        ?array $filters = null,
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ) {
        $filters = is_array($filters) ? $filters : [];

        return $this->model->query()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'account:id,name,city',
                'broker:id,name,city',
                'details' => function ($q) use ($detailsSelect) {
                    $q->select($detailsSelect);
                },
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->where([
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->when(!empty($filters['ids']), fn($q) => $q->whereIn('id', $filters['ids']))
            ->when(!empty($filters['dc_no']), fn($q) => $q->where('id', $filters['dc_no']))
            ->when(!empty($filters['dc_number']), fn($q) => $q->where('challan_number', $filters['dc_number']))
            ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
            ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
            ->when(($filters['delivery_status'] ?? 'all') !== 'all', fn($q) => $q->where('challan_status', $filters['delivery_status']))
            ->when(!empty($filters['qc_status']), fn($q) => $q->where('qc_status', $filters['qc_status']))
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::createFromFormat('d-m-Y', $filters['start_date'])->format('Y-m-d');
                $end   = Carbon::createFromFormat('d-m-Y', $filters['end_date'])->format('Y-m-d');
                $q->whereBetween('challan_date', [$start, $end]);
            })
            ->when(!empty($filters['item_id']), fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
            )
            ->when(!empty($filters['condition_id']), fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id']))
            )
            ->when(!empty($filters['destination_id']), fn($q) =>
                $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id']))
            )
            ->select($mainSelect);
    }

    function getStatusOpenChallanNumbers(int $companyId): Collection
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('challan_status', DeliveryChallan::STATUS_OPEN)
            ->get(['id', 'challan_number']);
    }

    function getChallanSerialAndNumber(?int $challanId = null): array
    {
        $data = $this->model->query()->where('id', $challanId)->select('challan_serial', 'challan_number')->first();

        return $data ? $data->toArray() : [
            "challan_serial" => null,
            "challan_number" => null,
        ];
    }
}
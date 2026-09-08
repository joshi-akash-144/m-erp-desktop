<?php

namespace App\Repositories;

use App\Models\Grn;
use App\Models\Penalty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GrnRepository extends BaseRepository
{
    public function __construct(Grn $grn)
    {
        parent::__construct($grn);
    }


    public function getNextVoucherSerial(int $companyId, int $financialYearId): int
    {
        $lastGrn = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('is_skip_serial_generation', false)
            ->orderByDesc('grn_serial')
            ->first();

        return $lastGrn ? $lastGrn->grn_serial : 0;
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

    public function findByReferenceNumberAndAccount(array $filters): ?Grn
    {
        return $this->model
            ->where($filters)
            ->first();
    }

    public function getGrnSerial(int $companyId, int $financialYearId, ?string $entryFrom = null, ?array $selectedIds): Collection
    {
        return $this->model->query()
            ->where(['company_id' =>  $companyId, 'financial_year_id' => $financialYearId, 'is_skip_serial_generation' => false])
            ->when($entryFrom, fn($q) => $q->where('entry_from', $entryFrom))
            ->when($selectedIds, fn($q) => $q->whereIn('account_id', $selectedIds))
            ->orderBy('id', 'desc')->get(['grn_serial', 'grn_number', 'id']);
    }

    public function getEditData(int $grnId, ?int $companyId, ?int $financialYearId): ?Grn
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $grnId)
            ->with([
                'account:id,city,gst_type,name',
                'broker:id,city,name',
                'details:id,grn_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,purchase_order_id,purchase_order_item_id,purchase_order_serial',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'grn_serial', 'grn_date','grn_in_date','grn_out_date','contract_number', 'reference_number', 'account_id', 'broker_id',  'total_quantity', 'sub_total as total_amount', 'grn_status', 'gst_type','vehicle_number','remarks',
            'gross_weight','tare_weight','net_weight','bag_type','bag_count','net_weight_wt_bag','party_bill_date','url_path', 'entry_from'])
            ->first();
    }

    public function getViewData(int $grnId, int $companyId, int $financialYearId): ?Grn{
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('id', $grnId)
            ->with([
                'account:id,city,name',
                'broker:id,city,name',
                'details:id,grn_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,purchase_order_id,purchase_order_item_id,purchase_order_serial',
                'details.item:id,name,tax_category_id,unit_id,hsn_sac_code',
                'details.item.unit:id,name',
                'details.condition:id,name',
                'details.destination:id,name',
                'details.item.taxCategory:id,name,cgst,sgst,igst'
            ])
            ->select(['id', 'grn_serial', 'grn_date','grn_in_date','grn_out_date','contract_number', 'reference_number', 'account_id', 'broker_id',  'total_quantity', 'sub_total as total_amount', 'grn_status', 'gst_type','vehicle_number','remarks',
            'gross_weight','tare_weight','net_weight','bag_type','bag_count','net_weight_wt_bag','party_bill_date','url_path', 'entry_from'])
            ->first();
    }

    public function list(int $companyId, int $financialYearId, ?array $filters = []){
        $mainSelect = ['id', 'grn_number','grn_serial', 'account_id', 'broker_id', 'contract_number', 'grn_date','grn_in_date', 'grn_out_date','reference_number','total_quantity','sub_total as total_amount','gst_type','vehicle_number','remarks','qc_status', 'status', 'updated_by', 'created_by', 'grn_status'];
        $detailsSelect = ['grn_id','purchase_order_id','purchase_order_serial', 'item_id', 'destination_id','condition_id','quantity','party_quantity', 'rate', 'inclusive_rate'];

        $query = $this->getFilteredQuery($companyId, $financialYearId, $filters, $mainSelect, $detailsSelect);
        return $query->simplePaginate(
            $filters['size'],
            ['*'],
            'page',
            $filters['page']
        );
    }

    public function listAll(int $companyId, int $financialYearId, ?array $filters = []){
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->get();
    }

    public function countFiltered(int $companyId, int $financialYearId, array $filters): int
    {
        return $this->getFilteredQuery($companyId, $financialYearId, $filters)->count();
    }

    public function countAll(int $companyId, int $financialYearId): int
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function getFilteredQuery(
        int $companyId,
        int $financialYearId,
        ?array $filters = null,
        array $mainSelect = ['*'],
        array $detailsSelect = ['*']
    ) {
    // Normalize filters (always an array)
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
            'financial_year_id' => $financialYearId,
            'party_type' => 'supplier'
        ])

        // Safe filtering using null coalescing
        ->when(!empty($filters['grn_no']), fn($q) => $q->where('id', $filters['grn_no']))
        ->when(!empty($filters['grn_serial']), fn($q) => $q->where('grn_serial', $filters['grn_serial']))
        ->when(!empty($filters['account_id']), fn($q) => $q->where('account_id', $filters['account_id']))
        ->when(!empty($filters['broker_id']), fn($q) => $q->where('broker_id', $filters['broker_id']))
        // ->when(!empty($filters['grn_status']), fn($q) => $q->where('grn_status', $filters['grn_status']))
        ->when(($filters['grn_status'] ?? 'all') !== 'all', fn($q) => $q->where('grn_status', $filters['grn_status']))
        ->when(!empty($filters['qc_status']), fn($q) => $q->where('qc_status', $filters['qc_status']))
        ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate   = Carbon::parse($filters['end_date'])->endOfDay();
            $query->whereBetween('grn_date', [$startDate, $endDate]);
        })

        ->when(!empty($filters['reference_number']), fn($q) => 
            $q->where('reference_number', 'like', '%' . $filters['reference_number'] . '%')
        )

        ->when(!empty($filters['item_id']), fn($q) =>
            $q->whereHas('details', fn($d) => $d->where('item_id', $filters['item_id']))
        )

        ->when(!empty($filters['condition_id']), fn($q) =>
            $q->whereHas('details', fn($d) => $d->where('condition_id', $filters['condition_id']))
        )

        ->when(!empty($filters['destination_id']), fn($q) =>
            $q->whereHas('details', fn($d) => $d->where('destination_id', $filters['destination_id']))
        )
        ->orderBy('grn_serial', 'desc')
        ->select($mainSelect);
    }

    function getStatusOpenGrnNumbers(int $companyId): Collection{
        return $this->model->query()->where('company_id', $companyId)->where('grn_status', GRN::STATUS_OPEN)->orderBy('grn_serial', 'desc')->get(['id', 'grn_serial']);
    }

    function getGrnSerialAndNumber(?int $grnId = null): array{
        
        $data = $this->model->query()->where('id', $grnId)->select('grn_serial', 'grn_number')->first();

        return $data ? $data->toArray() : [
            "grn_serial" => null,
            "grn_number" => null,
        ];
    }

    function getGrnDetails(int $grnId): ?Grn{
        $grn = $this->model->query()
        ->where('id', $grnId)
        ->with([
            'account:id,city,gst_type,name',
            'broker:id,city,name',
            'details:id,grn_id,item_id,condition_id,rate,inclusive_rate,amount,cgst_rate,sgst_rate,igst_rate,taxable_amount,tax_amount,quantity,party_quantity,destination_id,bag_count,purchase_order_id,purchase_order_item_id,purchase_order_serial',
            'details.purchaseOrder:id,due_date',
            'details.item:id,name,tax_category_id,unit_id,hsn_sac_code,purchase_type_local_id,purchase_type_interstate_id',
            'details.item.purchaseTypeLocal:id,name,region,cgst,sgst,igst',
            'details.item.purchaseTypeInterstate:id,name,region,cgst,sgst,igst',
            'details.item.unit:id,name',
            'details.condition:id,name',
            'details.destination:id,name',
            'details.item.taxCategory:id,name,cgst,sgst,igst'
        ])
        ->select(['id', 'grn_serial', 'grn_date','grn_in_date','grn_out_date','contract_number', 'reference_number', 'account_id', 'broker_id',  'total_quantity', 'sub_total as total_amount', 'grn_status', 'gst_type','vehicle_number','remarks',
        'gross_weight','tare_weight','net_weight','bag_type','bag_count','net_weight_wt_bag','party_bill_date','url_path','penalty','entry_from','company_id'])
        ->first();

        if ($grn) {
            $totalPenalty = 0;
            $debug = [];
            foreach ($grn->details as $detail) {
                $d = [
                    'item_id' => $detail->item_id,
                    'po_id' => $detail->purchase_order_id,
                    'has_po_relation' => $detail->purchaseOrder ? true : false,
                    'po_due_date' => $detail->purchaseOrder->due_date ?? null,
                ];
                
                if ($detail->purchase_order_id && $detail->purchaseOrder && $detail->purchaseOrder->due_date) {
                    $dueDate = \Carbon\Carbon::parse($detail->purchaseOrder->due_date)->startOfDay();
                    $grnInDate = \Carbon\Carbon::parse($grn->grn_in_date)->startOfDay();
                    
                    if ($grnInDate->gt($dueDate)) {
                        $penaltyRecord = \App\Models\Penalty::where('company_id', $grn->company_id)
                            ->where('item_id', $detail->item_id)
                            ->first();
                            
                        $d['penalty_record_found'] = $penaltyRecord ? true : false;
                        $d['penalty_amount'] = $penaltyRecord ? $penaltyRecord->amount : 0;
                        $d['overdue'] = true;
                            
                        if ($penaltyRecord) {
                            $totalPenalty += ((float)$penaltyRecord->amount * (float)$detail->quantity); 
                        }
                    } else {
                        $d['overdue'] = false;
                    }
                }
                $debug[] = $d;
            }
            if ($totalPenalty > 0) {
                $grn->penalty = round($totalPenalty);
            }
            $grn->debug_info = $debug;
        }
        
        return $grn;
    }
}

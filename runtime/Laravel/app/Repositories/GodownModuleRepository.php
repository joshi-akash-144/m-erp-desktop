<?php

namespace App\Repositories;

use App\Models\GodownModule;
use App\Models\Grn;
use App\Models\DeliveryChallan;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GodownModuleRepository extends BaseRepository
{
    public function __construct(GodownModule $godownModule)
    {
        parent::__construct($godownModule);
    }

    public function getNextVoucherSerial(int $companyId, int $financialYearId): int
    {
        $lastGrn = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('grn_serial')
            ->first();

        return $lastGrn ? $lastGrn->grn_serial : 0;
    }


    public function getVehiclesForGodown(int $companyId, int $financialYearId, string $type = 'in'): Collection
    {
        $isOut = ($type === 'out');

        // Fetch Open GodownModule records (Manual or GRN-linked but still in progress)
        // We rely exclusively on the GodownModule table as requested.
        $query = $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('is_cycle', 'open')
            ->where('in_out_status', $type);

        // Filter based on weight flow:
        // Inbound: Loaded first (Gross > 0, Tare = 0)
        // Outbound: Empty first (Tare > 0, Gross = 0)
        if ($isOut) {
            
            $query->whereHas('grn', function ($q) {
                $q->where('tare_weight', '>', 0)->where('gross_weight', '<=', 0);
            });
            $query->with([
                'grn.account:id,name',
                'grn.details.item:id,name',
                'grn.details.destination:id,name'
            ]);
        } else {
            $query->whereHas('grn', function ($q) {
                $q->where('gross_weight', '>', 0)->where('tare_weight', '<=', 0);
            });
            $query->with([
                'grn.account:id,name',
                'grn.details.item:id,name',
                'grn.details.destination:id,name'
            ]);
        }

        $vehicles = $query->orderBy('id', 'desc')->get();

        // Normalize for the view logic
        foreach ($vehicles as $v) {
            $v->is_godown_only = true;
            
            $parent = $v->grn ?? $v->deliveryChallan;

            if ($parent) {
                // Copy all missing attributes from parent to support the modal view
                foreach ($parent->getAttributes() as $key => $value) {
                    if (!in_array($key, ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'company_id', 'financial_year_id'])) {
                        // Only set if not already present on $v, or if it's currently null
                        if ($v->getAttribute($key) === null) {
                            $v->setAttribute($key, $value);
                        }
                    }
                }
                
                // For delivery challan, we need to map challan properties to grn properties expected by the view
                if ($parent->challan_serial) {
                    $v->setAttribute('grn_serial', $parent->challan_serial);
                    $v->setAttribute('grn_date', $parent->challan_date);
                    $v->setAttribute('reference_number', $parent->reference_number);
                }

                // Copy account info
                $v->account_id = $parent->account_id;
                $v->setRelation('account', $parent->account);

                $details = $parent->details ?? collect();
                
                // Copy details for view
                $v->details = $details;
                
                $firstDetail = $details->first();
                if ($firstDetail) {
                    $v->setAttribute('item_id', $firstDetail->item_id);
                    $v->setRelation('item', $firstDetail->item ?? null);
                    
                    $v->setAttribute('destination_id', $firstDetail->destination_id);
                    $v->setRelation('destination', $firstDetail->destination ?? null);
                } else {
                    $v->setAttribute('item_id', null);
                    $v->setAttribute('destination_id', null);
                }
            } else {
                $v->details = collect([]);
            }
        }

        return $vehicles;
    }

    public function getGrnByNumber(string $grnNumber, int $companyId, int $financialYearId): ?Grn
    {
        return Grn::query()
            ->where([
                'grn_number' => $grnNumber,
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->with([
                'account:id,name,city',
                'broker:id,name',
                'details.item:id,name',
                'details.destination:id,name',
                'details.purchaseOrder:id,destination_id',
                'details.purchaseOrder.destination:id,name'
            ])
            ->first();
    }

    public function getGrnBySerial(int $grnSerial, int $companyId, int $financialYearId): ?Grn
    {
        return Grn::query()
            ->where([
                'grn_serial'        => $grnSerial,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->with([
                'account:id,name,city',
                'broker:id,name',
                'details.item:id,name',
                'details.destination:id,name',
                'details.purchaseOrder:id,destination_id',
                'details.purchaseOrder.destination:id,name',
                'moisture'
            ])
            ->first();
    }
    
    public function getDeliveryChallanBySerial(int $challanSerial, int $companyId, int $financialYearId): ?DeliveryChallan
    {
        return DeliveryChallan::query()
            ->where([
                'challan_serial'    => $challanSerial,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->with([
                'account:id,name,city',
                'broker:id,name',
                'details.item:id,name',
                'details.destination:id,name',
                'details.salesOrder:id'
            ])
            ->first();
    }

    public function getGodownData(int $companyId, int $financialYearId, array $filters = []): ?Collection
    {
        // dd($filters);
        $godownData = $this->model->query()
            ->where([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId
            ])
            ->when(!empty($filters['selected_ids']), function ($q) use ($filters) {
                return $q->whereIn('id', $filters['selected_ids']);
            })

            ->when(!empty($filters['start_date']) , function ($query) use ($filters) {
                $startDate = !empty($filters['start_date']) ? format_date($filters['start_date'], 'Y-m-d') : date('Y-m-d');
                $endDate = !empty($filters['end_date']) ? format_date($filters['end_date'], 'Y-m-d') : date('Y-m-d');

                $productStatus = isset($filters['product_status']) ? $filters['product_status'] : '';

                if ($productStatus == 'product_in' || $productStatus == 'product_out' || $productStatus == 'product_in_out') {
                    $query->whereBetween('out_date', [$startDate, $endDate]);
                } else {
                    $query->whereBetween('in_date', [$startDate, $endDate]);
                }
                })
            ->when(!empty($filters['account_id']), function ($q) use ($filters) {
                return $q->where(function ($sub) use ($filters) {
                    $sub->whereHas('grn', function ($grn) use ($filters) {
                        $grn->where('account_id', $filters['account_id']);
                    })->orWhereHas('deliveryChallan', function ($dc) use ($filters) {
                        $dc->where('account_id', $filters['account_id']);
                    });
                });
            })
            ->when(!empty($filters['item_id']), function ($q) use ($filters) {
                return $q->where(function ($sub) use ($filters) {
                    $sub->whereHas('grn.details', function ($grnDetails) use ($filters) {
                        $grnDetails->where('item_id', $filters['item_id']);
                    })->orWhereHas('deliveryChallan.details', function ($dcDetails) use ($filters) {
                        $dcDetails->where('item_id', $filters['item_id']);
                    });
                });
            })
            ->when(!empty($filters['godown_id']), function ($q) use ($filters) {
                return $q->where(function ($sub) use ($filters) {
                    $sub->where('godown_id', $filters['godown_id'])
                        ->orWhere('party_destination_id', $filters['godown_id']);
                });
            })
            ->when(!empty($filters['godown_unit_id']), function ($q) use ($filters) {
                return $q->where('godown_unit_id', $filters['godown_unit_id']);
            })
            ->when(!empty($filters['grn_number']), function ($q) use ($filters) {
                return $q->where('grn_id', $filters['grn_number'])
                        ->orWhereHas('grn', function ($grn) use ($filters) {
                            $grn->where('grn_serial', $filters['grn_number']);
                        });
            })
            ->when(!empty($filters['challan_number']), function ($q) use ($filters) {
                return $q->where('delivery_challan_id', $filters['challan_number']);
            })
            ->when(!empty($filters['product_status']), function ($q) use ($filters) {
                return $this->productStatus($q, $filters['product_status']);
            })
            ->when(!empty($filters['is_moisture']), function ($q) {
                return $q->has('moisture');
            })
            ->when(!empty($filters['lr_number']), function ($q) use ($filters) {
                return $q->where('lr_number', $filters['lr_number']);
            })
            ->with([
                'godown:id,name',
                'partyDestination:id,name',
                'godownUnit:id,godown_name',
                'grn.account:id,name,city',
                'grn.details.item:id,name',
                'deliveryChallan.account:id,name,city',
                'deliveryChallan.details.item:id,name',
                'moisture.creator:id,name',
                'creator:id,name',
                'updater:id,name',
            ])
            ->orderBy('id', 'asc')
            ->get();

        $godownData->transform(function ($module) {
            $parent = $module->grn ?? $module->deliveryChallan;
            
            $module->vehicle_number = $parent->vehicle_number ?? null;
            $module->gross_weight = $parent->gross_weight ?? null;
            $module->tare_weight = $parent->tare_weight ?? null;
            $module->net_weight = $parent->net_weight ?? null;
            $module->net_weight_wt_bag = $parent->net_weight_wt_bag ?? null;
            
            $module->dc_serial = $parent->challan_serial ?? null;
            $module->dc_date = $parent->challan_date ?? null;
            $module->grn_serial = $parent->grn_serial ?? null;
            $module->grn_date = $parent->grn_date ?? null;
            $module->reference_number = $parent->reference_number ?? null;
            
            if ($parent) {
                $module->setRelation('account', $parent->account);
                $firstDetail = $parent->details ? $parent->details->first() : null;
                if ($firstDetail) {
                    $module->setRelation('item', $firstDetail->item);
                    $module->p_qty = $module->challan_weight ?? 0;
                    $module->rate = $firstDetail->rate ?? 0;
                }
                
                $module->bag_count = $parent->bag_count ?? 0;
                $module->challan_serial = $parent->challan_serial ?? $parent->grn_serial ?? null;
                $module->grn_status = $parent->grn_status;
                $module->challan_status = $parent->challan_status ?? $parent->grn_status ?? null;
            }

            // Map Date & Time fields to match JS expectations and format them to prevent timezone shifting
            $module->date_in = $module->in_date ? Carbon::parse($module->in_date)->format('d-m-Y') : '-';
            $module->date_out = $module->out_date ? Carbon::parse($module->out_date)->format('d-m-Y') : '-';
            $module->time_in = $module->in_time;
            $module->time_out = $module->out_time;

            // Map relations to match JS expectations
            $module->setRelation('destination', $module->godown);
            $module->setRelation('godowns', $module->godownUnit);

            // dd($module);
            return $module;
        });

        return $godownData;
    }

        private function productStatus($q, string $status)
        {
            switch ($status) {
                case 'all':
                    return $q->whereIn('in_out_status', ['in', 'out'])
                            ->whereIn('is_cycle', ['open', 'close']);

                case 'product_in':
                    // Product In (logic: in, closed)
                    return $q->where('in_out_status', 'in')
                            ->where('is_cycle', 'close');

                case 'product_out':
                    // Product Out (logic: out, closed)
                    return $q->where('in_out_status', 'out')
                            ->where('is_cycle', 'close');

                case 'product_in_out':
                    // Product In-Out (logic: in/out, closed)
                    return $q->whereIn('in_out_status', ['in', 'out'])
                            ->where('is_cycle', 'close');

                case 'pending_in':
                    // Pending In (logic: in, open)
                    return $q->where('in_out_status', 'in')
                            ->where('is_cycle', 'open');

                case 'pending_out':
                    // Pending Out (logic: out, open)
                    return $q->where('in_out_status', 'out')
                            ->where('is_cycle', 'open');

                case 'pending_in_out':
                    // Pending In-Out (logic: in/out, open)
                    return $q->whereIn('in_out_status', ['in', 'out'])
                            ->where('is_cycle', 'open');

                default:
                    return $q;
            }
        }
   private function getBaseTransporterQuery(int $companyId, int $financialYearId, array $filters = [])
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('in_out_status', 'out')
            ->whereNotNull('transporter_id')
            ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                return $q->whereDate('in_date', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                return $q->whereDate('in_date', '<=', $filters['end_date']);
            })
            ->when(!empty($filters['transporter_id']), function ($q) use ($filters) {
                return $q->where('transporter_id', $filters['transporter_id']);
            })
            ->when(!empty($filters['lr_number']), function ($q) use ($filters) {
                return $q->where('lr_number', $filters['lr_number']);
            })
            ->with(['transporter:id,name', 'grn:id,grn_serial,vehicle_number,gross_weight,tare_weight,net_weight,net_weight_wt_bag']);
    }

    private function mapTransporterData($modules)
    {
        return $modules->map(function ($module) {
            $parent = $module->grn;

            $module->vehicle_number    = $parent->vehicle_number ?? '-';
            $module->gross_weight      = $parent->gross_weight ?? 0;
            $module->tare_weight       = $parent->tare_weight ?? 0;
            $module->net_weight        = $parent->net_weight ?? 0;
            $module->net_weight_wt_bag = $parent->net_weight_wt_bag ?? 0;
            $module->grn_serial        = $parent->grn_serial ?? '-';

            return $module;
        });
    }

    public function getTransporterListData(int $companyId, int $financialYearId, array $filters = []): array
    {
        $page = max((int) ($filters['page'] ?? 1), 1);
        $size = max((int) ($filters['size'] ?? 50), 1);

        $query = $this->getBaseTransporterQuery($companyId, $financialYearId, $filters);

        $total    = $query->count();
        $lastPage = (int) ceil($total / $size);
        $start    = ($page - 1) * $size;

        $modules = $query->skip($start)->take($size)->get();

        return [
            'data'      => $this->mapTransporterData($modules),
            'total'     => $total,
            'last_page' => $lastPage,
        ];
    }

    public function getTransporterExportData(int $companyId, int $financialYearId, array $filters = []): Collection
    {
        $modules = $this->getBaseTransporterQuery($companyId, $financialYearId, $filters)->get();
        
        return $this->mapTransporterData($modules);
    }

    public function countAllTransporters(int $companyId, int $financialYearId): int
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereNotNull('transporter_id')
            ->count();
    }

    /**
     * Delete a record with audit logging
     */
    public function delete(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            $model->update(['deleted_by' => auth()->id()]);
            return $model->delete();
        });
    }

}

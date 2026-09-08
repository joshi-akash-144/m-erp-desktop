<?php

namespace App\Repositories;

use App\Models\DairyParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\DairyAnalysis;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use App\Models\Reference;

class DairyAnalysisRepository extends BaseRepository
{
    public function __construct(DairyAnalysis $dairyAnalysis)
    {
        parent::__construct($dairyAnalysis);

        // $this->salesInvoice = new SalesInvoice();
        // $this->purchaseInvoice = new PurchaseInvoice();
        // $this->dairyParameter = new DairyParameter();
        // $this->dairyAnalysis = new DairyAnalysis();   
     }

    /**
     * Get Sales, Purchase and Parameter data based on Invoice Serial
     * 
     * @param int $invoiceSerial
     * @param int $companyId
     * @param int $financialYearId
     * @return array
     */
    public function getInvoiceDataByBillSerial($invoiceSerial, int $companyId, int $financialYearId): array
    {        
        // Remove dot to find the original Sales Invoice
        $salesSerial = str_replace('.', '', (string)$invoiceSerial);

        // 1. Fetch Sales Invoice
        $salesInvoice = SalesInvoice::with(['account:id,name,city', 'details:id,sales_invoice_id,condition_id,rate,inclusive_rate,quantity', 'details.condition:id,name'])
            ->where('invoice_serial', $salesSerial)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->first();

        // Return Blank Array if no sales Bill Found
        if (!$salesInvoice) {
            return [];
        }

        // 2. Fetch Purchase Invoice by exact sales_invoice_serial (e.g. '3' or '3.')
        $purchaseInvoice = PurchaseInvoice::with([
                'account:id,name,city', 
                'details' => function($q) {
                    $q->select('id', 'purchase_invoice_id', 'item_id', 'destination_id', 'condition_id', 'quantity', 'rate' , 'inclusive_rate', 'net_amount','purchase_order_serial')
                      ->with(['item:id,name', 'destination:id,name', 'condition:id,name']);
                }
            ])
            ->where('sales_invoice_serial', (string)$invoiceSerial)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->first();
                   
        // Check if Purchase Invoice exists first
        if (!$purchaseInvoice) {
            return [ 'message' => 'Purchase Bill Number not found for this Bill Number'];
        }
        
        //get pending amount of purchase invoice from reference table
        $ref = Reference::where('voucher_id', $purchaseInvoice->voucher_id)->first();
        $purchaseInvoice->reference_pending_amount = $ref ? $ref->pending_amount : 0;       

        // 3. Get Parameters based on condition_id from Purchase Invoice
        $conditionId = $purchaseInvoice->details->pluck('condition_id')->filter()->first();          
          
        $parameters = collect();
        if ($conditionId) {
        $parameters = DairyParameter::with([
                'element',
                'parameterDetails' => function ($query) {
                    $query->orderBy('rebate', 'asc'); // change column as needed
                }
            ])
            ->where('condition_id', $conditionId)
            ->where('company_id', $companyId)
            ->get();
                    if ($parameters->isEmpty()) {
                return [ 'message' => 'Dairy Analysis Parameters data not found'];
            }
        }else{
            return [ 'message' => 'Dairy Analysis Parameters data not found'];
        }

        // 4. Check for existing Analysis Results (History)
        $existingAnalysis = DairyAnalysis::with('details')
            ->where('sales_invoice_id', $salesInvoice->id)
            ->where('purchase_invoice_id', $purchaseInvoice->id)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->latest()
            ->first();

        return [
            'sales_inv_number' => $invoiceSerial,
            'sales_invoice' => $salesInvoice,
            'purchase_invoice' => $purchaseInvoice,
            'parameters' => $parameters,
            'existing_analysis' => []
        ];
    }

    /**
     * Get Filtered Records for Dairy Analysis Register (Using Eloquent Models)
     */
    public function getAnalysisListData(array $filters, int $companyId, int $financialYearId, $paginate = false)
    {
        $query = DairyAnalysis::select([
            'id', 
            'company_id', 
            'financial_year_id', 
            'purchase_invoice_id',
            'sales_invoice_id',                                       
            'purchase_rebate_total',
            'sales_inv_number',
        ])
        ->with([                               
            'purchaseInvoice:id,account_id,invoice_number,invoice_date,sales_invoice_serial,file_number,reference_number,total_quantity,vehicle_number,invoice_serial',
            'purchaseInvoice.account:id,name,city',                  
            'purchaseInvoice.details:id,purchase_invoice_id,destination_id',
            'purchaseInvoice.details.destination:id,name',
            'salesInvoice:id,account_id,invoice_date',
            'salesInvoice.account:id,name',
            'reference:source_id,source_type,pending_amount,is_closed'
        ])
        ->where('company_id', $companyId)
        ->where('financial_year_id', $financialYearId)
        ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
            $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
            $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');
            $q->whereHas('purchaseInvoice', fn($sub) => $sub->whereBetween('invoice_date', [$start, $end]));
        })
        // supplier filter
        ->when(!empty($filters['supplier_id']), function ($q) use ($filters) {
            $q->whereHas('purchaseInvoice', fn($sub) => $sub->where('account_id', $filters['supplier_id']));
        })
        // customer filter
        ->when(!empty($filters['account_id']), function ($q) use ($filters) {
            $q->whereHas('salesInvoice', fn($sub) => $sub->where('account_id', $filters['account_id']));
        })
        // destination filter
        ->when(!empty($filters['destination_id']), function ($q) use ($filters) {
            $q->whereHas('purchaseInvoice.details', fn($sub) => $sub->where('destination_id', $filters['destination_id']));
        })
        // payment status filter
        ->when(isset($filters['payment_status']) && $filters['payment_status'] !== '', function ($q) use ($filters) {
            $isClosed = $filters['payment_status'] == 'pending' ? 0 : 1;
            $q->whereHas('reference', fn($sub) => $sub->where('is_closed', $isClosed));
        })
        ->orderBy('id', 'DESC');
        if ($paginate) {
            $perPage = (int)($filters['size'] ?? 50);
            return $query->paginate($perPage > 0 ? $perPage : ($query->count() ?: 1));
        }
        return $query->get();
    }


    public function getAnalysisRebatePendingData(array $filters, int $companyId, int $financialYearId, $paginate = false)
    {
        $query = $this->baseQuery($companyId, $financialYearId);

        $rebateStatus = $filters['rebate_status'] ?? null;
        $hasSupplierFilter = !empty($filters['supplier_id']);

        if ($rebateStatus == 3) {
            // Status 3: Not Mapped.
            // Using LEFT JOIN with IS NULL is the fastest anti-join in MySQL.
            $query->leftJoin('purchase_invoices as pi', function ($join) use ($companyId, $financialYearId) {
                $join->on('pi.sales_invoice_serial', '=', 'sales_invoices.invoice_serial')
                    ->where('pi.company_id', $companyId)
                    ->where('pi.financial_year_id', $financialYearId);
            })
                ->whereNull('pi.id');

            $this->applyCommonFilters($query, $filters, false);
        } elseif ($rebateStatus == 1 || $rebateStatus == 2 || $hasSupplierFilter) {
            // Using a derived table (joinSub) isolates the complex joins from the outer query's execution plan.
            // This ensures blazing fast performance both when filtering by customer (account_id) and during pagination counts.
            $piSub = DB::table('purchase_invoices as pi')
                ->select('pi.sales_invoice_serial')
                ->whereNotNull('pi.sales_invoice_serial')
                ->where('pi.company_id', $companyId)
                ->where('pi.financial_year_id', $financialYearId);

            if ($hasSupplierFilter) {
                $piSub->where('pi.account_id', $filters['supplier_id']);
            }

            if ($rebateStatus == 1 || $rebateStatus == 2) {
                $piSub->join('purchase_invoice_sundries as pis', function ($join) {
                    $join->on('pis.purchase_invoice_id', '=', 'pi.id')
                        ->where('pis.code', 1008)
                        ->where(function ($v) {
                            $v->where('pis.value', 0)->orWhereNull('pis.value');
                        });
                })
                    ->join('references as ref', function ($join) use ($rebateStatus) {
                        $join->on('ref.voucher_id', '=', 'pi.voucher_id')
                            ->where('ref.source_type', \App\Enums\SourceType::PURCHASE);

                        if ($rebateStatus == 1) {
                            $join->where(function ($w) {
                                $w->where('ref.is_closed', 0)->orWhereNull('ref.is_closed');
                            });
                        } else {
                            $join->where('ref.is_closed', 1);
                        }
                    });
            }

            $piSub->distinct();

            $query->joinSub($piSub, 'filtered_pi', function ($join) {
                $join->on('filtered_pi.sales_invoice_serial', '=', 'sales_invoices.invoice_serial');
            });

            $this->applyCommonFilters($query, $filters, false);
        } else {
            $this->applyCommonFilters($query, $filters, true);
        }

        return $paginate
            ? $query->paginate($filters['size'] ?? 50)
            : $query->get();
    }

    protected function baseQuery(int $companyId, int $financialYearId)
    {
        return SalesInvoice::select([
            'sales_invoices.id',
            'sales_invoices.company_id',
            'sales_invoices.financial_year_id',
            'sales_invoices.account_id',
            'sales_invoices.sales_order_id',
            'sales_invoices.invoice_serial',
            'sales_invoices.invoice_date',
            'sales_invoices.grn_number',
            'sales_invoices.total_quantity',
            'sales_invoices.vehicle_number',
        ])->with([                  
            'account:id,name,city',                         
            'salesOrder:id,order_number,purchase_order_number',            
            'details:id,sales_invoice_id,item_id,destination_id,quantity', 
            'details.item:id,name', 
            'details.condition:id,name', 
            'details.destination:id,name', 
                        
            'linkedPurchaseInvoice:id,voucher_id,sales_invoice_serial,account_id,file_number,reference_number,total_quantity,net_amount,vehicle_number,invoice_date',
            'linkedPurchaseInvoice.account:id,name,city',
            'linkedPurchaseInvoice.details:id,purchase_invoice_id,destination_id',
            'linkedPurchaseInvoice.details.destination:id,name',
            'linkedPurchaseInvoice.reference:source_id,source_type,voucher_id,pending_amount,is_closed',

            'dairyAnalysis:id,sales_invoice_id,purchase_rebate_total'
        ])
            ->where('sales_invoices.company_id', $companyId)
            ->where('sales_invoices.financial_year_id', $financialYearId);
    }

    protected function applyCommonFilters($query, array $filters, bool $applySupplierFilter = true)
    {
        $query->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                $end   = Carbon::parse($filters['end_date'])->format('Y-m-d');
            $q->whereBetween('sales_invoices.invoice_date', [$start, $end]);
            })
            ->when(!empty($filters['account_id']), function ($q) use ($filters) {
            $q->where('sales_invoices.account_id', $filters['account_id']);
            })
            ->when($applySupplierFilter && !empty($filters['supplier_id']), function ($q) use ($filters) {
                $q->whereHas('linkedPurchaseInvoice', fn($sub) => $sub->where('account_id', $filters['supplier_id']));
            })
            ->when(!empty($filters['destination_id']), function ($q) use ($filters) {
                $q->whereHas('details', fn($sub) => $sub->where('destination_id', $filters['destination_id']));
            })
            ->when(!empty($filters['item_id']), function ($q) use ($filters) {
                $q->whereHas('details', fn($sub) => $sub->where('item_id', $filters['item_id']));
            })
            ->orderBy('sales_invoices.id', 'DESC');
    }

    public function countAllAnalysis(int $companyId, int $financialYearId): int
    {
        return DairyAnalysis::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function countAllRebatePending(int $companyId, int $financialYearId): int
    {
        return SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereHas('linkedPurchaseInvoice', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('payment_status', '!=', 'fully_paid')
                       ->orWhereNull('payment_status');
                });
            })->count();
    }

    /**
     * Get dropdown list of dairy analysis records (id → sales_inv_number).
     */
    public function getDropdownList(int $companyId, int $financialYearId)
    {
        return DairyAnalysis::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->select('id', 'sales_inv_number')
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * Load sales invoice, purchase invoice, and parameters directly by IDs
     * (bypasses the invoice-serial lookup and the "fully_paid" guard).
     */
    public function getInvoiceDataByIds(int $salesInvoiceId, int $purchaseInvoiceId, int $companyId): array
    {
        $salesInvoice = SalesInvoice::with([
            'account:id,name,city',
            'details:id,sales_invoice_id,condition_id,rate,inclusive_rate,quantity',
            'details.condition:id,name',
        ])->find($salesInvoiceId);

        if (!$salesInvoice) {
            return [];
        }

        $purchaseInvoice = PurchaseInvoice::with([
            'account:id,name,city',
            'details' => function ($q) {
                $q->select('id', 'purchase_invoice_id', 'item_id', 'destination_id', 'condition_id', 'quantity', 'rate', 'inclusive_rate', 'net_amount')
                  ->with(['item:id,name', 'destination:id,name', 'condition:id,name']);
            },
        ])->find($purchaseInvoiceId);

        if (!$purchaseInvoice) {
            return [];
        }

        $ref = Reference::where('voucher_id', $purchaseInvoice->voucher_id)->first();
        $purchaseInvoice->reference_pending_amount = $ref ? $ref->pending_amount : 0;

        $conditionId = $purchaseInvoice->details->pluck('condition_id')->filter()->first();

        $parameters = collect();
        if ($conditionId) {
            $parameters = DairyParameter::with([
                'element',
                'parameterDetails' => function ($query) {
                    $query->orderBy('rebate', 'asc');
                },
            ])
            ->where('condition_id', $conditionId)
            ->where('company_id', $companyId)
            ->get();
        }

        return [
            'sales_invoice'    => $salesInvoice,
            'purchase_invoice' => $purchaseInvoice,
            'parameters'       => $parameters,
        ];
    }

}
<?php

namespace App\Services;

use App\Repositories\DairyAnalysisRepository;
use Carbon\Carbon;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\DairyAnalysis;
use App\Models\Reference;
use DB;


class DairyAnalysisService
{
    protected DairyAnalysisRepository $repository;
    protected PurchaseInvoiceService $purchaseInvoiceService;


    public function __construct(DairyAnalysisRepository $repository, PurchaseInvoiceService $purchaseInvoiceService)
    {
        $this->repository = $repository;
        $this->purchaseInvoiceService = $purchaseInvoiceService;
    }

    /**
     * Get Invoice data for Dairy Analysis
     * 
     * @param int $invoiceSerial
     * @param int $companyId
     * @param int $financialYearId
     * @return array
     */
    public function getInvoiceData($invoiceSerial, $companyId, $financialYearId): array
    {
        $isExists = DairyAnalysis::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)->where('sales_inv_number', $invoiceSerial)->first();
        if($isExists){
            return [
                'is_duplicate' => true,
                'dairy_analysis_id' => $isExists->id,
            ];
        }
        
        $data = $this->repository->getInvoiceDataByBillSerial($invoiceSerial, $companyId, $financialYearId);


        if (empty($data['sales_invoice'])) {
            return $data;
        }

        $salesInvoice = $data['sales_invoice'];
        $purchaseInvoice = $data['purchase_invoice'];
        $rawParameters = collect($data['parameters'] ?? []);

        // 1. Format Sales Data
        $salesData = [
            's_id'             => $salesInvoice->id,
            's_no'             => $salesInvoice->invoice_serial,
            's_date'           => $salesInvoice->invoice_date,
            's_customer'       => $salesInvoice->account->name ?? '',
            's_customer_city'  => $salesInvoice->account->city ?? '',
            's_qty'            => $salesInvoice->details->first()->quantity ?? 0,
            's_inclusive_rate' => $salesInvoice->details->first()->inclusive_rate ?? 0,
            's_rate'           => $salesInvoice->details->first()->rate ?? 0,
            's_grn_no'         => $salesInvoice->grn_number,
            's_vehicle_no'     => $salesInvoice->vehicle_number,
            's_condition'      => $salesInvoice->details->pluck('condition.name')->filter()->first() ?? '--',
            's_condition_id'   => $salesInvoice->details->pluck('condition_id')->filter()->first(),
            's_product'        => $salesInvoice->details->first()->item->name ?? '--',
        ];

        // 2. Format Purchase Data (with multi-item support)
        $purchaseData = [];
        if ($purchaseInvoice) {
            $rateQtyDetail = [];

            if ($purchaseInvoice->payment_status == 'fully_paid') {
                return [
                    'error' => true,
                    'message' => 'Old Bill Found! Already Paid.',
                ];
            }

            foreach ($purchaseInvoice->details as $item) {
                $rateQtyDetail[] = [
                    'qty'            => $item->quantity,
                    'inclusive_rate' => $item->inclusive_rate,
                    'net_amount'     => $item->amount,
                    'condition'      => $item->condition->name ?? '--',
                    'po_serial'      => $item->purchase_order_serial,
                ];
            }


            $pBillPayAmount = (float)($purchaseInvoice->reference_pending_amount);

            $purchaseData = [
                'p_id'             => $purchaseInvoice->id,
                'p_no'             => $purchaseInvoice->invoice_number,
                'p_supp'           => $purchaseInvoice->account->name ?? '',
                'p_supp_id'        => $purchaseInvoice->account_id,
                'p_supp_city'      => $purchaseInvoice->account->city ?? '',
                'p_product'        => $purchaseInvoice->details->first()->item->name ?? '',
                'p_date'           => $purchaseInvoice->invoice_date,
                'p_grn_no'         => $purchaseInvoice->grn_number,
                'p_destination'    => $purchaseInvoice->details->first()->destination->name ?? '--',
                'p_condition'      => $purchaseInvoice->details->first()->condition->name ?? '--',
                'p_qty'            => $purchaseInvoice->details->sum('quantity'),
                'p_file_no'        => $purchaseInvoice->file_number,
                'p_rate'           => $purchaseInvoice->details->first()->rate ?? 0,
                'p_inclusive_rate' => $purchaseInvoice->details->first()->inclusive_rate ?? 0,
                'p_amount'         => $purchaseInvoice->total_amount,
                'p_pay_amount'     => $pBillPayAmount,
                'p_vehicle_no'     => $purchaseInvoice->vehicle_number,
                'p_details'        => $rateQtyDetail,
                'p_ref_no'         => $purchaseInvoice->reference_number,
                'p_inv_serial'     => $purchaseInvoice->invoice_serial,
            ];
        }

        // Map and Sort
        $processedParameters = $rawParameters->map(function ($param) {
            return [
                'element' => $param->element_id,
                'element_name' => $param->element->name ?? "Missing (ID: {$param->element_id})",
                'element_range' => $param->element->range ?? null,
                'guarantee' => $param->guarantee,
                's_rebate' => $param->sales_rebate,
                's_premium' => $param->sales_premium,
                'p_rebate' => $param->purchase_rebate,
                'p_premium' => $param->purchase_premium,
                'ranges' => $param->parameterDetails->map(function ($rd) {
                    return [
                        'from' => $rd->from,
                        'to' => $rd->to,
                        'difference' => $rd->difference,
                        'rebate' => $rd->rebate,
                        'premium' => $rd->premium,
                        'element_range' => $param->element->range ?? null
                    ];
                }),
            ];
        });
        // 4. History Matching (Matching old results with current parameters)
        // $existingAnalysis = $data['existing_analysis'];

        // Define strict element display order
        $order = [
            'MOISTURE'  => 1,
            'FIBER'     => 2,
            'SILICA'    => 3,
            'ALBUMIN'   => 4,
        ];

        $processedParameters = $processedParameters->sortBy(function ($p) use ($order) {
            $name = strtoupper($p['element_name'] ?? '');
            return $order[$name] ?? 999;
        })->values();
        // dd($processedParameters);


        // if ($existingAnalysis && $existingAnalysis->details->isNotEmpty()) {
        //     $processedParameters->transform(function ($param) use ($existingAnalysis) {
        //         $elementId = (int)($param['element'] ?? 0);
        //         $matched = $existingAnalysis->details->firstWhere('element_id', $elementId);

        //         if ($matched) {
        //             $param['guarantee'] = $matched->guarantee; // Use saved guarantee
        //             $param['actual_val'] = $matched->actual;
        //             $param['diff_val'] = $matched->diff;
        //             $param['s_rebate_val'] = $matched->sales_rebate;
        //             $param['s_premium_val'] = $matched->sales_premium;
        //             $param['p_rebate_val'] = $matched->purchase_rebate;
        //             $param['p_premium_val'] = $matched->purchase_premium;
        //         }
        //         return $param;
        //     });

        //     // for show old element if present in dairy analysis but not in parameters
        //     $existingElementIds = $processedParameters->pluck('element')->toArray();
        //     foreach ($existingAnalysis->details as $savedDetail) {
        //         if (!in_array($savedDetail->element_id, $existingElementIds)) {
        //             $processedParameters->push([
        //                 'element' => $savedDetail->element_id,
        //                 'element_name' => $savedDetail->element->name ?? null,
        //                 'element_range' => $savedDetail->element->range ?? null,
        //                 'guarantee' => $savedDetail->guarantee,
        //                 'actual_val' => $savedDetail->actual,
        //                 'diff_val' => $savedDetail->diff,
        //                 's_rebate_val' => $savedDetail->sales_rebate,
        //                 's_premium_val' => $savedDetail->sales_premium,
        //                 'p_rebate_val' => $savedDetail->purchase_rebate,
        //                 'p_premium_val' => $savedDetail->purchase_premium,
        //                 'ranges' => collect([]), // No ranges available since master is deleted
        //             ]);
        //         }
        //     }
        // }

        // Torn Bags comes from the parameter master — just ensure it stays last
        $isTornBags = fn($p) => stripos($p['element_name'] ?? '', 'Torn Bags') !== false;

        $tornBags = $processedParameters->filter($isTornBags);
        if ($tornBags->isNotEmpty()) {
            $processedParameters = $processedParameters->reject($isTornBags)
                ->values()
                ->concat($tornBags->values());
        }

        return [
            'sales_data' => $salesData,
            'purchase_data' => $purchaseData,
            'parameter' => $processedParameters,
        ];
    }

    /**
     * Return dropdown list: id → sales_inv_number for edit page.
     */
    public function getAnalysisDropdownList(int $companyId, int $financialYearId)
    {
        return $this->repository->getDropdownList($companyId, $financialYearId);
    }

    /**
     * Fetch full invoice + parameter data for an existing DairyAnalysis (edit mode).
     * Overlays saved actual values so the form is pre-filled.
     */
    
    public function getAnalysisDataById(int $id, int $companyId, int $financialYearId): array
    {
        $analysis = DairyAnalysis::with('details.element')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->first();

        if (!$analysis) {
            return ['message' => 'Dairy Analysis record not found.'];
        }

        $raw = $this->repository->getInvoiceDataByIds(
            $analysis->sales_invoice_id,
            $analysis->purchase_invoice_id,
            $companyId
        );

        if (empty($raw['sales_invoice'])) {
            return ['message' => 'Invoice data not found for this analysis.'];
        }

        $salesInvoice    = $raw['sales_invoice'];
        $purchaseInvoice = $raw['purchase_invoice'];
        $rawParameters   = collect($raw['parameters'] ?? []);

        // --- Sales Data (same mapping as getInvoiceData) ---
        $salesData = [
            's_id'             => $salesInvoice->id,
            's_no'             => $salesInvoice->invoice_serial,
            's_date'           => $salesInvoice->invoice_date,
            's_customer'       => $salesInvoice->account->name ?? '',
            's_customer_city'  => $salesInvoice->account->city ?? '',
            's_qty'            => $salesInvoice->details->first()->quantity ?? 0,
            's_inclusive_rate' => $salesInvoice->details->first()->inclusive_rate ?? 0,
            's_rate'           => $salesInvoice->details->first()->rate ?? 0,
            's_grn_no'         => $salesInvoice->grn_number,
            's_vehicle_no'     => $salesInvoice->vehicle_number,
            's_condition'      => $salesInvoice->details->pluck('condition.name')->filter()->first() ?? '--',
            's_condition_id'   => $salesInvoice->details->pluck('condition_id')->filter()->first(),
            's_product'        => $salesInvoice->details->first()->item->name ?? '--',
        ];

        // --- Purchase Data ---
        $rateQtyDetail = [];
        foreach ($purchaseInvoice->details as $item) {
            $rateQtyDetail[] = [
                'qty'            => $item->quantity,
                'inclusive_rate' => $item->inclusive_rate,
                'net_amount'     => $item->net_amount,
                'condition'      => $item->condition->name ?? '--',
                'po_serial'      => $item->purchase_order_serial,
            ];
        }

        $purchaseData = [
            'p_id'             => $purchaseInvoice->id,
            'p_no'             => $purchaseInvoice->invoice_number,
            'p_supp'           => $purchaseInvoice->account->name ?? '',
            'p_supp_id'        => $purchaseInvoice->account_id,
            'p_supp_city'      => $purchaseInvoice->account->city ?? '',
            'p_product'        => $purchaseInvoice->details->first()->item->name ?? '',
            'p_date'           => $purchaseInvoice->invoice_date,
            'p_grn_no'         => $purchaseInvoice->grn_number,
            'p_destination'    => $purchaseInvoice->details->first()->destination->name ?? '--',
            'p_condition'      => $purchaseInvoice->details->first()->condition->name ?? '--',
            'p_qty'            => $purchaseInvoice->details->sum('quantity'),
            'p_file_no'        => $purchaseInvoice->file_number,
            'p_rate'           => $purchaseInvoice->details->first()->rate ?? 0,
            'p_inclusive_rate' => $purchaseInvoice->details->first()->inclusive_rate ?? 0,
            'p_amount'         => $purchaseInvoice->total_amount,
            'p_pay_amount'     => (float)($purchaseInvoice->reference_pending_amount ?? 0),
            'p_vehicle_no'     => $purchaseInvoice->vehicle_number,
            'p_details'        => $rateQtyDetail,
            'p_ref_no'         => $purchaseInvoice->reference_number,
            'p_inv_serial'     => $purchaseInvoice->invoice_serial,
        ];

        // --- Parameters + overlay saved values ---
        $processedParameters = $rawParameters->map(function ($param) use ($analysis) {
            $mapped = [
                'element'       => $param->element_id,
                'element_name'  => $param->element->name ?? "Missing (ID: {$param->element_id})",
                'element_range' => $param->element->range ?? null,
                'guarantee'     => $param->guarantee,
                's_rebate'      => $param->sales_rebate,
                's_premium'     => $param->sales_premium,
                'p_rebate'      => $param->purchase_rebate,
                'p_premium'     => $param->purchase_premium,
                'ranges'        => $param->parameterDetails->map(fn($rd) => [
                    'from'          => $rd->from,
                    'to'            => $rd->to,
                    'difference'    => $rd->difference,
                    'rebate'        => $rd->rebate,
                    'premium'       => $rd->premium,
                    'element_range' => $param->element->range ?? null,
                ]),
            ];

            // Overlay saved actual values from analysis details
            $saved = $analysis->details->firstWhere('element_id', $param->element_id);
            if ($saved) {
                $mapped['actual_val']      = $saved->actual;
                $mapped['diff_val']        = $saved->diff;
                $mapped['rebate_pct_val']  = $saved->rebate_percentage;
                $mapped['s_rebate_val']    = $saved->sales_rebate;
                $mapped['s_premium_val']   = $saved->sales_premium;
                $mapped['p_rebate_val']    = $saved->purchase_rebate;
                $mapped['p_premium_val']   = $saved->purchase_premium;
            }

            return $mapped;
        });

        // Sort by element display order (same as getInvoiceData)
        $order = ['MOISTURE' => 1, 'FIBER' => 2, 'SILICA' => 3, 'ALBUMIN' => 4];
        $processedParameters = $processedParameters->sortBy(function ($p) use ($order) {
            $name = strtoupper($p['element_name'] ?? '');
            return $order[$name] ?? 999;
        })->values();

        // Torn Bags always last
        $isTornBags = fn($p) => stripos($p['element_name'] ?? '', 'Torn Bags') !== false;
        $tornBags   = $processedParameters->filter($isTornBags);
        if ($tornBags->isNotEmpty()) {
            $processedParameters = $processedParameters->reject($isTornBags)
                ->values()
                ->concat($tornBags->values());
        }

        return [
            'sales_data'    => $salesData,
            'purchase_data' => $purchaseData,
            'parameter'     => $processedParameters,
            'general'       => ['id' => $analysis->id],
        ];
    }

    /**
     * Store or Update Dairy Analysis
     * 
     * @param array $data
     * @param int|null $id
     * @return \App\Models\DairyAnalysis
     */
    /**
     * Store a new Dairy Analysis
     */
    public function storeAnalysis(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {

             $isAnalysisExists = DairyAnalysis::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)->where('sales_inv_number', $data['sales_inv_number'])->first();

            if($isAnalysisExists){
                    throw new \Exception(
                    'Entry is already Exists.'
                );
            }

            $salesBillId = $data['sales_bill_id'] ?? null;
            $purchaseBillId = $data['purchase_bill_id'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Validate Required IDs
            |--------------------------------------------------------------------------
            */
            if (!$salesBillId || !$purchaseBillId) {
                throw new \Exception(
                    'Sales Bill ID or Purchase Bill ID is missing.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Get Sales Invoice
            |--------------------------------------------------------------------------
            */
            $salesInvoice = SalesInvoice::lockForUpdate()->find($salesBillId);

            if (!$salesInvoice) {
                throw new \Exception('Sales Invoice not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Get Purchase Invoice
            |--------------------------------------------------------------------------
            */
            $purchaseInvoice = PurchaseInvoice::with('billSundries')
                ->lockForUpdate()
                ->find($purchaseBillId);

            if (!$purchaseInvoice) {
                throw new \Exception('Purchase Invoice not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Get Reference Entry
            |--------------------------------------------------------------------------
            */
            $reference = Reference::where([
                'voucher_id' => $purchaseInvoice->voucher_id,
                'company_id' => $companyId,
            ])
                ->lockForUpdate()
                ->first();

            if (!$reference) {
                throw new \Exception(
                    'Reference not found for the given Purchase Invoice.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Check Already Closed
            |--------------------------------------------------------------------------
            */
            if ($reference->is_closed == 1) {
                throw new \Exception('Purchase Bill already paid.');
            }

            $this->updatePurchaseBill($purchaseInvoice->id, round($data['purchase_rebate_total'] ?? 0));
           

            /*
            |--------------------------------------------------------------------------
            | Create Dairy Analysis
            |--------------------------------------------------------------------------
            */
            $analysis = DairyAnalysis::create([
                'uuid' => $data['uuid'],

                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,

                'sales_invoice_id' => $salesInvoice->id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'sales_inv_number' => $data['sales_inv_number'],

                'sales_premium_total' =>
                $data['sales_premium_total'] ?? 0,

                'sales_rebate_total' => $data['sales_rebate_total'], //store original value 

                'purchase_rebate_total' => $data['purchase_rebate_total'], //store original value 

                'purchase_premium_total' =>
                $data['purchase_premium_total'] ?? 0,


                'created_by' => current_user_id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Save Analysis Details
            |--------------------------------------------------------------------------
            */
            if (!empty($data['items'])) {

                $analysis->details()->createMany($data['items']);
            }

            return $analysis;
        });
    }

    public function updatePurchaseBill(int $id, float $newPurchaseRebateTotal)
    {
        $purchaseInvoice = PurchaseInvoice::with('details', 'billSundries')->find($id);
        // dd($purchaseInvoice);
        // Create Payload
        $items = [];
        foreach ($purchaseInvoice->details as $detail) {
            $items[] =  [
                "item_id" => $detail->item_id,
                "quantity" => $detail->quantity,
                "party_quantity" => $detail->party_quantity,
                "rate" => $detail->rate,
                "inclusive_rate" => $detail->inclusive_rate,
                "amount" => $detail->amount,
                "condition_id" => $detail->condition_id,
                "destination_id" => $detail->destination_id,
                "bag_count" => $detail->bag_count,
                "purchase_order_serial" => $detail->purchase_order_serial,
                "purchase_order_id" => $detail->purchase_order_id,
                "purchase_order_item_id" => $detail->purchase_order_item_id,
            ];
        }

        $billSundries = [];
        $oldRebate = 0;

        foreach ($purchaseInvoice->billSundries->sortBy('sort_order') as $sundry) {
            if ($sundry->code == 1008) {
                $oldRebate = $sundry->value;
                $billSundries[] =  [
                    "bill_sundry_id" => $sundry->sundry_id,
                    "bill_sundry_percentage" => $sundry->rate_percent,
                    "bill_sundry_value" => $newPurchaseRebateTotal,
                    "bill_sundry_modal_dr_id" => $sundry->bill_sundry_modal_dr_id,
                    "bill_sundry_modal_cr_id" => $sundry->bill_sundry_modal_cr_id,
                ];
            } else {
                $billSundries[] =  [
                    "bill_sundry_id" => $sundry->sundry_id,
                    "bill_sundry_percentage" => $sundry->rate_percent,
                    "bill_sundry_value" => $sundry->value,
                    "bill_sundry_modal_dr_id" => $sundry->bill_sundry_modal_dr_id,
                    "bill_sundry_modal_cr_id" => $sundry->bill_sundry_modal_cr_id,
                ];
            }
        }
        // dd($billSundries, $oldRebate);

        $payload = [
                "purchase_invoice_id" => $purchaseInvoice->id,
                "invoice_date" => $purchaseInvoice->invoice_date,
                "party_bill_date" =>    $purchaseInvoice->party_bill_date,
                "grn_id" => $purchaseInvoice->grn_id,
                "file_number" => $purchaseInvoice->file_number,
                "sales_invoice_serial" => $purchaseInvoice->sales_invoice_serial,
                "account_id" => $purchaseInvoice->account_id,
                "purchase_type_id" => $purchaseInvoice->purchase_type_id,
                "reference_number" => $purchaseInvoice->reference_number,
                "broker_id" => $purchaseInvoice->broker_id,
                "vehicle_number" => $purchaseInvoice->vehicle_number,
                "remarks" =>   $purchaseInvoice->remarks,
                "net_total" => ((float)$purchaseInvoice->net_amount + (float)$oldRebate) - (float)$newPurchaseRebateTotal,
                "items" => $items,
                "bill_sundries" => $billSundries,
                'rebate_from_analysis' => true,
        ];

        $this->purchaseInvoiceService->updateInvoice($payload, $purchaseInvoice->company_id, $purchaseInvoice->financial_year_id, $purchaseInvoice->id);

    }
    /**
     * Update an existing Dairy Analysis
     */
    public function updateAnalysis($id, array $data)
    {
        return DB::transaction(function () use ($data, $id) {
            $analysis = DairyAnalysis::lockForUpdate()->find($id);
            if (!$analysis) {
                throw new \Exception('Dairy Analysis record not found.');
            }

            $salesBillId = $data['sales_bill_id'] ?? null;
            $purchaseBillId = $data['purchase_bill_id'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Validate Required IDs
            |--------------------------------------------------------------------------
            */
            if (!$salesBillId || !$purchaseBillId) {
                throw new \Exception(
                    'Sales Bill ID or Purchase Bill ID is missing.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Get Sales Invoice
            |--------------------------------------------------------------------------
            */
            $salesInvoice = SalesInvoice::lockForUpdate()->find($salesBillId);

            if (!$salesInvoice) {
                throw new \Exception('Sales Invoice not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Get Purchase Invoice
            |--------------------------------------------------------------------------
            */
            $purchaseInvoice = PurchaseInvoice::with('billSundries')
                ->lockForUpdate()
                ->find($purchaseBillId);

            if (!$purchaseInvoice) {
                throw new \Exception('Purchase Invoice not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Get Reference Entry
            |--------------------------------------------------------------------------
            */
            $reference = Reference::where([
                'voucher_id' => $purchaseInvoice->voucher_id,
                'company_id' => $analysis->company_id,
            ])
                ->lockForUpdate()
                ->first();

            if (!$reference) {
                throw new \Exception(
                    'Reference not found for the given Purchase Invoice.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Check Already Closed
            |--------------------------------------------------------------------------
            */
            // dd($reference->is_closed, $reference->pending_amount);
            if ($reference->is_closed == 1) {
                throw new \Exception('Purchase Bill is already closed. Cannot update rebate.');
            }

            $this->updatePurchaseBill($purchaseInvoice->id, round($data['purchase_rebate_total'] ?? 0));


            /*
            |--------------------------------------------------------------------------
            | Update Dairy Analysis
            |--------------------------------------------------------------------------
            */
            // dd($data);
            $analysis->update([
                'sales_invoice_id' => $salesInvoice->id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'sales_rebate_total' => $data['sales_rebate_total'],
                'sales_premium_total' => $data['sales_premium_total'] ?? 0,
                'purchase_rebate_total' => $data['purchase_rebate_total'],
                'purchase_premium_total' => $data['purchase_premium_total'] ?? 0,
                // 'sales_rebate_percentage' => $data['sales_rebate_percentage'] ?? 0,
                // 'purchase_rebate_percentage' => $data['purchase_rebate_percentage'] ?? 0,
                'updated_by' => current_user_id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Save Analysis Details
            |--------------------------------------------------------------------------
            */
            if (!empty($data['items'])) {
                $analysis->details()->delete();
                $analysis->details()->createMany($data['items']);
            }

            return $analysis;
        });
    }

    /**
     * Internal method to process saving/updating
     */
    public function getAnalysisList($filters, $companyId, $financialYearId)
    {
        $records = $this->repository->getAnalysisListData($filters, $companyId, $financialYearId, true);

        $data = $records->getCollection()->map(function ($row, $index) use ($records) {
            return [
                'id'               => $row->id,
                'supplier_name'    => $row->purchaseInvoice->account->name ?? '--',
                'city'             => $row->purchaseInvoice->account->city ?? '--',
                'invoice_serial'    => $row->purchaseInvoice->sales_invoice_serial ?? '--',
                'file_no'          => $row->purchaseInvoice->file_number ?? ($row->salesInvoice->file_number ?? '--'),
                'p_date'           => format_date($row->purchaseInvoice->invoice_date) ?? '--',
                'reference_number' => $row->purchaseInvoice->reference_number ?? '--',
                'p_qty'            => (float)($row->purchaseInvoice->total_quantity ?? 0),
                'rebate_amount'    => (float)($row->purchase_rebate_total ?? 0),
                'bill_balance'     => (float)($row->reference->pending_amount ?? 0),
                'sales_inv_number' => $row->sales_inv_number ?? '--'
            ];
        });

        $grandTotal = $this->repository->countAllAnalysis($companyId, $financialYearId);
        $permissions = userPermissions([
            'dairy_analysis.view',
            'dairy_analysis.update',
            'dairy_analysis.print',
            // 'dairy_analysis.delete',
        ], true);
        return [
            'data'         => $data,
            'permissions'  => $permissions,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'grand_total'  => $grandTotal,
        ];
    }

    /**
     * Get grouped data for Print report
     */
    public function getPrintData($filters, $companyId, $financialYearId)
    {

        $records = $this->repository->getAnalysisListData($filters, $companyId, $financialYearId);
        $finaldata = [];
        foreach ($records as $row) {
            $supplierId = $row->purchaseInvoice->account_id ?? 0;

            $finaldata[$supplierId][] = [
                'rebate'        => $row->purchase_rebate_total ?? 0,
                'sale_invoice_serial' => $row->purchaseInvoice->sales_invoice_serial ?? '--',
                'reference_number' => $row->purchaseInvoice->reference_number ?? '--',
                'p_qty'         => (float)($row->purchaseInvoice->total_quantity ?? 0),
                'balance_amt'   => (float)($row->reference->pending_amount ?? 0),
                'supplier'      => $row->purchaseInvoice->account->name ?? '--',
                'file_no'       => $row->purchaseInvoice->file_number ?? ($row->salesInvoice->file_number ?? '--'),
                'date'          => $row->purchaseInvoice->invoice_date ?? ($row->salesInvoice->invoice_date ?? ''),
                'customer_name' => $row->salesInvoice->account->name ?? '--',
                'destination'   => $row->purchaseInvoice->details->first()->destination->name ?? '--',
            ];
        }

        uasort($finaldata, function ($a, $b) {
            return strcmp($a[0]['supplier'] ?? '', $b[0]['supplier'] ?? '');
        });

        return $finaldata;
    }

    /**
     * Get flattened data for Excel export
     */
    public function getExportData($filters, $companyId, $financialYearId)
    {
        $records = $this->repository->getAnalysisListData($filters, $companyId, $financialYearId);

        $rows = [];
        $sr = 1;
        foreach ($records as $row) {
            $rows[] = [
                'sr_no'         => $sr++,
                'supplier'      => $row->purchaseInvoice->account->name ?? '--',
                'sbill_no'      => $row->purchaseInvoice->sales_invoice_serial ?? '--',
                'file_no'       => $row->purchaseInvoice->file_number ?? ($row->salesInvoice->file_number ?? '--'),
                'date'          => format_date($row->purchaseInvoice->invoice_date ?? ($row->salesInvoice->invoice_date ?? '')),
                'pbill_no'      => $row->purchaseInvoice->invoice_serial ?? '--',
                'p_qty'         => (float)($row->purchaseInvoice->total_quantity ?? 0),
                'rebate'        => $row->purchase_rebate_total ?? 0,
                'balance_amt'   => (float)($row->reference->pending_amount ?? 0),
                'customer_name' => $row->salesInvoice->account->name ?? '--',
                'destination'   => $row->purchaseInvoice->details->first()->destination->name ?? '--',
            ];
        }

        return $rows;
    }

    /**
     * Prepare analysis data for print report
     * Calculates purchase invoice total and per-detail rebate/premium percentages
     *
     * @param DairyAnalysis $analysis
     * @return DairyAnalysis
     */
    public function preparePrintReportData(DairyAnalysis $analysis): DairyAnalysis
    {
        // Calculate purchase invoice total from line items (qty × rate)
        $purchaseInvoiceTotal = 0;
        foreach ($analysis->purchaseInvoice->details as $item) {
            $purchaseInvoiceTotal += (float)$item->quantity * (float)$item->inclusive_rate;
        }

        $analysis->purchaseInvoice['calculated_total'] = $purchaseInvoiceTotal;

        // Calculate rebate/premium percentages for each analysis detail
        foreach ($analysis->details as $detail) {
            $detail->purchase_rebate_percentage  = $purchaseInvoiceTotal > 0
                ? ($detail->purchase_rebate / $purchaseInvoiceTotal) * 100
                : 0;
            $detail->purchase_premium_percentage = $purchaseInvoiceTotal > 0
                ? ($detail->purchase_premium / $purchaseInvoiceTotal) * 100
                : 0;
        }

        return $analysis;
    }

    /**
     * Fetch ALL rebate statuses (1+2+3) in a single paginated DB query.
     * Much faster than calling each status separately and merging in PHP.
     */
    public function allRebateStatuses($request)
    {
        $filters = $request->only('start_date', 'end_date', 'account_id', 'supplier_id', 'destination_id','item_id');
        // No rebate_status key → repository returns all statuses in one query
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, true);

        $data = $records->getCollection()->map(function ($row) {
            $isSalesInvoice  = $row instanceof SalesInvoice;
            $salesInvoice    = $isSalesInvoice ? $row : $row->salesInvoice;
            $purchaseInvoice = $isSalesInvoice ? $row->linkedPurchaseInvoice : $row->purchaseInvoice;

            $salesDate    = $salesInvoice?->invoice_date    ? Carbon::parse($salesInvoice->invoice_date)    : null;

            if ($salesDate) {
                $days = abs($salesDate->diffInDays(Carbon::now()));
            } else {
                $days = '--';
            }
            return [
                'id'               => $row->id,
                'customer_name'    => $salesInvoice?->account?->name ?? '--',
                'destination'      => $salesInvoice?->details?->first()?->destination?->name
                    ?? ($purchaseInvoice?->details?->first()?->destination?->name ?? '--'),
                'po_no'            => $salesInvoice?->salesOrder?->order_no
                    ?? ($salesInvoice?->salesOrder?->purchase_order_number ?? '--'),
                'invoice_serial'   => $salesInvoice?->invoice_serial ?? '--',
                'item_name'        => $salesInvoice?->details?->first()?->item?->name ?? '--',
                's_date'           => $salesInvoice?->invoice_date ? format_date($salesInvoice->invoice_date) : '--',
                'grn_no'           => $salesInvoice?->grn_number ?? '--',
                'days'             => (int) $days,
                'file_no'          => $purchaseInvoice?->file_number ?? ($salesInvoice?->file_number ?? '--'),
                'reference_number' => $purchaseInvoice?->reference_number ?? '--',
                'supplier_name'    => $purchaseInvoice?->account?->name ?? '--',
                'city'             => $purchaseInvoice?->account?->city ?? '--',
                'p_qty'            => (float) ($purchaseInvoice?->total_quantity ?? 0),
                'rebate_amount'    => (float) ($row->dairyAnalysis?->purchase_rebate_total ?? $row->purchase_rebate_total ?? 0),
                'bill_balance'     => (float) ($purchaseInvoice?->net_amount ?? $salesInvoice?->total_amount ?? 0),
            ];
        });

        return [
            'data'         => $data,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'grand_total'  => $records->total(),
        ];
    }

    public function rebatePending($request, $paginate = true)
    {
        $filters = $request->only('start_date', 'end_date', 'account_id', 'supplier_id', 'destination_id', 'rebate_status','item_id');
        $filters['rebate_status'] = 1;
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, $paginate);

        $collection = $paginate ? $records->getCollection() : $records;
        $data = $collection->map(function ($row) {
            // In the new logic, Status 1 returns SalesInvoice models
            $isSalesInvoice = $row instanceof SalesInvoice;

            $salesInvoice = $isSalesInvoice ? $row : $row->salesInvoice;
            $purchaseInvoice = $isSalesInvoice ? $row->linkedPurchaseInvoice : $row->purchaseInvoice;

            $salesDate = $salesInvoice?->invoice_date ? Carbon::parse($salesInvoice->invoice_date) : null;

            if ($salesDate) {
                $days = abs($salesDate->diffInDays(Carbon::now()));
            } else {
                $days = '--';
            }

            return [
                'id'               => $row->id,
                'customer_name'    => $salesInvoice?->account?->name ?? '--',
                'destination'      => $salesInvoice?->details?->first()?->destination?->name ?? ($purchaseInvoice?->details?->first()?->destination?->name ?? '--'),
                'po_no'            => $salesInvoice?->salesOrder?->order_no ?? ($salesInvoice?->salesOrder?->purchase_order_number ??   '--'),
                'invoice_serial'   => $salesInvoice?->invoice_serial ?? '--',
                'item_name'        => $salesInvoice?->details?->first()?->item?->name ?? '--',
                's_date'           => $salesInvoice?->invoice_date ? format_date($salesInvoice->invoice_date) : '--',
                'grn_no'           => $salesInvoice?->grn_number ?? '--',
                'days'             => (int)$days,
                'file_no'          => $purchaseInvoice?->file_number ?? ($salesInvoice?->file_number ?? '--'),
                'reference_number' => $purchaseInvoice?->reference_number ?? '--',
                'supplier_name'    => $purchaseInvoice?->account?->name ?? '--',
                'city'             => $purchaseInvoice?->account?->city ?? '--',
                'p_qty'            => (float)($purchaseInvoice?->total_quantity ?? 0),
                'rebate_amount'    => (float)($row->purchase_rebate_total ?? 0),
                'bill_balance'     => (float)($purchaseInvoice?->net_amount ?? 0),
            ];
        });
        //   dd($data);

        if (!$paginate) {
            return $data;
        }

        $grandTotal = $this->repository->countAllRebatePending($companyId, $financialYearId);

        return [
            'data'         => $data,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'grand_total'  => $grandTotal,
        ];
    }

    public function paymentClearRebatePending($request, $paginate = true)
    {
        $filters = $request->only('start_date', 'end_date', 'account_id', 'supplier_id', 'destination_id','item_id');
        $filters['rebate_status'] = 2;
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, $paginate);

        $collection = $paginate ? $records->getCollection() : $records;
        $data = $collection->map(function ($row) {
            // All statuses now return SalesInvoice models
            $salesInvoice = $row;
            $purchaseInvoice = $row->linkedPurchaseInvoice;

            $salesDate = $salesInvoice?->invoice_date ? Carbon::parse($salesInvoice->invoice_date) : null;


            if ($salesDate) {
                $days = abs($salesDate->diffInDays(Carbon::now()));
            } else {
                $days = '--';
            }

            return [
                'id'               => $row->id,
                'customer_name'    => $salesInvoice?->account?->name ?? '--',
                'destination'      => $salesInvoice?->details?->first()?->destination?->name ?? ($purchaseInvoice?->details?->first()?->destination?->name ?? '--'),
                'po_no'            => $salesInvoice?->salesOrder?->order_no ?? ($salesInvoice?->salesOrder?->purchase_order_number ??   '--'),
                'invoice_serial'   => $salesInvoice?->invoice_serial ?? '--',
                'item_name'        => $salesInvoice?->details?->first()?->item?->name ?? '--',
                's_date'           => $salesInvoice?->invoice_date ? format_date($salesInvoice->invoice_date) : '--',
                'grn_no'           => $salesInvoice?->grn_number ?? '--',
                'days'             => (int)$days,
                'file_no'          => $purchaseInvoice?->file_number ?? ($salesInvoice?->file_number ?? '--'),
                'reference_number' => $purchaseInvoice?->reference_number ?? '--',
                'supplier_name'    => $purchaseInvoice?->account?->name ?? '--',
                'city'             => $purchaseInvoice?->account?->city ?? '--',
                'p_qty'            => (float)($purchaseInvoice?->total_quantity ?? 0),
                'rebate_amount'    => (float)($row->dairyAnalysis->purchase_rebate_total ?? 0),
                'bill_balance'     => (float)($purchaseInvoice?->reference?->pending_amount ?? 0),
            ];
        });
        //  dd($data);
        if (!$paginate) {
            return $data;
        }
        return [
            'data'         => $data,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
        ];
    }

    public function salesBillMapRemaining($request, $paginate = true)
    {
        $filters = $request->only('start_date', 'end_date', 'account_id', 'supplier_id', 'destination_id','item_id');
        $filters['rebate_status'] = 3;
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, $paginate);

        $collection = $paginate ? $records->getCollection() : $records;
        $data = $collection->map(function ($row) use ($filters) {
            // Check if $row is SalesInvoice or DairyAnalysis
            $isSalesInvoice = $row instanceof SalesInvoice;

            $salesInvoice = $isSalesInvoice ? $row : $row->salesInvoice;
            // $purchaseInvoice = $isSalesInvoice ? null : $row->purchaseInvoice;

            $salesDate = $salesInvoice?->invoice_date ? Carbon::parse($salesInvoice->invoice_date) : null;
            // $purchaseDate = $purchaseInvoice?->invoice_date ? Carbon::parse($purchaseInvoice->invoice_date) : null;

            if ($salesDate) {
                $days = abs($salesDate->diffInDays(Carbon::now()));
            } else {
                $days = '--';
            }

            return [
                'id'               => $row->id,
                'customer_name'    => $salesInvoice?->account?->name ?? '--',
                'destination'      => $salesInvoice?->details?->first()?->destination?->name ?? '--',
                'po_no'            => $salesInvoice?->salesOrder?->order_no ?? ($salesInvoice?->salesOrder?->purchase_order_number ??   '--'),
                'invoice_serial'   => $salesInvoice?->invoice_serial ?? '--',
                'item_name'        => $salesInvoice?->details?->first()?->item?->name ?? '--',
                's_date'           => $salesInvoice?->invoice_date ? format_date($salesInvoice->invoice_date) : '--',
                'grn_no'           => $salesInvoice?->grn_number ?? '--',
                'days'             => (int)$days,
                'file_no'          => '',
                'p_bill_no'        => '',
                'supplier_name'    => '',
                'city'             => '',
                'p_qty'            => '',
                'rebate_amount'    => (float)($row->purchase_rebate_total ?? 0),
                'bill_balance'     => (float)($salesInvoice?->total_amount ?? 0),
            ];
        });
        // dd($data);
        if (!$paginate) {
            return $data;
        }
        return [
            'data'         => $data,
            'last_page'    => $records->lastPage(),
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
        ];
    }

    public function getRebatePendingPrintData($request)
    {

        if ($request->has('currentFilter')) {
            $filters = $request->input('currentFilter', []);
        } else {
            $filters = $request->only('start_date', 'end_date', 'account_id', 'supplier_id', 'destination_id', 'rebate_status');
        }

        // Empty/missing rebate_status means "All" — same as the register view,
        // which skips the status filter entirely instead of defaulting to 1.
        $companyId = company_id();
        $financialYearId = financial_year_id();

        // Fetch ALL records (no pagination)
        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, false);

        $finaldata = [];
        foreach ($records as $row) {
            $salesInvoice = $row;
            $purchaseInvoice = $row->linkedPurchaseInvoice ?? null;

            $supplierId = $purchaseInvoice ? ($purchaseInvoice->account_id ?? 0) : 0;
            $supplierName = $purchaseInvoice ? ($purchaseInvoice->account->name ?? '--') : '--';
            $city = $purchaseInvoice ? ($purchaseInvoice->account->city ?? '--') : ($salesInvoice->account->city ?? '--');
            $destination = $salesInvoice && $salesInvoice->details->isNotEmpty()
                ? ($salesInvoice->details->first()->destination->name ?? '--')
                : ($purchaseInvoice && $purchaseInvoice->details->isNotEmpty() ? ($purchaseInvoice->details->first()->destination->name ?? '--') : '--');
            $referenceNumber = $purchaseInvoice ? ($purchaseInvoice->reference_number ?? '--') : '--';
            $purchaseInvoiceDate = $purchaseInvoice ? ($purchaseInvoice->invoice_date ?? '') : '';
            $vehicle = $purchaseInvoice ? ($purchaseInvoice->vehicle_number ?? '--') : ($salesInvoice->vehicle_number ?? '--');
            $pQty = $purchaseInvoice ? (float)($purchaseInvoice->total_quantity ?? 0) : 0.0;
            $rebateTotal = $salesInvoice->dairyAnalysis ? (float)($salesInvoice->dairyAnalysis->purchase_rebate_total ?? 0) : 0.0;

            $finaldata[$supplierId][] = [
                'supplier'                => $supplierName,
                'city'                    => $city,
                'customer_name'           => $salesInvoice->account->name ?? '--',
                'destination'             => $destination,
                'sale_invoice_serial'     => $salesInvoice->invoice_serial ?? '--',
                'grn_no'                  => $salesInvoice->grn_number ?? '--',
                'reference_number'        => $referenceNumber,
                'date'                    => $purchaseInvoiceDate,
                'vehicle'                 => $vehicle,
                'p_qty'                   => $pQty,
                'rebate'                  => $rebateTotal,
                'balance_amt'             => (float)($purchaseInvoice?->net_amount ?? 0),
            ];
        }

        uasort($finaldata, function ($a, $b) {
            return strcmp($a[0]['supplier'] ?? '', $b[0]['supplier'] ?? '');
        });

        return $finaldata;
    }

    /**
     * Get flattened data for Rebate Pending Excel export
     */
    public function getRebatePendingExportData($filters, $companyId, $financialYearId)
    {
        // Empty/missing rebate_status means "All" — same as the register view,
        // which skips the status filter entirely instead of defaulting to 1.
        $records = $this->repository->getAnalysisRebatePendingData($filters, $companyId, $financialYearId, false);

        $rows = [];
        $sr = 1;
        foreach ($records as $row) {
            $salesInvoice = $row;
            $purchaseInvoice = $row->linkedPurchaseInvoice ?? null;

            // 1. srno
            $srNo = $sr++;

            // 2. customer_name
            $customerName = $salesInvoice->account->name ?? '--';

            // 3. destination
            $destination = $salesInvoice && $salesInvoice->details->isNotEmpty()
                ? ($salesInvoice->details->first()->destination->name ?? '--')
                : ($purchaseInvoice && $purchaseInvoice->details->isNotEmpty() ? ($purchaseInvoice->details->first()->destination->name ?? '--') : '--');

            // 4. po_no
            $poNo = $salesInvoice->salesOrder->order_no ?? ($salesInvoice->salesOrder->purchase_order_number ?? '--');

            // 5. sbill_no
            $sBillNo = $salesInvoice->invoice_serial ?? '--';

            // 6. product_name
            $productName = $salesInvoice->details->first()->item->name ?? '--';

            // 7. sbill_date
            $sBillDate = $salesInvoice->invoice_date ? format_date($salesInvoice->invoice_date) : '--';

            // 8. sales_quantity
            $salesQty = (float)($salesInvoice->total_quantity ?? 0);

            // 9. s_vehicle_no
            $sVehicleNo = $salesInvoice->vehicle_number ?? '--';

            // 10. grn
            $grn = $salesInvoice->grn_number ?? '--';

            // 11. days
            $salesDate = $salesInvoice->invoice_date ? Carbon::parse($salesInvoice->invoice_date) : null;

            if ($salesDate) {
                $days = abs($salesDate->diffInDays(Carbon::now()));
            } else {
                $days = '--';
            }

            // 12. file_no
            $fileNo = $purchaseInvoice->file_number ?? ($salesInvoice->file_number ?? '--');

            // 13. party_bill_no
            $partyBillNo = $purchaseInvoice ? ($purchaseInvoice->reference_number ?? ($purchaseInvoice->invoice_number ?? '--')) : '--';

            // 14. supplier_name
            $supplierName = $purchaseInvoice ? ($purchaseInvoice->account->name ?? '--') : '--';

            // 15. purchase quantity
            $purchaseQty = $purchaseInvoice ? (float)($purchaseInvoice->total_quantity ?? 0) : 0.0;

            // 16. p_vehicle_no
            $pVehicleNo = $purchaseInvoice ? ($purchaseInvoice->vehicle_number ?? '--') : '--';

            $rows[] = [
                'sr_no'             => $srNo,
                'customer_name'     => $customerName,
                'destination'       => $destination,
                'po_no'             => $poNo,
                'sbill_no'          => $sBillNo,
                'product_name'      => $productName,
                'sbill_date'        => $sBillDate,
                'sales_quantity'    => $salesQty,
                's_vehicle_no'      => $sVehicleNo,
                'grn'               => $grn,
                'days'              => (int)$days,
                'file_no'           => $fileNo,
                'party_bill_no'     => $partyBillNo,
                'supplier_name'     => $supplierName,
                'purchase_quantity' => $purchaseQty,
                'p_vehicle_no'      => $pVehicleNo,
            ];
        }

        return $rows;
    }
}

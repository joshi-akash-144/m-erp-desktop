<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\DairyImport;
use App\Models\DairyImportItem;
use App\Models\Freight;
use App\Models\FreightInvoiceItemOrder;
use App\Models\FreightItem;
use App\Models\FrightInvoiceItem;
use App\Models\Reference;
use App\Models\VehicleIncome;
use App\Models\VoucherType;
use App\Repositories\FreightRepository;
use Illuminate\Support\Facades\DB;
use App\Services\FreightService;
use App\Services\AuditService;

class FreightInvoiceService
{
    public function __construct(
        protected VoucherService $voucherService,
        protected FreightService $freightService,
        protected FreightRepository $freightRepo,
        protected AuditService $auditService,
    ) {}

    public function freightInvoiceList(array $filters): array
    {
        $filters['entry_from'] = Freight::ENTRY_FROM_INVOICE;

        $paginator = $this->freightRepo->list($filters);

        $companyId = $filters['company_id'] ?? company_id();
        $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        $formattedData = collect($paginator->items())->flatMap(function ($freight) use ($savedOrders) {
            $rows = [];
            $items = $freight->items ?? collect();
            
            $items = $items->sort(function($a, $b) use ($savedOrders) {
                $zoneA = $a->zone->name;
                $zoneB = $b->zone->name;
                $orderA = $savedOrders[$a->item_id];
                $orderB = $savedOrders[$b->item_id];
                return [$zoneA, $orderA] <=> [$zoneB, $orderB];
            })->values();

            if ($items->isEmpty()) {
                $rows[] = [
                    'id' => $freight->id,
                    'invoice_number' => $freight->bill_no,
                    'invoice_date' => $freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '',
                    'account_name' => $freight->account ? $freight->account->name : '', 
                    'item_name' => '',
                    'zone' => '',
                    'quantity' => 0,
                    'rate' => 0,
                    'total_amount' => number_format($freight->total_amount ?? 0, 2),
                    'is_total' => false,
                ];
                return $rows;
            }

            $totalQty = 0;
            $totalAmount = 0;

            foreach ($items as $index => $item) {
                $qty = (float) $item->quantity;
                $amount = (float) ($item->quantity * $item->rate);

                $totalQty += $qty;
                $totalAmount += $amount;

                $rows[] = [
                    'id' => $freight->id,
                    'invoice_serial' => $index === 0 ? $freight->invoice_serial : '',
                    'invoice_date' => $index === 0 ? ($freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '') : '',
                    'account_name' => $index === 0 ? ($freight->account ? $freight->account->name : '') : '',
                    'item_name' => $item->item ? $item->item->name : '',
                    'zone' => $item->zone ? $item->zone->name : '',
                    'quantity' => number_format($qty, 2, '.', ''),
                    'rate' => number_format($item->rate ?? 0, 2),
                    'total_amount' => number_format($amount, 2, '.', ''),
                    'is_total' => false,
                ];
            }

            $rows[] = [
                'id' => $freight->id,
                'invoice_number' => '',
                'invoice_date' => '',
                'account_name' => 'Total',
                'item_name' => '',
                'zone' => '',
                'quantity' => number_format($totalQty, 2, '.', ''),
                'rate' => '',
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'is_total' => true,
            ];

            return $rows;
        })->toArray();

        $permissions = userPermissions([
            'freight_invoice.view',
            'freight_invoice.update',
            'freight_invoice.print',
            'freight_invoice.printZone',
            'freight_invoice.exportZone',
        ], true);

        $grandTotal = $this->freightRepo->countAll($filters['company_id'], $filters['financial_year_id'], Freight::ENTRY_FROM_INVOICE);

        return [
            'data' => $formattedData,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'grand_total' => $grandTotal,
            'permissions' => $permissions,
        ];
    }

    public function createFreightInvoice(array $data, int $companyId, int $financialYearId): Freight
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            if (!empty($data['uuid'])) {
                $existing = Freight::where('uuid', $data['uuid'])
                    ->where('company_id', $companyId)
                    ->first();

                if ($existing) {
                    throw new \Exception("Freight already exists", 409);
                }
            }

            $billInfo = $this->freightService->getNextBillNumber($data['account_id'], $companyId, $financialYearId);

            $freight = Freight::create([
                'uuid'              => $data['uuid'],
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'account_id'        => $data['account_id'],
                'invoice_serial'    => $billInfo['serial'],
                'invoice_number'    => $billInfo['bill_number'],
                'prefix'            => $billInfo['prefix'],
                'invoice_date'      => $data['invoice_date'],
                'from_date'         => $data['from_date'],
                'to_date'           => $data['to_date'],
                'total_amount'      => $data['total_amount'],
                'remarks'           => $data['narration'] ?? null,
                'entry_from'        => Freight::ENTRY_FROM_INVOICE,
                'created_by'        => current_user_id(),
                'reference_number'  => $billInfo['reference_number']
            ]);

            foreach ($data['items'] as $item) {
                $freight->items()->create([
                    'item_id'  => $item['item_id'],
                    'zone_id'  => $item['zone_id'],
                    'quantity' => $item['quantity'],
                    'rate'     => $item['rate'],
                    'amount'   => $item['amount'],
                ]);
            }



            $voucherLines = $this->prepareVoucherLines($data, $companyId);

            $voucherNumberDTO = $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, $companyId, $financialYearId);

            $voucherMaster = [
                'uuid'              => $freight->uuid,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_date'      => $freight->invoice_date,
                'voucher_type_id'   => VoucherType::SALE_INVOICE,
                'source_id'         => $freight->id,
                'source_type'       => SourceType::FREIGHT,
                'reference_number'  => $freight->invoice_number,
                'voucher_serial'    => $voucherNumberDTO->serial,
                'voucher_number'    => $voucherNumberDTO->voucher_number,
                'narration'         => $freight->remarks,
                'created_by'        => current_user_id(),
            ];

            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            if (!empty($data['dairy_import_ids'])) {
                DairyImport::whereIn('id', $data['dairy_import_ids'])
                    ->where('company_id', $companyId)
                    ->update(['is_used' => true]);

                $detail = DairyImportItem::whereIn('dairy_import_id', $data['dairy_import_ids'])->get();
                if ($detail) {
                    $this->createInvoiceDetail($freight->id, $detail);
                }

                $vehicleIncomeDetail = FrightInvoiceItem::where('freight_id', $freight->id)->get();

                $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');

                foreach ($vehicleIncomeDetail as $key => $vid) {
                    VehicleIncome::create([
                        'voucher_id' => $voucher->id,
                        'freight_id' => $freight->id,
                        'company_id'   => $voucher->company_id,
                        'financial_year_id' => $financialYearId,
                        'income_account_id' => $freightIncomeId,
                        'amount'            => ($vid->quantity * $vid->rate) ?? 0,
                        'vehicle_id'            => $vid->vehicle_id,
                        'income_date'       => $vid->billing_date,
                        'voucher_date'       => $voucher->voucher_date,
                    ]);
                }
            }

            $isBillWise = Account::find($data['account_id']);
            if ($isBillWise) {
                Reference::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $data['account_id'],
                    'reference_number' => $freight->reference_number,
                    'reference_date' => $freight->invoice_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $freight->total_amount,
                    'settled_amount' => 0,
                    'pending_amount' => $freight->total_amount,
                    'voucher_id' => $voucher->id,
                    'source_type'       => SourceType::FREIGHT,
                    'source_id'         => $freight->id,
                    'direction'          => 'debit',
                    'created_by' => current_user_id(),
                ]);
            }


            $freight->update(['voucher_id' => $voucher->id]);

            $this->logAudit([], $freight, $voucherLines, AuditTrail::ACTION_CREATE);

            return $freight;
        });
    }

    private function createInvoiceDetail(int $id, object $detail)
    {
        $insertEntry = [];
        $freightItem = FreightItem::where('freight_id', $id)->get();
        $zoneWiseItemRate = [];
        foreach ($freightItem as $key => $fti) {
            $zoneWiseItemRate[$fti->zone_id . '-' . $fti->item_id] = $fti->rate;
        }

        foreach ($detail as $key => $dt) {
            $insertEntry[] = [
                'freight_id' => $id,
                'item_id' => $dt->product_id,
                'quantity' => $dt->quantity,
                'import_date' => $dt->import_date,
                'billing_date' => $dt->billing_date,
                'customer_po_no' => $dt->customer_po_no,
                'sold_to_party' => $dt->sold_to_party,
                'to' => $dt->to,
                'from' => $dt->from,
                'vehicle_id' => $dt->vehicle_id,
                'zone_id' => $dt->zone_id,
                'lr_number' => $dt->lr_number,
                'dc_number' => $dt->dc_number,
                'rate' => $zoneWiseItemRate[$dt->zone_id . '-' . $dt->product_id],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (count($insertEntry) > 0) {
            FrightInvoiceItem::insert($insertEntry);
        }
    }

    private function prepareVoucherLines(array $data, int $companyId): array
    {
        $totalAmount = (float) $data['total_amount'];


        $lines = [];


        $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');

        // Dr: Customer / party account
        // $firstCrAccount = array_key_first($creditByAccount);
        $lines[] = [
            'account_id'         => $data['account_id'],
            'against_account_id' => $freightIncomeId,
            'debit'              => $totalAmount,
            'credit'             => 0,
            'is_party_account'   => true,
        ];

        // Cr: Income accounts (per item's sale type)

        $lines[] = [
            'account_id'         => $freightIncomeId,
            'against_account_id' => $data['account_id'],
            'debit'              => 0,
            'credit'             => round($totalAmount, 2),
            'is_party_account'   => false,
        ];


        return $lines;
    }

    public function prepareAuditData(Freight $freight, array $voucherLines, string $action): array
    {
        $freight->loadMissing('items.item.unit', 'items.zone', 'account');

        $billNumber = $freight->prefix ? $freight->prefix . $freight->reference_number : $freight->reference_number;
        if (empty($billNumber)) {
            $billNumber = $freight->invoice_number;
        }

        $invoiceDate = '';
        if ($freight->invoice_date) {
            $invoiceDate = $freight->invoice_date instanceof \Carbon\Carbon
                ? $freight->invoice_date->format('d-m-Y')
                : (is_string($freight->invoice_date) ? date('d-m-Y', strtotime($freight->invoice_date)) : '');
        }

        $fromDate = '';
        if ($freight->from_date) {
            $fromDate = $freight->from_date instanceof \Carbon\Carbon
                ? $freight->from_date->format('d-m-Y')
                : (is_string($freight->from_date) ? date('d-m-Y', strtotime($freight->from_date)) : '');
        }

        $toDate = '';
        if ($freight->to_date) {
            $toDate = $freight->to_date instanceof \Carbon\Carbon
                ? $freight->to_date->format('d-m-Y')
                : (is_string($freight->to_date) ? date('d-m-Y', strtotime($freight->to_date)) : '');
        }

        $data = [
            'voucher_no'       => (string) ($freight->invoice_serial ?? ''),
            'bill_number'      => (string) ($billNumber ?? ''),
            'reference_number' => (string) ($freight->reference_number ?? ''),
            'invoice_date'     => $invoiceDate,
            'from_date'        => $fromDate,
            'to_date'          => $toDate,
            'account_id'       => $freight->account_id,
            'account_name'     => $freight->account->name ?? '',
            'total_amount'     => number_format((float) ($freight->total_amount ?? 0), 2, '.', ''),
            'remarks'          => (string) ($freight->remarks ?? ''),
        ];

        // Voucher lines
        $accountIds = array_filter(array_column($voucherLines, 'account_id'));
        $accountsMap = !empty($accountIds)
            ? Account::whereIn('id', $accountIds)->pluck('name', 'id')->toArray()
            : [];

        foreach (array_values($voucherLines) as $i => $line) {
            $data["details.{$i}.account_id"]   = $line['account_id'] ?? null;
            if (isset($line['account_id'])) {
                $data["details.{$i}.account_name"] = $accountsMap[$line['account_id']] ?? '';
            }
            $data["details.{$i}.debit"]        = number_format((float) ($line['debit'] ?? 0), 2, '.', '');
            $data["details.{$i}.credit"]       = number_format((float) ($line['credit'] ?? 0), 2, '.', '');
        }

        // Items
        foreach ($freight->items->values() as $i => $d) {
            $data["items.{$i}.item_id"]        = $d->item_id;
            $data["items.{$i}.name"]           = $d->item->name ?? '';
            $data["items.{$i}.item_unit_name"] = $d->item->unit->name ?? '';
            $data["items.{$i}.zone_id"]        = $d->zone_id;
            $data["items.{$i}.zone_name"]      = $d->zone->name ?? '';
            $data["items.{$i}.quantity"]       = number_format((float) ($d->quantity ?? 0), 2, '.', '');
            $data["items.{$i}.rate"]           = number_format((float) ($d->rate ?? 0), 2, '.', '');
            $data["items.{$i}.amount"]         = number_format((float) ($d->amount ?? 0), 2, '.', '');
        }

        return $data;
    }

    private function logAudit(
        array   $data,
        Freight $freight,
        array   $voucherLines,
        string  $action
    ): mixed {
        if (!isAuditLog()) {
            return null;
        }

        $oldValues = $data['old_values'] ?? [];
        $newValues = [];

        if ($action === AuditTrail::ACTION_CREATE || $action === AuditTrail::ACTION_UPDATE) {
            $freight->load('items.item.unit', 'items.zone', 'account');
            $newValues = $this->prepareAuditData($freight, $voucherLines, $action);
        }

        if ($action === AuditTrail::ACTION_UPDATE) {
            $oldDot = \Illuminate\Support\Arr::dot($oldValues);
            $newDot = \Illuminate\Support\Arr::dot($newValues);
            $allKeys = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));
            $hasChanges = false;
            foreach ($allKeys as $key) {
                if (($oldDot[$key] ?? null) != ($newDot[$key] ?? null)) {
                    $hasChanges = true;
                    break;
                }
            }
            if (!$hasChanges) {
                return null;
            }
        }

        $orgAmount   = (float) ($oldValues['total_amount'] ?? 0);
        $finalAmount = (float) ($newValues['total_amount'] ?? 0);

        return $this->auditService->log([
            'company_id'        => $freight->company_id,
            'financial_year_id' => $freight->financial_year_id,
            'action'            => $action,
            'module'            => SourceType::FREIGHT,
            'record_type'       => AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => Freight::class,
            'source_id'         => $freight->id,
            'voucher_id'        => $freight->voucher_id,
            'reference_number'  => $freight->reference_number,
            'org_amount'        => $orgAmount,
            'final_amount'      => $finalAmount,
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ]);
    }
}

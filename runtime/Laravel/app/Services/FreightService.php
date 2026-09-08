<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\DairyImport;
use App\Models\DairyImportItem;
use App\Models\Freight;
use App\Models\FreightItem;
use App\Models\Reference;
use App\Models\VehicleIncome;
use App\Repositories\FreightRepository;
use App\Repositories\SalesInvoiceRepository;
use App\Models\VoucherType;
use App\Services\VoucherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Str;

class FreightService
{
    protected FreightRepository $freightRepo;
    protected SalesInvoiceRepository $salesRepo;
    protected VoucherService $voucherService;
    protected AuditService $auditService;

    public function __construct(
        FreightRepository $freightRepo,
        SalesInvoiceRepository $salesRepo,
        VoucherService $voucherService,
        AuditService $auditService
    ) {
        $this->freightRepo = $freightRepo;
        $this->salesRepo = $salesRepo;
        $this->voucherService = $voucherService;
        $this->auditService = $auditService;
    }
    public function store(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $accountId = $data['account_id'] ?? null;
            if (!$accountId) {
                throw new \Exception("Bill To account is required.");
            }

            $billInfo = $this->getNextBillNumber($accountId, $companyId, $financialYearId);

            $freight = Freight::create([
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'account_id'         => $accountId,
                'invoice_serial'     => $billInfo['serial'],
                'invoice_number'     => $billInfo['bill_number'],
                'prefix'             => $billInfo['prefix'],
                'reference_number'   => $billInfo['reference_number'],
                'invoice_date'       => $data['invoice_date'],
                'grn_serial'         => $data['grn_serial'],
                'lr_number'          => $data['lr_number'],
                'vehicle_id'         => $data['vehicle_id'],
                'consignor_id'       => $data['consignor_id'],
                'consignee_id'       => $data['consignee_id'],
                'from_destination_id' => $data['from_destination_id'],
                'to_destination_id'  => $data['to_destination_id'],
                'total_amount'       => $data['total_amount'],
                'remarks'            => $data['remarks'] ?? null,
                'entry_from'         => Freight::ENTRY_FROM_VOUCHER,
                'created_by'         => current_user_id(),
            ]);

            if (!empty($data['item_id'])) {
                $freight->items()->create([
                    'item_id'    => $data['item_id'],
                    'zone_id'    => null,
                    'quantity'   => 0,
                    'rate'       => $data['freight_rate'] ?? 0,
                    'amount'     => $data['total_amount'] ?? 0,
                    'bag_type'   => $data['bag_type'],
                    'bag_count'  => $data['bag_count'] ?? 0,
                    'net_weight' => $data['net_weight'] ?? 0,
                    'kms'        => $data['kms'] ?? 0,
                ]);
            }

            $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');
            if (!$freightIncomeId) {
                throw new \Exception("Freight Income account (Code: 40000) not found for this company.");
            }

            $voucherLines = $this->prepareVoucherLines($data, $companyId, $freightIncomeId);

            $voucherNumberDTO = $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, $companyId, $financialYearId);

            $voucherMaster = [
                'uuid' => $freight->uuid,
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_date' => $freight->invoice_date,
                'voucher_type_id' => VoucherType::SALE_INVOICE,
                'source_id' => $freight->id,
                'source_type' => SourceType::FREIGHT,
                'reference_number' => $freight->invoice_number,
                'voucher_serial' => $voucherNumberDTO->serial,
                'voucher_number' => $voucherNumberDTO->voucher_number,
                'narration' => $freight->remarks,
                'created_by' => current_user_id(),
            ];

            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $vehicleIncomeDetail = FreightItem::where('freight_id', $freight->id)->get();

            foreach ($vehicleIncomeDetail as $key => $vid) {
                VehicleIncome::create([
                    'voucher_id' => $voucher->id,
                    'freight_id' => $freight->id,
                    'company_id' => $voucher->company_id,
                    'financial_year_id' => $financialYearId,
                    'income_account_id' => $freightIncomeId,
                    'amount' => $freight->total_amount,
                    'vehicle_id' => $freight->vehicle_id,
                    'income_date' => $freight->invoice_date,
                    'voucher_date' => $voucher->voucher_date,
                ]);
            }

            $isBillWise = Account::find($accountId);
            if ($isBillWise) {
                Reference::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $accountId,
                    'reference_number' => $freight->invoice_number,
                    'reference_date' => $freight->invoice_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $freight->total_amount,
                    'settled_amount' => 0,
                    'pending_amount' => $freight->total_amount,
                    'voucher_id' => $voucher->id,
                    'source_type' => SourceType::SALES,
                    'source_id' => $freight->id,
                    'direction' => 'debit',
                    'created_by' => current_user_id(),
                ]);
            }

            $dairyImport = DairyImport::create([
                'uuid'              => Str::uuid(),
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'import_date'       => $freight->invoice_date,
                'import_type'       => DairyImport::FREIGHT_ENTRY,
                'product_id'        => null,
                'created_by'        => current_user_id(),
            ]);

            $now = now();

            $firstItem = $freight->items->first();
            DairyImportItem::create([
                'dairy_import_id' => $dairyImport->getKey(),
                'import_date'     => $freight->invoice_date,
                'product_id'      => $firstItem->item_id,
                'quantity'        => null,
                'billing_date'    => $freight->invoice_date,
                'customer_po_no'  => null,
                'sold_to_party'   => null,
                'to'              => $freight->to_destination_id,
                'from'            => $freight->from_destination_id,
                'vehicle_id'      => $freight->vehicle_id,
                'zone_id'         => null,
                'is_used'         => false,
                'lr_number'       => $freight->lr_number,
                'dc_number'       => null,
                'rate'            => $firstItem->rate,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $freight->update(['voucher_id' => $voucher->id]);

            $this->logAudit([], $freight, $voucherLines, AuditTrail::ACTION_CREATE);

            return $freight;
        });
    }

    public function update(string $id, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($id, $data, $companyId, $financialYearId) {
            $freight = Freight::with(['items'])->findOrFail($id);
            $oldValues = $this->buildAuditValues($freight);

            $accountId = $data['account_id'] ?? null;
            if (!$accountId) {
                throw new \Exception("Bill To account is required.");
            }

            // Find existing DairyImport
            $oldItem = $freight->items->first();
            if ($oldItem) {
                $oldDairyImportItem = DairyImportItem::where('lr_number', $freight->lr_number)
                    ->where('vehicle_id', $freight->vehicle_id)
                    ->where('product_id', $oldItem->item_id)
                    ->first();
                if ($oldDairyImportItem) {
                    DairyImport::where('id', $oldDairyImportItem->dairy_import_id)->delete();
                }
            }

            $freight->update([
                'account_id'         => $accountId,
                'invoice_date'       => $data['invoice_date'],
                'grn_serial'         => $data['grn_serial'],
                'lr_number'          => $data['lr_number'],
                'vehicle_id'         => $data['vehicle_id'],
                'consignor_id'       => $data['consignor_id'],
                'consignee_id'       => $data['consignee_id'],
                'from_destination_id' => $data['from_destination_id'],
                'to_destination_id'  => $data['to_destination_id'],
                'total_amount'       => $data['total_amount'],
                'remarks'            => $data['remarks'] ?? null,
                'updated_by'         => current_user_id(),
            ]);

            $freight->items()->delete();

            if (!empty($data['item_id'])) {
                $freight->items()->create([
                    'item_id'    => $data['item_id'],
                    'zone_id'    => null,
                    'quantity'   => 0,
                    'rate'       => $data['freight_rate'] ?? 0,
                    'amount'     => $data['total_amount'] ?? 0,
                    'bag_type'   => $data['bag_type'],
                    'bag_count'  => $data['bag_count'] ?? 0,
                    'net_weight' => $data['net_weight'] ?? 0,
                    'kms'        => $data['kms'] ?? 0,
                ]);
            }

            $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');
            if (!$freightIncomeId) {
                throw new \Exception("Freight Income account (Code: 40000) not found for this company.");
            }

            $voucherLines = $this->prepareVoucherLines($data, $companyId, $freightIncomeId);

            $voucherMaster = [
                'account_id' => $accountId,
                'voucher_date' => $freight->invoice_date,
                'narration' => $freight->remarks,
            ];

            $voucher = $this->voucherService->updateVoucher($voucherMaster, $voucherLines, $freight->voucher_id);

            VehicleIncome::where('freight_id', $freight->id)->delete();

            $vehicleIncomeDetail = FreightItem::where('freight_id', $freight->id)->get();

            foreach ($vehicleIncomeDetail as $key => $vid) {
                VehicleIncome::create([
                    'voucher_id' => $voucher->id,
                    'freight_id' => $freight->id,
                    'company_id' => $voucher->company_id,
                    'financial_year_id' => $financialYearId,
                    'income_account_id' => $freightIncomeId,
                    'amount' => $freight->total_amount,
                    'vehicle_id' => $freight->vehicle_id,
                    'income_date' => $freight->invoice_date,
                    'voucher_date' => $voucher->voucher_date,
                ]);
            }

            Reference::where('source_id', $freight->id)->where('source_type', SourceType::SALES)->delete();

            $isBillWise = Account::find($accountId);
            if ($isBillWise) {
                Reference::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $accountId,
                    'reference_number' => $freight->invoice_number,
                    'reference_date' => $freight->invoice_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $freight->total_amount,
                    'settled_amount' => 0,
                    'pending_amount' => $freight->total_amount,
                    'voucher_id' => $voucher->id,
                    'source_type' => SourceType::SALES,
                    'source_id' => $freight->id,
                    'direction' => 'debit',
                    'created_by' => current_user_id(),
                ]);
            }

            $dairyImport = DairyImport::create([
                'uuid'              => Str::uuid(),
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'import_date'       => $freight->invoice_date,
                'import_type'       => DairyImport::FREIGHT_ENTRY,
                'product_id'        => null,
                'created_by'        => current_user_id(),
            ]);

            $now = now();

            $firstItem = $freight->items->first();
            DairyImportItem::create([
                'dairy_import_id' => $dairyImport->getKey(),
                'import_date'     => $freight->invoice_date,
                'product_id'      => $firstItem->item_id,
                'quantity'        => null,
                'billing_date'    => $freight->invoice_date,
                'customer_po_no'  => null,
                'sold_to_party'   => null,
                'to'              => $freight->to_destination_id,
                'from'            => $freight->from_destination_id,
                'vehicle_id'      => $freight->vehicle_id,
                'zone_id'         => null,
                'is_used'         => false,
                'lr_number'       => $freight->lr_number,
                'dc_number'       => null,
                'rate'            => $firstItem->rate,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $this->logAudit(['old_values' => $oldValues], $freight, $voucherLines, AuditTrail::ACTION_UPDATE);

            return $freight;
        });
    }

    private function prepareVoucherLines(array $data, int $companyId, int $freightIncomeId): array
    {
        $totalAmount = (float) ($data['total_amount'] ?? 0);

        $lines = [];

        // Dr: Customer / party account
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

    public function getGrnBySerial(int $grnSerial, int $companyId, int $financialYearId)
    {
        return $this->freightRepo->getGrnBySerial($grnSerial, $companyId, $financialYearId);
    }

    public function getByLrNumber(string $lrNumber, int $companyId, int $financialYearId)
    {
        return $this->freightRepo->getByLrNumber($lrNumber, $companyId, $financialYearId);
    }

    public function getNextBillNumber(int $accountId, int $companyId, int $financialYearId): array
    {
        $prefixConfig = config("prefix.prefix") ?? [];
        $prefix = $prefixConfig[$accountId] ?? null;

        if ($prefix) {
            // Prefix company: serial counter is per-prefix
            $lastSerial = $this->freightRepo->getNextBillSerialByPrefix($companyId, $financialYearId, $accountId);
            $nextSerial = $lastSerial + 1;

            $billNumber = $prefix . $nextSerial;
            $referenceNumber = $nextSerial;

            return [
                "serial"           => $nextSerial,
                "bill_number"      => $billNumber,
                "display_number"   => $billNumber,
                "prefix"           => $prefix,
                "reference_number" => $referenceNumber,
            ];
        } else {
            // No-prefix company:
            $nextSerial = $this->getDefaultNextBillNumber($companyId, $financialYearId);
            $referenceNumber = $nextSerial;
            $billNumber = (string) $nextSerial;

            return [
                "serial"           => $nextSerial,
                "bill_number"      => $billNumber,
                "display_number"   => $billNumber,
                "prefix"           => null,
                "reference_number" => $referenceNumber,
            ];
        }
    }

    public function getDefaultNextBillNumber(int $companyId, int $financialYearId): int
    {
        $prefixConfig = config("prefix.prefix") ?? [];
        $prefixAccountIds = array_keys($prefixConfig);

        $lastSerial = (int) Freight::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereNotIn('account_id', $prefixAccountIds)
            ->max('invoice_serial');
        // dd($lastSerial);
        return $lastSerial + 1;
    }

    public function checkDuplicateLRNumber(string $lrNumber, int $companyId, int $financialYearId, $excludeId = null)
    {
        return $this->freightRepo->checkDuplicateLRNumber($lrNumber, $companyId, $financialYearId, $excludeId);
    }

    public function freightExportList(array $filters): Collection
    {
        $filters['entry_from'] = Freight::ENTRY_FROM_VOUCHER;

        $query = $this->freightRepo->buildQuery($filters);

        return $query->reorder('invoice_date', 'asc')->get()->map(function ($freight) {
            $firstItem = $freight->items->first();
            $billNumber = $freight->prefix ? $freight->prefix . $freight->reference_number : $freight->reference_number;

            return [
                'account_name'     => $freight->account ? $freight->account->name : '',
                'bill_number'      => $billNumber,
                'invoice_date'     => $freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '',
                'lr_number'        => $freight->lr_number,
                'vehicle_number'   => $freight->vehicle ? $freight->vehicle->name : '',
                'item_name'        => $freight->items->pluck('item.name')->filter()->implode(', '),
                'consignor'        => $freight->consignor ? $freight->consignor->name : '',
                'consignee'        => $freight->consignee ? $freight->consignee->name : '',
                'from_destination' => $freight->fromDestination ? $freight->fromDestination->name : '',
                'to_destination'   => $freight->toDestination ? $freight->toDestination->name : '',
                'bag_count'      => (int)   ($firstItem?->bag_count   ?? 0),
                'net_weight'     => (float) ($firstItem?->net_weight  ?? 0),
                'kms'            => (float) ($firstItem?->kms         ?? 0),
                'rate'           => (float) ($firstItem?->rate        ?? 0),
                'freight_amount' => (float) ($freight->total_amount   ?? 0),
            ];
        });
    }

    public function freightList(array $filters): array
    {
        $filters['entry_from'] = Freight::ENTRY_FROM_VOUCHER;

        $paginator = $this->freightRepo->list($filters);

        $formattedData = collect($paginator->items())->map(function ($freight) {
            $firstItem = $freight->items->first();
            $billNumber = $freight->prefix ? $freight->prefix . $freight->reference_number : $freight->reference_number;

            return [
                'id' => $freight->id,
                'voucher_number' => $freight->voucher->voucher_serial,
                'bill_number' => $billNumber,
                'invoice_date' => $freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '',
                'account_name' => $freight->account ? $freight->account->name : '',
                'grn_number' => $freight->grn_serial,
                'lr_number' => $freight->lr_number,
                'vehicle_number' => $freight->vehicle ? $freight->vehicle->name : '',
                'from_destination' => $freight->fromDestination ? $freight->fromDestination->name : '',
                'to_destination' => $freight->toDestination ? $freight->toDestination->name : '',
                'consignor' => $freight->consignor ? $freight->consignor->name : '',
                'consignee' => $freight->consignee ? $freight->consignee->name : '',
                'item_name' => $freight->items ? $freight->items->pluck('item.name')->filter()->implode(', ') : '',
                'bag_count'        => (int) ($firstItem?->bag_count ?? 0),
                'net_weight'       => number_format((float) ($firstItem?->net_weight ?? 0), 2),
                'kms'              => (float) ($firstItem?->kms ?? 0),
                'rate'             => number_format((float) ($firstItem?->rate ?? 0), 2),
                'total_amount'     => number_format((float) ($freight->total_amount ?? 0), 2),
            ];
        })->toArray();

        $permissions = userPermissions([
            'freight.view',
            'freight.update',
            'freight.print',
        ], true);

        $grandTotal = $this->freightRepo->countAll($filters['company_id'], $filters['financial_year_id'], Freight::ENTRY_FROM_VOUCHER);

        return [
            'data' => $formattedData,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'grand_total' => $grandTotal,
            'permissions' => $permissions,
        ];
    }

    public function prepareAuditData(Freight $freight, array $voucherLines, string $action): array
    {
        $freight->loadMissing('items.item.unit', 'account', 'vehicle', 'consignor', 'consignee', 'fromDestination', 'toDestination');

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

        $data = [
            'voucher_no'            => (string) ($freight->invoice_serial ?? ''),
            'bill_number'           => (string) ($billNumber ?? ''),
            'reference_number'      => (string) ($freight->reference_number ?? ''),
            'invoice_date'          => $invoiceDate,
            'account_id'            => $freight->account_id,
            'account_name'          => $freight->account->name ?? '',
            'grn_serial'            => (string) ($freight->grn_serial ?? ''),
            'lr_number'             => (string) ($freight->lr_number ?? ''),
            'vehicle_id'            => $freight->vehicle_id,
            'vehicle_number'        => $freight->vehicle->name ?? '',
            'consignor_id'          => $freight->consignor_id,
            'consignor_name'        => $freight->consignor->name ?? '',
            'consignee_id'          => $freight->consignee_id,
            'consignee_name'        => $freight->consignee->name ?? '',
            'from_destination_id'   => $freight->from_destination_id,
            'from_destination_name' => $freight->fromDestination->name ?? '',
            'to_destination_id'     => $freight->to_destination_id,
            'to_destination_name'   => $freight->toDestination->name ?? '',
            'total_amount'          => number_format((float) ($freight->total_amount ?? 0), 2, '.', ''),
            'remarks'               => (string) ($freight->remarks ?? ''),
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
            $data["items.{$i}.bag_type"]       = $d->bag_type ?? '';
            $data["items.{$i}.bag_count"]      = $d->bag_count ?? 0;
            $data["items.{$i}.net_weight"]     = number_format((float) ($d->net_weight ?? 0), 2, '.', '');
            $data["items.{$i}.kms"]            = (float) ($d->kms ?? 0);
            $data["items.{$i}.rate"]           = number_format((float) ($d->rate ?? 0), 2, '.', '');
            $data["items.{$i}.amount"]         = number_format((float) ($d->amount ?? 0), 2, '.', '');
        }

        return $data;
    }

    private function buildAuditValues(Freight $freight): array
    {
        $freight->loadMissing('items.item.unit', 'account', 'vehicle', 'consignor', 'consignee', 'fromDestination', 'toDestination');
        $oldVoucherLines = [];
        if ($freight->voucher_id) {
            $transactions = \App\Models\VoucherTransaction::where('voucher_id', $freight->voucher_id)->orderBy('id')->get();
            foreach ($transactions as $t) {
                $oldVoucherLines[] = [
                    'account_id' => $t->account_id,
                    'debit'      => $t->debit,
                    'credit'     => $t->credit,
                ];
            }
        }
        return $this->prepareAuditData($freight, $oldVoucherLines, '');
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
            $freight->load('items.item.unit', 'account', 'vehicle', 'consignor', 'consignee', 'fromDestination', 'toDestination');
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

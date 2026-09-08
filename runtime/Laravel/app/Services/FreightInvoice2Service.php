<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\Freight;
use App\Models\FreightContractorItem;
use App\Models\Reference;
use App\Models\VehicleIncome;
use App\Models\VoucherType;
use App\Repositories\FreightRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AuditTrail;
use Str;
use App\Services\AuditService;

class FreightInvoice2Service
{
    protected FreightRepository $freightRepo;
    protected VoucherService $voucherService;
    protected FreightService $freightService;
    protected AuditService $auditService;

    public function __construct(
        FreightRepository $freightRepo,
        VoucherService $voucherService,
        FreightService $freightService,
        AuditService $auditService
    ) {
        $this->freightRepo = $freightRepo;
        $this->voucherService = $voucherService;
        $this->freightService = $freightService;
        $this->auditService = $auditService;
    }

    public function store(array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $accountId = $data['account_id'] ?? null;
            if (!$accountId) {
                throw new \Exception("To Bill (account_id) is required.");
            }

            $uuid = $data['uuid'] ?? Str::uuid();
            if (Freight::where('uuid', $uuid)->exists()) {
                throw new \Exception("Duplicate request detected. This invoice has already been saved.");
            }

            // Get next bill number
            $billInfo = $this->freightService->getNextBillNumber($accountId, $companyId, $financialYearId);

            $freight = Freight::create([
                'uuid'               => $uuid,
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'account_id'         => $accountId,
                'invoice_serial'     => $billInfo['serial'],
                'invoice_number'     => $billInfo['bill_number'],
                'prefix'             => $billInfo['prefix'],
                'reference_number'   => $billInfo['reference_number'],
                'invoice_date'       => $data['invoice_date'] ?? now()->toDateString(),
                'total_amount'       => $data['total_amount'] ?? 0,
                'entry_from'         => Freight::ENTRY_FROM_INVOICE2,
                'created_by'         => current_user_id(),
            ]);

            $totalAmount = 0;
            $freightContractorItems = [];

            if (isset($data['destination_id']) && is_array($data['destination_id'])) {
                $now = now();
                foreach ($data['destination_id'] as $key => $destId) {
                    if (!$destId) continue;
                    
                    $amt = (float)($data['amount'][$key] ?? 0);
                    $totalAmount += $amt;

                    $freightContractorItems[] = [
                        'freight_id'     => $freight->id,
                        'destination_id' => $destId,
                        'contractor_id'  => $data['contractor_id'][$key] ?? null,
                        'vehicle_id'     => $data['vehicle_id'][$key] ?? null,
                        'date'           => !empty($data['bill_date'][$key]) ? Carbon::createFromFormat('d-m-Y', $data['bill_date'][$key])->format('Y-m-d') : null,
                        'code'           => $data['code'][$key] ?? null,
                        'route'          => $data['route'][$key] ?? null,
                        'vendor'         => $data['vendor'][$key] ?? null,
                        'bag_count'      => $data['bag'][$key] ?? 0,
                        'kms'            => $data['km'][$key] ?? 0,
                        'rate'           => $data['rate'][$key] ?? 0,
                        'amount'         => $amt,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }

            if (!empty($freightContractorItems)) {
                FreightContractorItem::insert($freightContractorItems);
            }

            // Update freight total if JS sent differently
            $freight->update(['total_amount' => $totalAmount]);

            // Voucher Creation
            $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');
            if (!$freightIncomeId) {
                throw new \Exception("Freight Income account (Code: 40000) not found for this company.");
            }

            $voucherLines = [];
            $voucherLines[] = [
                'account_id'         => $accountId,
                'against_account_id' => $freightIncomeId,
                'debit'              => $totalAmount,
                'credit'             => 0,
                'is_party_account'   => true,
            ];
            $voucherLines[] = [
                'account_id'         => $freightIncomeId,
                'against_account_id' => $accountId,
                'debit'              => 0,
                'credit'             => round($totalAmount, 2),
                'is_party_account'   => false,
            ];

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
                'narration' => "Freight Invoice 2 Bill No: " . $freight->invoice_number,
                'created_by' => current_user_id(),
            ];

            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);
            $freight->update(['voucher_id' => $voucher->id]);

            // Vehicle Income Mapping
            $vehicleIncomes = [];
            foreach ($freightContractorItems as $item) {
                if (!empty($item['vehicle_id']) && $item['amount'] > 0) {
                    $vehicleIncomes[] = [
                        'voucher_id' => $voucher->id,
                        'freight_id' => $freight->id,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'income_account_id' => $freightIncomeId,
                        'amount' => $item['amount'],
                        'vehicle_id' => $item['vehicle_id'],
                        'income_date' => $freight->invoice_date,
                        'voucher_date' => $voucher->voucher_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($vehicleIncomes)) {
                VehicleIncome::insert($vehicleIncomes);
            }

            // Bill Wise Tracking
            $isBillWise = Account::find($accountId);
            if ($isBillWise) { // Need to verify if isBillwise is a property, usually ->is_billwise
                Reference::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $accountId,
                    'reference_number' => $freight->invoice_number,
                    'reference_date' => $freight->invoice_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $totalAmount,
                    'settled_amount' => 0,
                    'pending_amount' => $totalAmount,
                    'voucher_id' => $voucher->id,
                    'source_type' => SourceType::SALES,
                    'source_id' => $freight->id,
                    'direction' => 'debit',
                    'created_by' => current_user_id(),
                ]);
            }

            return $freight;
        });
    }

    public function update(string $id, array $data, int $companyId, int $financialYearId)
    {
        return DB::transaction(function () use ($id, $data, $companyId, $financialYearId) {
            $freight = Freight::with(['contractorItems', 'account', 'voucher'])->findOrFail($id);
            
            // For audit log
            $oldVoucherLines = [];
            $oldValues = $this->prepareAuditData($freight, $oldVoucherLines, AuditTrail::ACTION_UPDATE);
            $data['old_values'] = $oldValues;
            
            $accountId = $data['account_id'] ?? null;
            if (!$accountId) {
                throw new \Exception("To Bill (account_id) is required.");
            }

            $freight->update([
                'account_id'         => $accountId,
                'invoice_date'       => $data['invoice_date'] ?? $freight->invoice_date,
                'total_amount'       => $data['total_amount'] ?? 0,
                'remarks'            => $data['narration'] ?? $freight->remarks,
            ]);

            // Clear old items and incomes
            FreightContractorItem::where('freight_id', $freight->id)->delete();
            VehicleIncome::where('freight_id', $freight->id)->delete();

            // Re-create items
            $totalAmount = 0;
            $freightContractorItems = [];

            if (isset($data['destination_id']) && is_array($data['destination_id'])) {
                $now = now();
                foreach ($data['destination_id'] as $key => $destId) {
                    if (!$destId) continue;
                    
                    $amt = (float)($data['amount'][$key] ?? 0);
                    $totalAmount += $amt;

                    $freightContractorItems[] = [
                        'freight_id'     => $freight->id,
                        'destination_id' => $destId,
                        'contractor_id'  => $data['contractor_id'][$key] ?? null,
                        'vehicle_id'     => $data['vehicle_id'][$key] ?? null,
                        'date'           => !empty($data['bill_date'][$key]) ? Carbon::createFromFormat('d-m-Y', $data['bill_date'][$key])->format('Y-m-d') : null,
                        'code'           => $data['code'][$key] ?? null,
                        'route'          => $data['route'][$key] ?? null,
                        'vendor'         => $data['vendor'][$key] ?? null,
                        'bag_count'      => $data['bag'][$key] ?? 0,
                        'kms'            => $data['km'][$key] ?? 0,
                        'rate'           => $data['rate'][$key] ?? 0,
                        'amount'         => $amt,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }

            if (!empty($freightContractorItems)) {
                FreightContractorItem::insert($freightContractorItems);
            }

            $freight->update(['total_amount' => $totalAmount]);

            $freightIncomeId = Account::where('company_id', $companyId)->where('code', 40000)->value('id');
            if (!$freightIncomeId) {
                throw new \Exception("Freight Income account (Code: 40000) not found for this company.");
            }

            $voucherLines = [];
            $voucherLines[] = [
                'account_id'         => $accountId,
                'against_account_id' => $freightIncomeId,
                'debit'              => $totalAmount,
                'credit'             => 0,
                'is_party_account'   => true,
            ];
            $voucherLines[] = [
                'account_id'         => $freightIncomeId,
                'against_account_id' => $accountId,
                'debit'              => 0,
                'credit'             => round($totalAmount, 2),
                'is_party_account'   => false,
            ];

            $voucherMaster = [
                'uuid' => $freight->uuid,
                'company_id' => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_date' => $freight->invoice_date,
                'voucher_type_id' => VoucherType::SALE_INVOICE,
                'source_id' => $freight->id,
                'source_type' => SourceType::FREIGHT,
                'reference_number' => $freight->invoice_number,
                'narration' => "Freight Invoice 2 Bill No: " . $freight->invoice_number,
                'created_by' => current_user_id(),
            ];

            if ($freight->voucher_id) {
                $voucher = $this->voucherService->updateVoucher($voucherMaster, $voucherLines, $freight->voucher_id);
            } else {
                $voucherNumberDTO = $this->voucherService->getNextVoucherNumber(VoucherType::SALE_INVOICE, $companyId, $financialYearId);
                $voucherMaster['voucher_serial'] = $voucherNumberDTO->serial;
                $voucherMaster['voucher_number'] = $voucherNumberDTO->voucher_number;
                $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);
                $freight->update(['voucher_id' => $voucher->id]);
            }

            // Vehicle Income Mapping
            $vehicleIncomes = [];
            foreach ($freightContractorItems as $item) {
                if (!empty($item['vehicle_id']) && $item['amount'] > 0) {
                    $vehicleIncomes[] = [
                        'voucher_id' => $voucher->id,
                        'freight_id' => $freight->id,
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'income_account_id' => $freightIncomeId,
                        'amount' => $item['amount'],
                        'vehicle_id' => $item['vehicle_id'],
                        'income_date' => $freight->invoice_date,
                        'voucher_date' => $voucher->voucher_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($vehicleIncomes)) {
                VehicleIncome::insert($vehicleIncomes);
            }

            // Update Bill Wise Tracking

            $oldRefExists = Reference::where('voucher_id', $voucher->id)->first();
            if($oldRefExists){
                $newPendingAmount = $totalAmount - $oldRefExists->settled_amount;
                $oldRefExists->reference_date = $freight->invoice_date;
                $oldRefExists->reference_number = $freight->invoice_number;
                $oldRefExists->account_id = $accountId;
                $oldRefExists->amount = $totalAmount;
                $oldRefExists->pending_amount = $newPendingAmount;
                $oldRefExists->is_closed = $oldRefExists->pending_amount == 0;
                $oldRefExists->closed_at = $oldRefExists->is_closed ? Carbon::now() : null;
                $oldRefExists->updated_by = current_user_id();
                $oldRefExists->updated_at = Carbon::now();
                $oldRefExists->save();
            }else{
                $isBillWise = Account::find($accountId);
                 if ($isBillWise->is_billwise) {
                    Reference::create([
                            'company_id' => $companyId,
                            'financial_year_id' => $financialYearId,
                            'account_id' => $accountId,
                            'reference_number' => $freight->invoice_number,
                            'reference_date' => $freight->invoice_date,
                            'file_number' => null,
                            'reference_type' => Reference::NewReference,
                            'amount' => $totalAmount,
                            'settled_amount' => 0,
                            'pending_amount' => $totalAmount,
                            'voucher_id' => $voucher->id,
                            'source_type' => SourceType::SALES,
                            'source_id' => $freight->id,
                            'direction' => 'debit',
                            'created_by' => current_user_id(),
                        ]);
                }
            }

            $this->logAudit($data, $freight, $voucherLines, AuditTrail::ACTION_UPDATE);

            return $freight;
        });
    }

    public function freightInvoice2List(array $filters): array
    {
        $filters['entry_from'] = Freight::ENTRY_FROM_INVOICE2;

        $paginator = $this->freightRepo->list($filters);

        $paginator->getCollection()->load(['contractorItems.destination', 'contractorItems.vehicle', 'contractorItems.contractor']);

        $formattedData = collect($paginator->items())->flatMap(function ($freight) {
            $rows = [];
            $items = $freight->contractorItems ?? collect();
            $billNumber = $freight->prefix ? $freight->prefix . $freight->reference_number : $freight->reference_number;

            if ($items->isEmpty()) {
                $rows[] = [
                    'id'             => $freight->id,
                    'bill_number'    => $billNumber,
                    'invoice_date'   => $freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '',
                    'account_name'   => $freight->account ? $freight->account->name : '',
                    'item_date'      => '',
                    'code'           => '',
                    'society_name'   => '',
                    'route'          => '',
                    'bag_count'      => 0,
                    'vehicle_no'     => '',
                    'vendor'         => '',
                    'kms'            => 0,
                    'rate'           => 0,
                    'amount'         => 0,
                    'contractor'     => '',
                    'is_total'       => false,
                ];
                return $rows;
            }

            $totalBagCount = 0;
            $totalAmount = 0;

            foreach ($items as $index => $item) {
                $totalBagCount += (float)$item->bag_count;
                $totalAmount += (float)$item->amount;

                $rows[] = [
                    'id'             => $freight->id,
                    'item_id'        => $item->id,
                    'bill_number'    => $index === 0 ? $billNumber : '',
                    'invoice_date'   => $index === 0 ? ($freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : '') : '',
                    'account_name'   => $index === 0 ? ($freight->account ? $freight->account->name : '') : '',
                    'item_date'      => $item->date ? date('d-m-Y', strtotime($item->date)) : '',
                    'code'           => $item->code,
                    'society_name'   => $item->destination ? $item->destination->name : '',
                    'route'          => $item->route,
                    'bag_count'      => $item->bag_count,
                    'vehicle_no'     => $item->vehicle ? $item->vehicle->name : '',
                    'vendor'         => $item->vendor,
                    'kms'            => $item->kms,
                    'rate'           => $item->rate,
                    'amount'         => $item->amount,
                    'contractor'     => $item->contractor ? $item->contractor->name : '',
                    'is_total'       => false,
                ];
            }

            $rows[] = [
                'id'             => $freight->id,
                'bill_number'    => '',
                'invoice_date'   => '',
                'account_name'   => 'Total',
                'item_date'      => '',
                'code'           => '',
                'society_name'   => '',
                'route'          => '',
                'bag_count'      => $totalBagCount,
                'vehicle_no'     => '',
                'vendor'         => '',
                'kms'            => '',
                'rate'           => '',
                'amount'         => $totalAmount,
                'contractor'     => '',
                'is_total'       => true,
            ];

            return $rows;
        })->toArray();

        $permissions = userPermissions([
            'freight_invoice2.view',
            'freight_invoice2.update',
            'freight_invoice2.print',
            'freight_invoice2.delete',
        ], true);

        $grandTotal = $this->freightRepo->countAll($filters['company_id'], $filters['financial_year_id'], Freight::ENTRY_FROM_INVOICE2);

        return [
            'data'         => $formattedData,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
            'grand_total'  => $grandTotal,
            'permissions'  => $permissions,
        ];
    }

    public function prepareAuditData(Freight $freight, array $voucherLines, string $action): array
    {
        $freight->loadMissing('contractorItems.destination', 'contractorItems.vehicle', 'contractorItems.contractor', 'account');

        $billNumber = $freight->prefix ? $freight->prefix . $freight->reference_number : $freight->reference_number;
        if (empty($billNumber)) {
            $billNumber = $freight->invoice_number;
        }

        $invoiceDate = '';
        if ($freight->invoice_date) {
            $invoiceDate = $freight->invoice_date instanceof Carbon
                ? $freight->invoice_date->format('d-m-Y')
                : (is_string($freight->invoice_date) ? date('d-m-Y', strtotime($freight->invoice_date)) : '');
        }

        $data = [
            'voucher_no'       => (string) ($freight->invoice_serial ?? ''),
            'bill_number'      => (string) ($billNumber ?? ''),
            'reference_number' => (string) ($freight->reference_number ?? ''),
            'invoice_date'     => $invoiceDate,
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
        foreach ($freight->contractorItems->values() as $i => $d) {
            $data["items.{$i}.destination_id"] = $d->destination_id;
            $data["items.{$i}.destination_name"]= $d->destination->name ?? '';
            $data["items.{$i}.contractor_id"]  = $d->contractor_id;
            $data["items.{$i}.contractor_name"]= $d->contractor->name ?? '';
            $data["items.{$i}.vehicle_id"]     = $d->vehicle_id;
            $data["items.{$i}.vehicle_name"]   = $d->vehicle->name ?? '';
            $data["items.{$i}.date"]           = $d->date ? date('d-m-Y', strtotime($d->date)) : '';
            $data["items.{$i}.code"]           = $d->code ?? '';
            $data["items.{$i}.route"]          = $d->route ?? '';
            $data["items.{$i}.vendor"]         = $d->vendor ?? '';
            $data["items.{$i}.bag_count"]      = number_format((float) ($d->bag_count ?? 0), 2, '.', '');
            $data["items.{$i}.kms"]            = number_format((float) ($d->kms ?? 0), 2, '.', '');
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
            $freight->loadMissing('contractorItems.destination', 'contractorItems.vehicle', 'contractorItems.contractor', 'account');
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

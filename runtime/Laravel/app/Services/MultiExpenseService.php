<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\JournalVoucher;
use App\Models\MultiExpenseVoucher;
use App\Models\MultiExpenseVoucherItem;
use App\Models\Reference;
use App\Models\VehicleExpense;
use App\Models\VoucherType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MultiExpenseService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function store(array $data, int $companyId, int $financialYearId): MultiExpenseVoucher
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {
            $this->validateBeforeSave($data, $companyId, $financialYearId);

            if (!empty($data['uuid'])) {
                $existing = MultiExpenseVoucher::where('uuid', $data['uuid'])
                    ->where('company_id', $companyId)
                    ->first();
                if ($existing) return $existing;
            }

            // Skip rows where every field is blank
            $rows = array_values(array_filter($data['items'], function ($item) {
                return !empty($item['vehicle_id']) || !empty($item['amount'])
                    || !empty($item['bill_no']) || !empty($item['bill_date']) || !empty($item['remark']);
            }));

            $totalAmount = 0;
            foreach ($rows as $row) {
                $totalAmount += (float) ($row['amount'] ?? 0);
            }

            if (empty($rows) || $totalAmount <= 0) {
                throw new \Exception('Please enter at least one valid item row with amount.');
            }

            $expense = MultiExpenseVoucher::create([
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'voucher_date'       => $data['voucher_date'],
                'day_for'            => $data['day_for'] ?? null,
                'account_id'         => $data['account_id'],
                'expense_account_id' => $data['expense_account_id'],
                'total_amount'       => $totalAmount,
                'narration'          => $data['narration'] ?? null,
                'created_by'         => current_user_id(),
            ]);

            foreach ($rows as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                if ($amount <= 0) continue;

                $serialInfo = $this->voucherService->getNextVoucherNumber(
                    VoucherType::JOURNAL,
                    $companyId,
                    $financialYearId
                );

                $voucherMaster = [
                    'uuid'              => \Illuminate\Support\Str::uuid()->toString(),
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'voucher_date'      => $expense->voucher_date,
                    'voucher_type_id'   => VoucherType::JOURNAL,
                    'source_id'         => null,
                    'source_type'       => SourceType::JOURNAL,
                    'reference_number'  => $row['bill_no'] ?? "",
                    'voucher_serial'    => $serialInfo->serial,
                    'voucher_number'    => $serialInfo->voucher_number,
                    'narration'         => !empty($row['remark']) ? $row['remark'] : '',
                    'created_by'        => current_user_id(),
                ];

                $voucherLines = [
                    [
                        'account_id'         => $data['expense_account_id'],
                        'against_account_id' => $data['account_id'],
                        'debit'              => $amount,
                        'credit'             => 0,
                        'is_party_account'   => false,
                        'line_number'        => 1,
                    ],
                    [
                        'account_id'         => $data['account_id'],
                        'against_account_id' => $data['expense_account_id'],
                        'debit'              => 0,
                        'credit'             => $amount,
                        'is_party_account'   => true,
                        'line_number'        => 2,
                    ],
                ];

                $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

                $journal = JournalVoucher::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'gst_nature'        => 'gst_not_applicable',
                    'voucher_id'        => $voucher->id,
                    'entry_from'        => JournalVoucher::ENTRY_FORM_VOUCHER,
                ]);

                $voucher->update([
                    'source_id' => $journal->id,
                ]);

                MultiExpenseVoucherItem::create([
                    'multi_expense_voucher_id' => $expense->id,
                    'voucher_id'               => $voucher->id,
                    'bill_no'                  => empty($row['bill_no']) ? null : $row['bill_no'],
                    'challan_number'           => empty($row['challan_number']) ? null : $row['challan_number'],
                    'bill_date'                => empty($row['bill_date']) ? null : $row['bill_date'],
                    'vehicle_id'               => $row['vehicle_id'],
                    'amount'                   => $amount,
                    'remark'                   => $row['remark'] ?? null,
                ]);

                if (!empty($row['challan_number'])) {
                    \App\Models\DieselItem::where('challan_number', $row['challan_number'])
                        ->where('is_closed', 0)
                        ->update([
                            'is_closed' => 1,
                            'closed_by_voucher_id' => $voucher->id,
                            'reference_number' => empty($row['bill_no']) ? null : $row['bill_no'],
                        ]);
                }

                VehicleExpense::create([
                    'voucher_id'         => $voucher->id,
                    'company_id'         => $companyId,
                    'financial_year_id'  => $financialYearId,
                    'vehicle_id'         => $row['vehicle_id'],
                    'expense_account_id' => $data['expense_account_id'],
                    'amount'             => $amount,
                    'bill_date'          => $row['bill_date'] ?? null,
                    'voucher_date'       => $expense->voucher_date,
                ]);

                $isRefCreate = Account::where('id', $data['account_id'])->where('is_billwise', 1)->exists();
                if ($isRefCreate) {
                    Reference::create([
                        'company_id' => $expense->company_id,
                        'financial_year_id' => $expense->financial_year_id,
                        'account_id' => $data['account_id'],
                        'reference_number' => $voucher->reference_number,
                        'reference_date' => $voucher->voucher_date,
                        'file_number' => null,
                        'reference_type' => Reference::NewReference,
                        'amount' => $amount,
                        'settled_amount' => 0,
                        'pending_amount' => $amount,
                        'voucher_id' => $voucher->id,
                        'source_type'       => SourceType::JOURNAL,
                        'source_id'         => $journal->id,
                        'direction'          => 'credit',
                        'created_by' => current_user_id(),
                    ]);
                }

                $freshVoucher = $voucher->fresh(['details.account']);
                $this->logJournalAudit($freshVoucher, \App\Models\AuditTrail::ACTION_CREATE);
            }

            return $expense->fresh(['items']);
        });
    }

    public function update(array $data, MultiExpenseVoucher $expense): MultiExpenseVoucher
    {
        return DB::transaction(function () use ($data, $expense) {
            $this->validateBeforeSave($data, $expense->company_id, $expense->financial_year_id, $expense);

            // Skip rows where every field is blank
            $rows = array_values(array_filter($data['items'], function ($item) {
                return !empty($item['vehicle_id']) || !empty($item['amount'])
                    || !empty($item['bill_no']) || !empty($item['bill_date']) || !empty($item['remark']);
            }));

            $totalAmount = 0;
            foreach ($rows as $row) {
                $totalAmount += (float) ($row['amount'] ?? 0);
            }

            if (empty($rows) || $totalAmount <= 0) {
                throw new \Exception('Please enter at least one valid item row with amount.');
            }

            $oldVoucherId = $expense->voucher_id;
            $oldVoucher = $oldVoucherId ? \App\Models\Voucher::find($oldVoucherId) : null;

            $expense->update([
                'voucher_date'       => $data['voucher_date'],
                'day_for'            => $data['day_for'] ?? null,
                'account_id'         => $data['account_id'],
                'expense_account_id' => $data['expense_account_id'],
                'total_amount'       => $totalAmount,
                'narration'          => $data['narration'] ?? null,
                'updated_by'         => current_user_id(),
                'updated_at'         => now(),
                'voucher_id'         => null,
            ]);

            $existingItems = $expense->items()->get()->keyBy('id');
            $incomingIds = array_filter(array_column($rows, 'id'));

            foreach ($existingItems as $item) {
                if ($item->challan_number) {
                    \App\Models\DieselItem::where('challan_number', $item->challan_number)
                        ->update([
                            'is_closed' => 0,
                            'closed_by_voucher_id' => null,
                            'reference_number' => null,
                        ]);
                }
            }

            // 1. Delete removed items and their vouchers
            $itemsToDelete = $existingItems->except($incomingIds);
            foreach ($itemsToDelete as $item) {
                if ($item->voucher) {
                    $freshVoucherForDelete = $item->voucher->fresh(['details.account']);
                    $oldValuesForDelete = $this->buildJournalAuditValues($freshVoucherForDelete);
                    $item->voucher->update(['deleted_by' => current_user_id()]);
                    $item->voucher->delete();
                    VehicleExpense::where('voucher_id', $item->voucher->id)->delete();

                    $ref = Reference::where('voucher_id', $item->voucher->id)->first();
                    if ($ref) {
                        $ref->update(['deleted_by' => current_user_id()]);
                        $ref->delete();
                    }
                    $this->logJournalAudit($freshVoucherForDelete, \App\Models\AuditTrail::ACTION_DELETE, $oldValuesForDelete);
                }

                $item->delete();
            }

            // If we still have an old voucher but all its existing items were deleted, we should delete the old voucher too, unless we reuse it for a new item. We can reuse it for the first new item.

            // 2. Process incoming rows (Update or Create)
            foreach ($rows as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                if ($amount <= 0) continue;

                $itemId = $row['id'] ?? null;
                $item = null;

                if ($itemId && $existingItems->has($itemId)) {
                    $item = $existingItems[$itemId];
                    $voucher = $item->voucher;
                } else {
                    $voucher = null;
                }

                // If no voucher exists for this item/row, try to claim the old voucher
                if (!$voucher && $oldVoucher) {
                    $voucher = $oldVoucher;
                    $oldVoucher = null;
                }

                if ($voucher) {
                    $freshVoucherForUpdate = $voucher->fresh(['details.account']);
                    $oldValuesForUpdate = $this->buildJournalAuditValues($freshVoucherForUpdate);
                    // Update existing voucher (whether it's the item's original voucher, or the claimed old voucher)
                    $voucher->update([
                        'voucher_date'     => $expense->voucher_date,
                        'reference_number' => $row['bill_no'] ?? "",
                        'narration'        => !empty($row['remark']) ? $row['remark'] : $expense->narration,
                        'updated_by'       => current_user_id(),
                    ]);

                    $voucher->details()->delete();
                    $voucher->details()->create([
                        'account_id'         => $data['expense_account_id'],
                        'against_account_id' => $data['account_id'],
                        'debit'              => $amount,
                        'credit'             => 0,
                        'is_party_account'   => false,
                    ]);
                    $voucher->details()->create([
                        'account_id'         => $data['account_id'],
                        'against_account_id' => $data['expense_account_id'],
                        'debit'              => 0,
                        'credit'             => $amount,
                        'is_party_account'   => true,
                    ]);

                    VehicleExpense::where('voucher_id', $voucher->id)->delete();
                    VehicleExpense::create([
                        'voucher_id'         => $voucher->id,
                        'company_id'         => $expense->company_id,
                        'financial_year_id'  => $expense->financial_year_id,
                        'vehicle_id'         => $row['vehicle_id'],
                        'expense_account_id' => $data['expense_account_id'],
                        'amount'             => $amount,
                        'bill_date'          => $row['bill_date'] ?? null,
                        'voucher_date'       => $expense->voucher_date,
                    ]);
                } else {
                    // Create new voucher
                    $serialInfo = $this->voucherService->getNextVoucherNumber(
                        VoucherType::JOURNAL,
                        $expense->company_id,
                        $expense->financial_year_id
                    );

                    $voucherMaster = [
                        'uuid'              => \Illuminate\Support\Str::uuid()->toString(),
                        'company_id'        => $expense->company_id,
                        'financial_year_id' => $expense->financial_year_id,
                        'voucher_date'      => $expense->voucher_date,
                        'voucher_type_id'   => VoucherType::JOURNAL,
                        'source_id'         => null,
                        'source_type'       => SourceType::JOURNAL,
                        'reference_number'  => $row['bill_no'] ?? "",
                        'voucher_serial'    => $serialInfo->serial,
                        'voucher_number'    => $serialInfo->voucher_number,
                        'narration'         => !empty($row['remark']) ? $row['remark'] : '',
                        'created_by'        => current_user_id(),
                    ];

                    $voucherLines = [
                        [
                            'account_id'         => $data['expense_account_id'],
                            'against_account_id' => $data['account_id'],
                            'debit'              => $amount,
                            'credit'             => 0,
                            'is_party_account'   => false,
                            'line_number'        => 1,
                        ],
                        [
                            'account_id'         => $data['account_id'],
                            'against_account_id' => $data['expense_account_id'],
                            'debit'              => 0,
                            'credit'             => $amount,
                            'is_party_account'   => true,
                            'line_number'        => 2,
                        ],
                    ];

                    $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

                    $journal = JournalVoucher::create([
                        'company_id'        => $expense->company_id,
                        'financial_year_id' => $expense->financial_year_id,
                        'gst_nature'        => 'gst_not_applicable',
                        'voucher_id'        => $voucher->id,
                        'entry_from'        => JournalVoucher::ENTRY_FORM_VOUCHER,
                    ]);

                    $voucher->update([
                        'source_id' => $journal->id,
                    ]);

                    VehicleExpense::create([
                        'voucher_id'         => $voucher->id,
                        'company_id'         => $expense->company_id,
                        'financial_year_id'  => $expense->financial_year_id,
                        'vehicle_id'         => $row['vehicle_id'],
                        'expense_account_id' => $data['expense_account_id'],
                        'amount'             => $amount,
                        'bill_date'          => $row['bill_date'] ?? null,
                        'voucher_date'       => $expense->voucher_date,
                    ]);
                }

                if ($item) {
                    $item->update([
                        'voucher_id' => $voucher->id,
                        'bill_no'    => empty($row['bill_no']) ? null : $row['bill_no'],
                        'challan_number' => empty($row['challan_number']) ? null : $row['challan_number'],
                        'bill_date'  => empty($row['bill_date']) ? null : $row['bill_date'],
                        'vehicle_id' => $row['vehicle_id'],
                        'amount'     => $amount,
                        'remark'     => $row['remark'] ?? null,
                    ]);
                } else {
                    MultiExpenseVoucherItem::create([
                        'multi_expense_voucher_id' => $expense->id,
                        'voucher_id'               => $voucher->id,
                        'bill_no'                  => empty($row['bill_no']) ? null : $row['bill_no'],
                        'challan_number'           => empty($row['challan_number']) ? null : $row['challan_number'],
                        'bill_date'                => empty($row['bill_date']) ? null : $row['bill_date'],
                        'vehicle_id'               => $row['vehicle_id'],
                        'amount'                   => $amount,
                        'remark'                   => $row['remark'] ?? null,
                    ]);
                }

                if (!empty($row['challan_number'])) {
                    \App\Models\DieselItem::where('challan_number', $row['challan_number'])
                        ->update([
                            'is_closed' => 1,
                            'closed_by_voucher_id' => $voucher->id,
                            'reference_number' => empty($row['bill_no']) ? null : $row['bill_no'],
                        ]);
                }

                $voucherReference = Reference::where('voucher_id', $voucher->id)->first();

                if ($voucherReference) {
                    if ($voucherReference->is_closed) {
                        throw new \Exception("Cannot update: Some references are already settled or paid.If you want to update this voucher, please delete the Payment Reference first.");
                    }

                    $voucherReference->update([
                        'account_id' => $data['account_id'],
                        'reference_number' => $voucher->reference_number,
                        'reference_date' => $voucher->voucher_date,
                        'amount' => $amount,
                        'pending_amount' => $amount - $voucherReference->settled_amount,
                        'is_closed' => $amount - $voucherReference->settled_amount == 0 ? 1 : 0,
                        'closed_at' => $amount - $voucherReference->settled_amount == 0 ? now() : null,
                        'updated_by' => current_user_id(),
                    ]);
                } else {
                    $isRefCreate = Account::where('id', $data['account_id'])->where('is_billwise', 1)->exists();
                    if ($isRefCreate) {
                        $sourceId = $voucher->source_id;
                        if (!$sourceId && isset($journal) && $journal->voucher_id == $voucher->id) {
                            $sourceId = $journal->id;
                        }

                        Reference::create([
                            'company_id' => $expense->company_id,
                            'financial_year_id' => $expense->financial_year_id,
                            'account_id' => $data['account_id'],
                            'reference_number' => $voucher->reference_number,
                            'reference_date' => $voucher->voucher_date,
                            'file_number' => null,
                            'reference_type' => Reference::NewReference,
                            'amount' => $amount,
                            'settled_amount' => 0,
                            'pending_amount' => $amount,
                            'voucher_id' => $voucher->id,
                            'source_type'       => SourceType::JOURNAL,
                            'source_id'         => $sourceId,
                            'direction'          => 'credit',
                            'created_by' => current_user_id(),
                        ]);
                    }
                }

                if (isset($oldValuesForUpdate)) {
                    $freshVoucherUpdated = $voucher->fresh(['details.account']);
                    $this->logJournalAudit($freshVoucherUpdated, \App\Models\AuditTrail::ACTION_UPDATE, $oldValuesForUpdate);
                    unset($oldValuesForUpdate);
                } else {
                    $freshVoucherCreated = $voucher->fresh(['details.account']);
                    $this->logJournalAudit($freshVoucherCreated, \App\Models\AuditTrail::ACTION_CREATE);
                }
            }

            // Cleanup old voucher if it wasn't claimed
            if ($oldVoucher) {
                $freshOldVoucher = $oldVoucher->fresh(['details.account']);
                $oldValuesOldVoucher = $this->buildJournalAuditValues($freshOldVoucher);
                $oldVoucher->update(['deleted_by' => current_user_id()]);
                $oldVoucher->delete();
                VehicleExpense::where('voucher_id', $oldVoucher->id)->delete();

                $ref = Reference::where('voucher_id', $oldVoucher->id)->first();
                if ($ref) {
                    $ref->update(['deleted_by' => current_user_id()]);
                    $ref->delete();
                }
                $this->logJournalAudit($freshOldVoucher, \App\Models\AuditTrail::ACTION_DELETE, $oldValuesOldVoucher);
            }


            return $expense->fresh(['items']);
        });
    }

    public function getExpenseAccounts(int $companyId): Collection
    {
        $expenseGroup = AccountGroup::where('company_id', $companyId)
            ->where('code', '420')
            ->first();

        if (!$expenseGroup) {
            return collect();
        }

        $childGroups = AccountGroup::where('company_id', $companyId)
            ->where('parent_id', $expenseGroup->id)
            ->pluck('id')
            ->toArray();

        $allGroupIds = array_merge([$expenseGroup->id], $childGroups);

        return Account::where('company_id', $companyId)
            ->whereIn('account_group_id', $allGroupIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getRegisterList(array $filters): array
    {
        $query = MultiExpenseVoucher::with([
            'account:id,name',
            'expenseAccount:id,name',
            'creator:id,name',
            'updater:id,name',
            'items.vehicle:id,name',
            'items.voucher:id,voucher_serial',
        ])
            ->where('multi_expense_vouchers.company_id', $filters['company_id'])
            ->where('multi_expense_vouchers.financial_year_id', $filters['financial_year_id']);

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['expense_account_id'])) {
            $query->where('expense_account_id', $filters['expense_account_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('voucher_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('voucher_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['voucher_serial'])) {
            $query->whereHas('items.voucher', function ($q) use ($filters) {
                $q->where('voucher_serial', $filters['voucher_serial']);
            });
        }

        $page     = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['size'] ?? 50);
        $total    = $query->count();

        $records = $query->orderByDesc('multi_expense_vouchers.id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        $voucherIds = [];
        foreach ($records as $expense) {
            foreach ($expense->items as $item) {
                if ($item->voucher_id) {
                    $voucherIds[] = $item->voucher_id;
                }
            }
        }
        $paidVoucherIds = \App\Models\Reference::whereIn('voucher_id', $voucherIds)
            ->where('is_closed', 1)
            ->pluck('voucher_id')
            ->toArray();

        // Build flat rows: one per voucher
        $flatRows = [];
        $offset   = ($page - 1) * $pageSize;

        foreach ($records as $idx => $expense) {
            $items     = $expense->items;
            $firstItem = $items->first();
            $vSerial   = $firstItem?->voucher?->voucher_serial ?? '-';

            $isLocked = false;
            foreach ($items as $item) {
                if ($item->voucher_id && in_array($item->voucher_id, $paidVoucherIds)) {
                    $isLocked = true;
                    break;
                }
            }

            // Single row per voucher
            $flatRows[] = [
                'id'                  => $expense->id,
                'row_num'             => $offset + $idx + 1,
                'voucher_serial'      => $vSerial,
                'voucher_date'        => $expense->voucher_date?->format('d/m/Y') ?? '-',
                'account_name'        => $expense->account?->name ?? '-',
                'expense_account_name' => $expense->expenseAccount?->name ?? '-',
                'created_by'          => $expense->creator?->name ?? '-',
                'updated_by'          => $expense->updater?->name ?? '-',
                'narration'           => $expense->narration ?? '-',
                'amount'              => (float) $expense->total_amount,
                'is_locked'           => $isLocked,
            ];
        }

        $permissions = [
            'update' => true,
            'print'  => true,
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

    public function voucherSerials(int $companyId, int $financialYearId)
    {
        return MultiExpenseVoucherItem::join('multi_expense_vouchers', 'multi_expense_vouchers.id', '=', 'multi_expense_voucher_items.multi_expense_voucher_id')
            ->where('multi_expense_vouchers.company_id', $companyId)
            ->where('multi_expense_vouchers.financial_year_id', $financialYearId)
            ->whereNotNull('multi_expense_voucher_items.voucher_id')
            ->join('vouchers', 'vouchers.id', '=', 'multi_expense_voucher_items.voucher_id')
            ->orderByDesc(DB::raw('CAST(vouchers.voucher_serial AS UNSIGNED)'))
            ->pluck('vouchers.voucher_serial')
            ->unique()
            ->values();
    }

    public function validateBeforeSave(array $data, int $companyId, int $financialYearId, ?MultiExpenseVoucher $expense = null)
    {
        $financialYear = \App\Models\FinancialYear::find($financialYearId);
        if ($financialYear) {
            $voucherDate = \Carbon\Carbon::parse($data['voucher_date'])->format('Y-m-d');
            if ($voucherDate < $financialYear->start_date || $voucherDate > $financialYear->end_date) {
                throw ValidationException::withMessages([
                    'voucher_date' => "Voucher date ({$data['voucher_date']}) must be within the current financial year (" . \Carbon\Carbon::parse($financialYear->start_date)->format('d-m-Y') . " to " . \Carbon\Carbon::parse($financialYear->end_date)->format('d-m-Y') . ")."
                ]);
            }
        }

        $accountId = $data['account_id'];
        $partyName = \App\Models\Account::find($accountId)->name ?? 'this party';

        $excludeVoucherIds = [];
        if ($expense) {
            $excludeVoucherIds = $expense->items()->pluck('voucher_id')->filter()->toArray();
            if (!empty($expense->voucher_id)) {
                $excludeVoucherIds[] = $expense->voucher_id;
            }
        }

        foreach ($data['items'] as $index => $item) {
            $isDirty = !empty($item['vehicle_id']) || !empty($item['amount'])
                || !empty($item['bill_no']) || !empty($item['bill_date']) || !empty($item['remark']);
            if (!$isDirty) continue;

            if (!empty($item['bill_no'])) {
                $exists = \App\Models\Voucher::where('reference_number', $item['bill_no'])
                    ->where('company_id', $companyId)
                    ->whereHas('details', function ($q) use ($accountId) {
                        $q->where('account_id', $accountId)->where('is_party_account', 1);
                    })
                    ->when(!empty($excludeVoucherIds), function ($q) use ($excludeVoucherIds) {
                        $q->whereNotIn('id', $excludeVoucherIds);
                    })
                    ->first();

                if ($exists) {
                    $rowNum = $index + 1;
                    $existingDate = $exists->voucher_date ? \Carbon\Carbon::parse($exists->voucher_date)->format('d-m-Y') : 'Unknown Date';
                    $voucherSerial = $exists->voucher_serial ?? 'N/A';
                    throw ValidationException::withMessages([
                        'items' => "Row {$rowNum}: Bill No \"{$item['bill_no']}\" already exists in Voucher No \"{$voucherSerial}\" with date {$existingDate} for party \"{$partyName}\"."
                    ]);
                }
            }
        }
    }

    private function buildJournalAuditValues($voucher)
    {
        $details = [];
        foreach ($voucher->details as $row) {
            $details[] = [
                'account_id'    => $row->account_id,
                'account_name'  => $row->account?->name ?? 'Unknown',
                'dr_cr'         => $row->debit > 0 ? 'DR' : 'CR',
                'debit_amount'  => number_format((float)($row->debit ?? 0), 2, '.', ''),
                'credit_amount' => number_format((float)($row->credit ?? 0), 2, '.', ''),
            ];
        }

        return [
            'voucher_no'       => $voucher->voucher_serial,
            'voucher_date'     => $voucher->voucher_date,
            'narration'        => $voucher->narration,
            'reference_number' => $voucher->reference_number,
            'details'          => $details,
        ];
    }

    private function logJournalAudit($voucher, $action, $oldValues = [])
    {
        if (!\isAuditLog()) return;

        $newValues = [];
        if ($action == \App\Models\AuditTrail::ACTION_CREATE || $action == \App\Models\AuditTrail::ACTION_UPDATE) {
            $newValues = $this->buildJournalAuditValues($voucher);
        }

        if ($action == \App\Models\AuditTrail::ACTION_UPDATE) {
            $oldDot = \Illuminate\Support\Arr::dot($oldValues);
            $newDot = \Illuminate\Support\Arr::dot($newValues);

            $hasChanges = false;
            $allKeys = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));

            foreach ($allKeys as $key) {
                if (($oldDot[$key] ?? null) != ($newDot[$key] ?? null)) {
                    $hasChanges = true;
                    break;
                }
            }

            if (!$hasChanges) {
                return;
            }
        }

        $origionalAmount = collect($oldValues['details'] ?? [])->sum(function ($item) {
            return (float)($item['debit_amount'] ?? 0);
        });
        $finalAmount = collect($newValues['details'] ?? [])->sum(function ($item) {
            return (float)($item['debit_amount'] ?? 0);
        });

        $auditData = [
            'company_id'        => $voucher->company_id,
            'financial_year_id' => $voucher->financial_year_id,
            'action'            => $action,
            'module'            => \App\Enums\SourceType::JOURNAL,
            'record_type'       => \App\Models\AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => \App\Models\JournalVoucher::class,
            'source_id'         => $voucher->source_id ?? null,
            'voucher_id'        => $voucher->id,
            'reference_number'  => $voucher->reference_number,
            'org_amount'        => $origionalAmount,
            'final_amount'      => $finalAmount,
            'version'           => 0,
            'http_method'       => request()->method(),
            'user_id'           => current_user_id(),
            'user_name'         => current_user()?->name ?? 'System',
            'ip_address'        => (request()->header('CF-Connecting-IP') ?? request()->header('True-Client-IP') ?? request()->header('X-Real-IP') ?? trim(explode(',', request()->header('X-Forwarded-For', ''))[0]) ?: request()->ip()),
            'user_agent'        => request()->userAgent(),
            'request_url'       => request()->fullUrl(),
            'old_values'        => $oldValues,
            'new_values'        => $newValues,
        ];

        app(\App\Services\AuditService::class)->log($auditData);
    }
}

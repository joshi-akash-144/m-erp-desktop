<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\DairyImportItem;
use App\Models\DriverExpense;
use App\Models\DriverExpenseItem;
use App\Models\JournalVoucher;
use App\Models\Reference;
use App\Models\VehicleExpense;
use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;

class DriverExpenseService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function store(array $data, int $companyId, int $financialYearId): DriverExpense
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {

            if (!empty($data['uuid'])) {
                $existing = DriverExpense::where('uuid', $data['uuid'])
                    ->where('company_id', $companyId)
                    ->first();
                if ($existing) return $existing;
            }

            $expense = DriverExpense::create([
                'uuid'                => $data['uuid'],
                'company_id'          => $companyId,
                'financial_year_id'   => $financialYearId,
                'voucher_date'        => $data['voucher_date'],
                'vehicle_id'          => $data['vehicle_id'],
                'account_id'           => $data['driver_id'],
                'narration'           => $data['narration']            ?? null,
                'start_kms'           => $data['start_kms']            ?? 0,
                'end_kms'             => $data['end_kms']              ?? 0,
                'total_kms'           => $data['total_kms']            ?? 0,
                'start_diesel'        => $data['start_diesel']         ?? 0,
                'end_diesel'          => $data['end_diesel']           ?? 0,
                'diesel_average'      => $data['diesel_average']       ?? 0,
                'idle_days'           => $data['idle_days']            ?? 0,
                'idle_day_wage'       => $data['idle_day_wage']        ?? 0,
                'idle_day_wage_amount' => $data['idle_day_wage_amount'] ?? 0,
                'expense_total'       => $data['expense_total']        ?? 0,
                'created_by'          => current_user_id(),
            ]);

            // 1) Expense-account-wise totals — zero-amount / accountless rows don't count
            $expenseWiseAmount = [];
            foreach ($data['items'] as $item) {
                $accountId = $item['expense_account_id'] ?? null;
                $amount    = (float) ($item['amount'] ?? 0);

                if (!$accountId || $amount <= 0) continue;

                $expenseWiseAmount[$accountId] = ($expenseWiseAmount[$accountId] ?? 0) + $amount;
            }

            if (array_sum($expenseWiseAmount) <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Expense voucher amount must be greater than 0.']);
            }

            // 2) Voucher transaction entries (debit expense accounts = credit driver)
            $serialInfo = $this->voucherService->getNextVoucherNumber(
                VoucherType::JOURNAL,
                $companyId,
                $financialYearId
            );

            $voucherMaster = [
                'uuid'              => $expense->uuid,
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'voucher_date'      => $expense->voucher_date,
                'voucher_type_id'   => VoucherType::JOURNAL,
                'source_id'         => null,
                'source_type'       => SourceType::JOURNAL,
                'reference_number'  => self::generateReferenceNumber('DRIEXP', $serialInfo->serial),
                'voucher_serial'    => $serialInfo->serial,
                'voucher_number'    => $serialInfo->voucher_number,
                'narration'         => $expense->narration,
                'created_by'        => current_user_id(),
            ];

            $voucherLines = $this->prepareVoucherLines($data, $companyId, $expenseWiseAmount);
            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);

            $expense->update([
                'voucher_id' => $voucher->id,
            ]);

            $journal = JournalVoucher::create([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'gst_nature' => 'gst_not_applicable',
                'voucher_id'        => $voucher->id,
                'entry_from'        => JournalVoucher::TRANSPORT_EXP_VOUCHER
            ]);

            $voucher->update([
                'source_id' => $journal->id,
            ]);

            // 3) Store every row as-is — skip only rows where all fields are blank
            $itemData = [];
            foreach ($data['items'] as $item) {
                $isBlank = empty($item['date']) && empty($item['expense_account_id'])
                    && empty($item['from_id']) && empty($item['to_id']) && empty($item['dc_lr'])
                    && empty($item['item_id']) && empty($item['rate']) && empty($item['bags'])
                    && empty($item['weight']) && empty($item['trips']) && empty($item['amount'])
                    && empty($item['remark']);

                if ($isBlank) continue;

                if (empty($item['date']) || empty($item['expense_account_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Bill date and expense account are required for all expense items.']);
                }

                $itemData[] = [
                    'driver_expense_id'    => $expense->id,
                    'billing_date'         => $item['date']    ?? null,
                    'expense_account_id'   => $item['expense_account_id'] ?? null,
                    'from_destination_id'  => $item['from_id'] ?? null,
                    'to_destination_id'    => $item['to_id']   ?? null,
                    'item_id'              => $item['item_id'] ?? null,
                    'dc_lr'                => $item['dc_lr'] ?? null,
                    'rate'                 => $item['rate']    ?? 0,
                    'bags'                 => $item['bags']    ?? 0,
                    'weight'               => $item['weight']  ?? 0,
                    'trips'                => $item['trips']   ?? 0,
                    'amount'               => $item['amount']  ?? 0,
                    'remark'               => $item['remark']  ?? null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];
            }

            if (!empty($itemData)) {
                DriverExpenseItem::insert($itemData);
            }

            // Mark linked dairy import items as used
            $dairyIds = collect($data['items'])
                ->pluck('dairy_import_item_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($dairyIds)) {
                DairyImportItem::whereIn('id', $dairyIds)->update(['is_used' => true]);
            }

            // Vehicle expense entries (one per item line)
            $this->syncVehicleExpenses($data['items'], $voucher->id, $data['vehicle_id'], $companyId, $financialYearId, $expense->voucher_date);

            // ref Entry
            $account = Account::find($data['driver_id']);
            
            if ($account->is_billwise) {
                Reference::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $data['driver_id'],
                    'reference_number' => self::generateReferenceNumber('DRIEXP' ,$voucher->voucher_serial),
                    'reference_date' => $voucher->voucher_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $data['expense_total'],
                    'settled_amount' => 0,
                    'pending_amount' => $data['expense_total'],
                    'voucher_id' => $voucher->id,
                    'source_type'       => SourceType::JOURNAL,
                    'source_id'         => $journal->id,
                    'direction'          => 'credit',
                    'created_by'        => current_user_id(),
                ]);
            }

            $freshVoucher = $voucher->fresh(['details.account']);
            $this->logJournalAudit($freshVoucher, \App\Models\AuditTrail::ACTION_CREATE);

            return $expense;
        });
    }

    private function prepareVoucherLines(array $data, int $companyId, array $expenseWiseAmount): array
    {

        $totalAmount = (float) array_sum($expenseWiseAmount);

        $lines = [];

        $firstDrAccountId = null;
        $isFirstDrAccount = true;
        $lineNo = 1;

        foreach ($expenseWiseAmount as $expenseAccountId => $amount) {
            if ($isFirstDrAccount) {
                $firstDrAccountId = $expenseAccountId;
                $isFirstDrAccount = false;
            }
            $lines[] = [
                'account_id'         => $expenseAccountId,
                'against_account_id' => $data['driver_id'],
                'debit'              => $amount,
                'credit'             => 0,
                'is_party_account'   => false,
                'line_no'            => $lineNo++,
            ];
        }

        // Dr: Customer / party account
        $lines[] = [
            'account_id'         => $data['driver_id'],
            'against_account_id' => $firstDrAccountId,
            'debit'              => 0,
            'credit'             => $totalAmount,
            'is_party_account'   => true,
            'line_no'            => $lineNo,
        ];

        return $lines;
    }

    public function update(array $data, DriverExpense $expense): DriverExpense
    {
        return DB::transaction(function () use ($data, $expense) {

            // Check if payment has been applied — if so, block the edit
            $reference = Reference::where('voucher_id', $expense->voucher_id)->first();
            if ($reference && $reference->settled_amount > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Cannot edit: payment has already been applied against this expense.']);
            }

            $voucher = $expense->voucher;
            $freshVoucherForUpdate = $voucher->fresh(['details.account']);
            $oldValuesForUpdate = $this->buildJournalAuditValues($freshVoucherForUpdate);

            // Update expense header
            $expense->update([
                'voucher_date'         => $data['voucher_date'],
                'vehicle_id'           => $data['vehicle_id'],
                'account_id'           => $data['driver_id'],
                'narration'            => $data['narration']            ?? null,
                'start_kms'            => $data['start_kms']            ?? 0,
                'end_kms'              => $data['end_kms']              ?? 0,
                'total_kms'            => $data['total_kms']            ?? 0,
                'start_diesel'         => $data['start_diesel']         ?? 0,
                'end_diesel'           => $data['end_diesel']           ?? 0,
                'diesel_average'       => $data['diesel_average']       ?? 0,
                'idle_days'            => $data['idle_days']            ?? 0,
                'idle_day_wage'        => $data['idle_day_wage']        ?? 0,
                'idle_day_wage_amount' => $data['idle_day_wage_amount'] ?? 0,
                'expense_total'        => $data['expense_total']        ?? 0,
                'updated_by'           => current_user_id(),
            ]);

            // Delete old items and re-insert
            $expense->items()->delete();

            // 1) Expense-account-wise totals — zero-amount / accountless rows don't count
            $expenseWiseAmount = [];
            foreach ($data['items'] as $item) {
                $accountId = $item['expense_account_id'] ?? null;
                $amount    = (float) ($item['amount'] ?? 0);

                if (!$accountId || $amount <= 0) continue;

                $expenseWiseAmount[$accountId] = ($expenseWiseAmount[$accountId] ?? 0) + $amount;
            }

            if (array_sum($expenseWiseAmount) <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Expense voucher amount must be greater than 0.']);
            }

            // 2) Store every row as-is — skip only rows where all fields are blank
            $itemData = [];
            foreach ($data['items'] as $item) {
                $isBlank = empty($item['date']) && empty($item['expense_account_id'])
                    && empty($item['from_id']) && empty($item['to_id']) && empty($item['dc_lr'])
                    && empty($item['item_id']) && empty($item['rate']) && empty($item['bags'])
                    && empty($item['weight']) && empty($item['trips']) && empty($item['amount'])
                    && empty($item['remark']);

                if ($isBlank) continue;

                if (empty($item['date']) || empty($item['expense_account_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Bill date and expense account are required for all expense items.']);
                }

                $itemData[] = [
                    'driver_expense_id'   => $expense->id,
                    'billing_date'        => $item['date']    ?? null,
                    'expense_account_id'  => $item['expense_account_id'] ?? null,
                    'from_destination_id' => $item['from_id'] ?? null,
                    'to_destination_id'   => $item['to_id']   ?? null,
                    'item_id'             => $item['item_id'] ?? null,
                    'dc_lr'               => $item['dc_lr']   ?? null,
                    'rate'                => $item['rate']     ?? 0,
                    'bags'                => $item['bags']     ?? 0,
                    'weight'              => $item['weight']   ?? 0,
                    'trips'               => $item['trips']    ?? 0,
                    'amount'              => $item['amount']   ?? 0,
                    'remark'              => $item['remark']   ?? null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }

            if (!empty($itemData)) {
                DriverExpenseItem::insert($itemData);
            }

            // Update voucher header
            $voucher = $expense->voucher;
            $voucher->update([
                'voucher_date' => $expense->voucher_date,
                'narration'    => $expense->narration,
                'updated_by'   => current_user_id(),
            ]);

            // Replace voucher transaction lines
            $voucher->details()->delete();
            $voucherLines = $this->prepareVoucherLines($data, $expense->company_id, $expenseWiseAmount);
            foreach ($voucherLines as $line) {
                $voucher->details()->create($line);
            }

            // Update reference amount/pending if it exists
             if ($reference) {
                $newAmount = (float) ($data['expense_total'] ?? 0);

                $reference->reference_date = $voucher->voucher_date;
                $reference->reference_number = $voucher->reference_number;
                $reference->account_id = $data['driver_id'];
                $reference->amount = $newAmount;
                $reference->pending_amount = $newAmount - (float) $reference->settled_amount;
                $reference->is_closed = $reference->pending_amount == 0;
                $reference->closed_at = $reference->is_closed ? now() : null;
                $reference->updated_by = current_user_id();
                $reference->updated_at = now();
                $reference->save();
            } else {
               // ref Entry
            $account = Account::find($data['driver_id']);
            
            if ($account->is_billwise) {
                Reference::create([
                    'company_id'        => $expense->company_id,
                    'financial_year_id' => $expense->financial_year_id,
                    'account_id' => $data['driver_id'],
                    'reference_number' => self::generateReferenceNumber('DRIEXP' ,$voucher->voucher_serial),
                    'reference_date' => $voucher->voucher_date,
                    'file_number' => null,
                    'reference_type' => Reference::NewReference,
                    'amount' => $data['expense_total'],
                    'settled_amount' => 0,
                    'pending_amount' => $data['expense_total'],
                    'voucher_id' => $voucher->id,
                    'source_type'       => SourceType::JOURNAL,
                    'source_id'         => $voucher->source_id,
                    'direction'          => 'credit',
                    'created_by'        => current_user_id(),
                ]);
            }
            }

            // Re-sync vehicle expense entries
            VehicleExpense::where('voucher_id', $voucher->id)->delete();
            $this->syncVehicleExpenses($data['items'], $voucher->id, $data['vehicle_id'], $expense->company_id, $expense->financial_year_id, $expense->voucher_date);

            $freshVoucherUpdated = $voucher->fresh(['details.account']);
            $this->logJournalAudit($freshVoucherUpdated, \App\Models\AuditTrail::ACTION_UPDATE, $oldValuesForUpdate);

            return $expense->fresh(['voucher']);
        });
    }

    public function getList(array $filters): array
    {
        $query = DriverExpense::with(['vehicle:id,name', 'driver.account:id,name'])
            ->where('company_id', $filters['company_id'])
            ->where('financial_year_id', $filters['financial_year_id']);

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (!empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('voucher_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('voucher_date', '<=', $filters['to_date']);
        }

        $page     = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['size'] ?? 50);
        $total    = $query->count();

        $records = $query->orderByDesc('voucher_no')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return [
            'data'       => $records,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
            'total_pages' => (int) ceil($total / $pageSize),
        ];
    }

    public function getRegisterList(array $filters): array
    {
        $query = DriverExpense::with([
            'vehicle:id,name',
            'account:id,name',
            'voucher:id,voucher_serial',
            'creator:id,name',
            'updater:id,name',
            'items.expenseAccount:id,name',
            'items.fromDestination:id,name',
            'items.toDestination:id,name',
            'items.item:id,name',
        ])
            ->where('driver_expenses.company_id', $filters['company_id'])
            ->where('driver_expenses.financial_year_id', $filters['financial_year_id']);

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('voucher_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('voucher_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['voucher_serial'])) {
            $query->whereHas('voucher', function ($q) use ($filters) {
                $q->where('voucher_serial', $filters['voucher_serial']);
            });
        }

        $page     = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['size'] ?? 50);
        $total    = $query->count();

        $records = $query->orderByDesc('driver_expenses.id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        // Determine which vouchers have been (partially) settled
        $voucherIds = $records->pluck('voucher_id')->filter()->unique()->values()->all();
        $lockedVoucherIds = [];
        if (!empty($voucherIds)) {
            $lockedVoucherIds = Reference::whereIn('voucher_id', $voucherIds)
                ->where('settled_amount', '>', 0)
                ->pluck('voucher_id')
                ->flip()
                ->all();
        }

        $rows = [];
        $offset   = ($page - 1) * $pageSize;

        foreach ($records as $idx => $expense) {
            $vSerial   = $expense->voucher?->voucher_serial ?? '-';
            
            // Extract unique expense account names
            $expenseNames = $expense->items->map(fn($item) => $item->expenseAccount?->name)
                                ->filter()
                                ->unique()
                                ->implode(', ');

            $rows[] = [
                'id'             => $expense->id,
                'is_locked'      => isset($lockedVoucherIds[$expense->voucher_id]),
                'row_num'        => $offset + $idx + 1,
                'voucher_serial' => $vSerial,
                'voucher_date'   => $expense->voucher_date?->format('d/m/Y') ?? '-',
                'driver_name'    => $expense->account?->name ?? '-',
                'vehicle_name'   => $expense->vehicle?->name ?? '-',
                'expense_name'   => $expenseNames ?: '-',
                'created_by'     => $expense->creator?->name ?? '-',
                'updated_by'     => $expense->updater?->name ?? '-',
                'amount'         => (float) $expense->expense_total,
            ];
        }

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'last_page' => (int) ceil($total / max($pageSize, 1)),
        ];
    }

    public function getById(int $id, int $companyId): ?DriverExpense
    {
        return DriverExpense::with([
            'vehicle:id,name',
            'driver.account:id,name',
            'expenseAccount:id,name',
            'items.fromDestination:id,name',
            'items.toDestination:id,name',
            'items.item:id,name',
        ])
            ->where('company_id', $companyId)
            ->find($id);
    }
    private function generateReferenceNumber(string $prefix, int $number, int $length = 6): string
    {
        return $prefix . '-' . str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Aggregate item rows by expense_account_id (expense-wise total) and
     * insert one VehicleExpense record per unique expense account.
     * This mirrors the expenseWiseAmount logic used when building voucher lines.
     */
    private function syncVehicleExpenses(array $items, int $voucherId, int $vehicleId, int $companyId, int $financialYearId, $voucherDate): void
    {
        // Build expense-wise totals (same logic as expenseWiseAmount in store/update)
        $expenseWiseAmount = [];
        foreach ($items as $item) {
            $accountId = $item['expense_account_id'] ?? null;
            $amount    = (float) ($item['amount'] ?? 0);

            if (!$accountId || $amount <= 0) continue;

            $expenseWiseAmount[$accountId] = ($expenseWiseAmount[$accountId] ?? 0) + $amount;
        }

        // Insert one VehicleExpense per unique expense account with consolidated total
        foreach ($expenseWiseAmount as $expenseAccountId => $total) {
            VehicleExpense::create([
                'voucher_id'         => $voucherId,
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'vehicle_id'         => $vehicleId,
                'expense_account_id' => $expenseAccountId,
                'amount'             => $total,
                'bill_date'          => null,         // consolidated — no single bill date
                'voucher_date'       => $voucherDate,
            ]);
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

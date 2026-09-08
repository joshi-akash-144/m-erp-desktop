<?php

namespace App\Services;

use App\Enums\SourceType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\JournalVoucher;
use App\Models\Reference;
use App\Models\Salary;
use App\Models\SalaryDetail;
use App\Models\VehicleExpense;
use App\Models\VoucherType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryModuleService
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function store(array $data, int $companyId, int $financialYearId): Salary
    {
        return DB::transaction(function () use ($data, $companyId, $financialYearId) {

            if (!empty($data['uuid'])) {
                $existing = Salary::where('uuid', $data['uuid'])
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

            $expense = Salary::create([
                'uuid'               => $data['uuid'],
                'company_id'         => $companyId,
                'financial_year_id'  => $financialYearId,
                'voucher_date'       => $data['voucher_date'],
                'day_for'            => $data['day_for'] ?? null,
                'month'              => $data['month'] ?? null,
                'expense_account_id' => $data['expense_account_id'],
                'total_amount'       => $totalAmount,
                'narration'          => $data['narration'] ?? null,
                'created_by'         => current_user_id(),
            ]);


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
                'reference_number'  => 'SAL-' . (!empty($data['month']) ? date('M', strtotime($data['month'])) : ''),
                'voucher_serial'    => $serialInfo->serial,
                'voucher_number'    => $serialInfo->voucher_number,
                'narration'         => $expense->narration,
                'created_by'        => current_user_id(),
            ];


            $voucherLines = [];
            $voucherLines[] = [
                'account_id'         => $data['expense_account_id'],
                'against_account_id' => $rows[0]['account_id'] ?? null,
                'debit'              => $totalAmount,
                'credit'             => 0,
                'is_party_account'   => false,
                'line_number'        => 1,
            ];

            $lineNumber = 2;
            $refRow = [];
            foreach ($rows as $row) {
                $voucherLines[] = [
                    'account_id'         => $row['account_id'],
                    'against_account_id' => $data['expense_account_id'],
                    'debit'              => 0,
                    'credit'             => $row['amount'] ?? 0,
                    'is_party_account'   => true,
                    'line_number'        => $lineNumber++,
                ];
            }


            $voucher = $this->voucherService->createVoucher($voucherMaster, $voucherLines);



            $expense->update([
                'voucher_id' => $voucher->id,
            ]);

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

            $refRow = [];
            // Store item rows
            $itemData = [];
            foreach ($rows as $row) {
                $itemData[] = [
                    'salary_id'  => $expense->id,
                    'account_id' => $row['account_id'] ?? null,
                    'ref_no'     => $row['bill_no'] ?? null,
                    'vehicle_id' => $row['vehicle_id'],
                    'amount'     => $row['amount'] ?? 0,
                    'remark'     => $row['remark'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $refRow[] = [
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'account_id' => $row['account_id'],
                    'reference_number' => $row['bill_no'],
                    'reference_date' => $expense->voucher_date,
                    'voucher_id' => $voucher->id,
                    'file_number' => '',
                    'reference_type' =>  Reference::NewReference,
                    'amount' => $row['amount'],
                    'settled_amount' => 0,
                    'pending_amount' => $row['amount'],
                    'source_type' => SourceType::JOURNAL,
                    'source_id' => $journal->id,
                    'created_by' => current_user_id(),
                    'direction' => 'credit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            SalaryDetail::insert($itemData);
            Reference::insert($refRow);

            // Sync vehicle-wise expense — one row per vehicle with its total amount

            $vehicleWiseAmount = [];
            foreach ($rows as $row) {
                $vehicleId = $row['vehicle_id'];
                $vehicleWiseAmount[$vehicleId] = ($vehicleWiseAmount[$vehicleId] ?? 0) + (float) ($row['amount'] ?? 0);
            }


            foreach ($vehicleWiseAmount as $vehicleId => $amount) {
                VehicleExpense::create([
                    'voucher_id'         => $voucher->id,
                    'company_id'         => $companyId,
                    'financial_year_id'  => $financialYearId,
                    'vehicle_id'         => $vehicleId,
                    'expense_account_id' => $data['expense_account_id'],
                    'amount'             => $amount,
                    'bill_date'          => null,
                    'voucher_date'       => $expense->voucher_date,
                ]);
            }

            $freshVoucher = $voucher->fresh(['details.account']);
            $this->logJournalAudit($freshVoucher, \App\Models\AuditTrail::ACTION_CREATE);

            return $expense->fresh(['voucher', 'items']);
        });
    }

    public function update(array $data, Salary $expense): Salary
    {
        return DB::transaction(function () use ($data, $expense) {

            // Check if any reference is settled
            $hasSettledReference = Reference::where('source_type', SourceType::JOURNAL)
                ->where('source_id', $expense->voucher->source_id)
                ->where('settled_amount', '>', 0)
                ->exists();

            if ($hasSettledReference) {
                throw new \Exception('Voucher cannot be updated because one or more payments have already been settled against it. Please clear the payment first before updating.');
            }

            $voucher = $expense->voucher;
            $freshVoucherForUpdate = $voucher->fresh(['details.account']);
            $oldValuesForUpdate = $this->buildJournalAuditValues($freshVoucherForUpdate);

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

            $expense->update([
                'voucher_date'       => $data['voucher_date'],
                'day_for'            => $data['day_for'] ?? null,
                'expense_account_id' => $data['expense_account_id'],
                'total_amount'       => $totalAmount,
                'narration'          => $data['narration'] ?? null,
                'updated_by'         => current_user_id(),
                'updated_at'         => now(),
                'month'              => $data['month'] ?? null,
            ]);

            // Replace item rows
            $expense->details()->delete();
            $itemData = [];
            $refRow = [];
            foreach ($rows as $row) {
                $itemData[] = [
                    'salary_id'  => $expense->id,
                    'account_id' => $row['account_id'] ?? null,
                    'ref_no'     => $row['bill_no'] ?? null,
                    'vehicle_id' => $row['vehicle_id'],
                    'amount'     => $row['amount'] ?? 0,
                    'remark'     => $row['remark'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $refRow[] = [
                    'company_id' => $expense->company_id,
                    'financial_year_id' => $expense->financial_year_id,
                    'account_id' => $row['account_id'],
                    'reference_number' => $row['bill_no'],
                    'reference_date' => $expense->voucher_date,
                    'file_number' => '',
                    'voucher_id' => $expense->voucher_id,
                    'reference_type' =>  Reference::NewReference,
                    'amount' => $row['amount'],
                    'settled_amount' => 0,
                    'pending_amount' => $row['amount'],
                    'source_type' => SourceType::JOURNAL,
                    'source_id' => $expense->voucher->source_id,
                    'created_by' => current_user_id(),
                    'direction' => 'credit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            SalaryDetail::insert($itemData);
            Reference::where('source_type', SourceType::JOURNAL)
                ->where('source_id', $expense->voucher->source_id)
                ->delete();
            if (!empty($refRow)) {
                Reference::insert($refRow);
            }

            // Update voucher header + replace its two Dr/Cr lines
            $voucher = $expense->voucher;
            $voucher->update([
                'voucher_date' => $expense->voucher_date,
                'narration'    => $expense->narration,
                'updated_by'   => current_user_id(),
            ]);

            $voucher->details()->delete();
            $voucher->details()->create([
                'account_id'         => $data['expense_account_id'],
                'against_account_id' => $rows[0]['account_id'] ?? null,
                'debit'              => $totalAmount,
                'credit'             => 0,
                'is_party_account'   => false,
                'line_number'        => 1,
            ]);

            $lineNumber = 2;
            foreach ($rows as $row) {
                $voucher->details()->create([
                    'account_id'         => $row['account_id'],
                    'against_account_id' => $data['expense_account_id'],
                    'debit'              => 0,
                    'credit'             => $row['amount'] ?? 0,
                    'is_party_account'   => true,
                    'line_number'        => $lineNumber++,
                ]);
            }

            // Re-sync vehicle-wise expense
            VehicleExpense::where('voucher_id', $voucher->id)->delete();
            $vehicleWiseAmount = [];
            foreach ($rows as $row) {
                $vehicleId = $row['vehicle_id'];
                $vehicleWiseAmount[$vehicleId] = ($vehicleWiseAmount[$vehicleId] ?? 0) + (float) ($row['amount'] ?? 0);
            }

            foreach ($vehicleWiseAmount as $vehicleId => $amount) {
                VehicleExpense::create([
                    'voucher_id'         => $voucher->id,
                    'company_id'         => $expense->company_id,
                    'financial_year_id'  => $expense->financial_year_id,
                    'vehicle_id'         => $vehicleId,
                    'expense_account_id' => $data['expense_account_id'],
                    'amount'             => $amount,
                    'bill_date'          => null,
                    'voucher_date'       => $expense->voucher_date,
                ]);
            }

            $freshVoucherUpdated = $voucher->fresh(['details.account']);
            $this->logJournalAudit($freshVoucherUpdated, \App\Models\AuditTrail::ACTION_UPDATE, $oldValuesForUpdate);

            return $expense->fresh(['voucher', 'items']);
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
        $query = Salary::with([
            'account:id,name',
            'expenseAccount:id,name',
            'voucher:id,voucher_serial',
            'creator:id,name',
            'updater:id,name',
            'details.vehicle:id,name',
            'details.account:id,name',
        ])
            ->where('salaries.company_id', $filters['company_id'])
            ->where('salaries.financial_year_id', $filters['financial_year_id']);

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
            $query->whereHas('voucher', function ($q) use ($filters) {
                $q->where('voucher_serial', $filters['voucher_serial']);
            });
        }

        $page     = (int) ($filters['page'] ?? 1);
        $pageSize = (int) ($filters['size'] ?? 50);
        $total    = $query->count();

        $records = $query->orderByDesc('salaries.id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        // Build flat rows: one per voucher
        $flatRows = [];
        $offset   = ($page - 1) * $pageSize;

        foreach ($records as $idx => $expense) {
            $vSerial   = $expense->voucher?->voucher_serial ?? '-';

            // One row per voucher
            $flatRows[] = [
                'id'                  => $expense->id,
                'row_num'             => $offset + $idx + 1,
                'voucher_serial'      => $vSerial,
                'voucher_date'        => $expense->voucher_date?->format('d/m/Y') ?? '-',
                'expense_account_name' => $expense->expenseAccount?->name ?? '-',
                'created_by'          => $expense->creator?->name ?? '-',
                'updated_by'          => $expense->updater?->name ?? '-',
                'month'               => $expense->month ?? '-',
                'narration'           => $expense->narration ?? '-',
                'amount'              => (float) $expense->total_amount,
                'is_locked'           => false,
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
        return Salary::where('salaries.company_id', $companyId)
            ->where('salaries.financial_year_id', $financialYearId)
            ->whereNotNull('salaries.voucher_id')
            ->join('vouchers', 'vouchers.id', '=', 'salaries.voucher_id')
            ->orderByDesc(DB::raw('CAST(vouchers.voucher_serial AS UNSIGNED)'))
            ->pluck('vouchers.voucher_serial');
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

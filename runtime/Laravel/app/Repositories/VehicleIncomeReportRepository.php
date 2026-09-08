<?php

namespace App\Repositories;

use App\Models\VehicleIncome;
use App\Models\Voucher;
use Illuminate\Support\Collection;

class VehicleIncomeReportRepository
{
    public function getVehicleIncomes(int $companyId, int $financialYearId, ?string $fromDate, ?string $toDate, ?int $vehicleId, ?int $accountId, ?string $voucherNo, string $type = 'all'): Collection
    {
        return VehicleIncome::query()
            ->with([
                'vehicle:id,name',
                'account:id,name',
                'voucher:id,voucher_serial,reference_number',
                'freight:id,prefix,reference_number,entry_from,account_id',
                'freight.account:id,name'
            ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->when($fromDate, fn ($q) => $q->whereDate('voucher_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('voucher_date', '<=', $toDate))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($accountId, fn ($q) => $q->whereHas('freight', fn($fq) => $fq->where('account_id', $accountId)))
            ->when($voucherNo, fn ($q) => $q->whereHas('voucher', fn($vq) => $vq->where('voucher_serial', $voucherNo)))
            ->when($type !== 'all', function ($q) use ($type) {
                $entryFrom = match ($type) {
                    'freight' => 'voucher',
                    'freight_invoice2' => 'invoice2',
                    default => 'invoice',
                };
                $q->whereHas('freight', fn ($fq) => $fq->where('entry_from', $entryFrom));
            })
            ->get();
    }

    public function getVoucherNo(int $companyId): Collection
    {
        return Voucher::where('company_id', $companyId)
            ->where(function ($q) use ($companyId) {
                $q->whereIn('id', function ($sub) use ($companyId) {
                    $sub->select('voucher_id')->from('vehicle_incomes')->where('company_id', $companyId);
                })->orWhereIn('id', function ($sub) use ($companyId) {
                    $sub->select('voucher_id')->from('freights')->where('company_id', $companyId)->where('entry_from', 'invoice');
                });
            })
            ->whereNotNull('voucher_serial')
            ->where('voucher_serial', '!=', '')
            ->orderBy('voucher_serial', 'desc')
            ->get(['voucher_serial']);
    }
}

<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Repositories\VehicleIncomeReportRepository;

class VehicleIncomeReportService
{
    public function __construct(protected VehicleIncomeReportRepository $repository) {
    }

    public function getVoucherNumbers(int $companyId): Collection
    {
        return $this->repository->getVoucherNo($companyId);
    }

    public function getReport(int $companyId, int $financialYearId, array $filters = []): array
    {
        $fromDate   = $filters['from_date']  ?? null;
        $toDate     = $filters['to_date']    ?? null;
        $vehicleId  = $filters['vehicle_id'] ?? null;
        $accountId  = $filters['account_id'] ?? null;
        $voucherNo  = $filters['voucher_no'] ?? null;
        $type       = $filters['type']       ?? 'all';

        $rows = $this->repository->getVehicleIncomes($companyId, $financialYearId, $fromDate, $toDate, $vehicleId, $accountId, $voucherNo, $type)
            ->toBase()
            ->map(function ($i) {                
                $isInvoice = $i->freight?->entry_from === 'invoice';                
                $rowType = $isInvoice ? 'Freight Invoice' : ($i->freight?->entry_from === 'invoice2' ? 'Freight Invoice 2' : 'Freight');
                $refNo = $i->voucher?->reference_number ?? (($i->freight?->prefix ?? '') . ($i->freight?->reference_number ?? ''));
                
                return $this->formatRow(
                    date: $i->voucher_date,
                    voucherNo: $i->voucher?->voucher_serial,
                    refNo: $refNo,
                    partyName: $i->freight?->account?->name,
                    vehicleName: $i->vehicle?->name,
                    type: $rowType,
                    amount: (float) $i->amount,
                );
            });       
        
        $data = $rows->sortByDesc('sort_date')
            ->values()
            ->map(fn ($row) => Arr::except($row, ['sort_date']))
            ->all();
        
        return ['data' => $data];
    }

private function formatRow(
    ?string $date,
    ?string $voucherNo,
    ?string $refNo,
    ?string $partyName,
    ?string $vehicleName,
    string $type,
    float $amount,
): array {
    return [
        'sort_date'    => $date,
        'date'         => $date ? Carbon::parse($date)->format('d-m-Y') : '-',
        'voucher_no'   => $voucherNo ?? '-',
        'ref_no'       => $refNo ?? '-',
        'party_name'   => $partyName ?? '-',
        'vehicle_name' => $vehicleName ?? '-',
        'type'         => $type,
        'amount'       => $amount,
    ];
}
}

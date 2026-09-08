<?php

namespace App\Services;

use App\Repositories\ExpenseRegisterRepository;
use Carbon\Carbon;
use App\Models\Vehicle;
use App\Models\Account;
use App\Models\Voucher;

class ExpenseRegisterService
{
    protected ExpenseRegisterRepository $repository;

    public function __construct(ExpenseRegisterRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getRegisterList(array $filters): array
    {
        $result = $this->repository->list($filters);
        
        $formattedData = collect($result['data'])->map(function ($row) {
            return [
                'id'                => $row->id,
                'voucher_date'      => $row->voucher_date     ? Carbon::parse($row->voucher_date)->format('d-m-Y') : '-',               
                'voucher_serial'    => $row->voucher->voucher_serial ?? ($row->voucher->voucher_number ?? '-'),               
                'party_name'        => $row->voucherTransaction->account->name ?? '-',
                'reference_number'  => $row->voucher->reference_number ?? '-',
                'account_name'      => $row->vehicle->name ?? '-',
                'expense_account'   => $row->expenseAccount->name ?? '-',
                'amount'            => (float) $row->amount,
            ];
        })->values()->all();

        $grandTotal = $this->repository->grandTotal($filters);
        
        return [
            'data'            => $formattedData,
            'total'           => $result['total'],
            'last_page'       => $result['last_page'],
            'current_page'    => $result['page'],
            'grand_total'     => number_format($grandTotal, 2),
            'grand_total_raw' => $grandTotal,
        ];
    }
    public function getFilterData(int $companyId): array
    {
        $vehicles = Vehicle::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereHas('group', function ($q) {
                $q->where('code', '420'); 
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $voucherNumbers = Voucher::where('company_id', $companyId)
            ->whereIn('id', function($q) use ($companyId) {
                $q->select('voucher_id')
                  ->from('vehicle_expenses')
                  ->where('company_id', $companyId);
            })
            ->whereNotNull('voucher_serial')
            ->where('voucher_serial', '!=', '')
            ->orderBy('voucher_serial','desc')
            ->get(['voucher_serial', 'reference_number']);
        
        $accounts = Account::where('company_id', $companyId)
            ->whereIn('id', function ($query) use ($companyId) {
                $query->select('account_id')
                    ->from('voucher_transactions')
                    ->where('company_id', $companyId)
                    ->whereIn('voucher_id', function ($subQuery) use ($companyId) {
                        $subQuery->select('voucher_id')
                            ->from('vehicle_expenses')
                            ->where('company_id', $companyId);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $referenceNumbers = Voucher::where('company_id', $companyId)
            ->whereIn('id', function($q) use ($companyId) {
                $q->select('voucher_id')
                  ->from('vehicle_expenses')
                  ->where('company_id', $companyId);
            })
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->orderBy('reference_number','asc')
            ->get(['reference_number']);

        return compact('vehicles', 'expenseAccounts', 'voucherNumbers', 'accounts','referenceNumbers');
    }
}

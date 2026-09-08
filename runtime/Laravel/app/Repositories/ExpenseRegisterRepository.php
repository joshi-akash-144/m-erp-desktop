<?php

namespace App\Repositories;

use App\Models\VehicleExpense;
use App\Models\Voucher;

class ExpenseRegisterRepository extends BaseRepository
{
    public function __construct(VehicleExpense $model)
    {
        parent::__construct($model);
    }
    
    public function getExpenseRegisterData(array $filters)
    {
        return $this->model->query()
            ->select([
                'id', 
                'company_id', 
                'financial_year_id', 
                'voucher_date',                 
                'vehicle_id', 
                'expense_account_id', 
                'voucher_id', 
                'amount'
            ])
            ->with([
                'vehicle:id,name',                           
                'expenseAccount:id,name',
                'voucher:id,voucher_number,voucher_serial,reference_number',
                'voucherTransaction.account:id,name'
            ])
            ->where('company_id',       $filters['company_id'])
            ->where('financial_year_id', $filters['financial_year_id'])
            ->when(!empty($filters['start_date']),        fn($q) => $q->whereDate('voucher_date',       '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']),          fn($q) => $q->whereDate('voucher_date',       '<=', $filters['end_date']))
            ->when(!empty($filters['vehicle_id']),        fn($q) => $q->where('vehicle_id',        $filters['vehicle_id']))
            ->when(!empty($filters['expense_account_id']),fn($q) => $q->where('expense_account_id', $filters['expense_account_id']))
            ->when(!empty($filters['voucher_serial']), function($q) use ($filters) {
                $q->whereHas('voucher', function($vq) use ($filters) {
                    $vq->where(function($sub) use ($filters) {
                        $sub->where('voucher_number', $filters['voucher_serial'])
                            ->orWhere('voucher_serial', $filters['voucher_serial']);
                    });
                });
            })
            ->when(!empty($filters['party_id']), function($q) use ($filters) {
                $q->whereHas('voucherTransaction', function($vq) use ($filters) {
                    $vq->where('account_id', $filters['party_id']);
                });
            })
            ->when(!empty($filters['reference_number']), function($q) use ($filters) {
                $q->whereHas('voucher', function($vq) use ($filters) {
                    $vq->where('reference_number', $filters['reference_number']);
                });
            })
            ->orderByDesc('voucher_date');
    }
    
    public function list(array $filters)
    {
        $sizeInput = $filters['size'] ?? 50;
        if ($sizeInput === 'true' || $sizeInput === true || $sizeInput === 'all') {
            $query = $this->getExpenseRegisterData($filters);
            $total = $query->count();
            return [
                'data'     => $query->get(),
                'total'    => $total,
                'last_page'=> 1,
                'page'     => 1,
            ];
        }

        $size = max(1, (int) $sizeInput);
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = $this->getExpenseRegisterData($filters);
        
        $total    = $query->count();
        $lastPage = (int) ceil($total / $size);
        $data    = $query->skip(($page - 1) * $size)->take($size)->get();

        return [
            'data'    => $data,
            'total'    => $total,
            'last_page'=> $lastPage,
            'page'     => $page,
        ];
    }

    
    public function grandTotal(array $filters): float
    {
        return (float) $this->getExpenseRegisterData($filters)->sum('amount');
    }        
}

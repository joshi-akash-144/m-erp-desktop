<?php

namespace App\Repositories;

use App\Models\ManualCheque;

class ManualChequeRepository extends BaseRepository
{
    public function __construct(ManualCheque $model)
    {
        parent::__construct($model);
    }

    /**
     * Get paginated list of manual cheques based on filters
     */
    public function getManualChequeList(array $filters, int $page = 1, int $size = 15): array
    {
        $query = $this->model->with(['account', 'chequeFormat'])
            // ->where('company_id', $companyId)
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->when(!empty($filters['account_id']), function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });

        $total = $query->count();
        $data = $query->orderByDesc('id')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();
      

        return [
            'data'      => $data,
            'total'     => $total,
            'last_page' => ceil($total / $size)
        ];
    }
}

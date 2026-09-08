<?php

namespace App\Repositories;

use App\Models\SaleType;

class SaleTypeRepository extends BaseRepository
{
    public function __construct(SaleType $saleType)
    {
        parent::__construct($saleType);
    }

    public function hasAccounts(SaleType $purchaseType): bool
    {
        return $purchaseType->accounts()->exists();
    }

    public function nextCode(int $companyId): int
    {
        $prefix = 1000;
        $maxCode = $this->model->where('company_id', $companyId)->max('code');
        return $maxCode ? $maxCode + 1 : $prefix + 1;
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}

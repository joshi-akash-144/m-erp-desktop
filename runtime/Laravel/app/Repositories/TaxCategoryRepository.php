<?php

namespace App\Repositories;

use App\Models\TaxCategory;

class TaxCategoryRepository extends BaseRepository
{
    public function __construct(TaxCategory $taxCategory)
    {
        parent::__construct($taxCategory);
    }

    /**
     * Determine if the given tax category is linked to any accounts.
     *
     * This is used to prevent deletion of tax categories that are
     * already in use by one or more accounts.
     *
     * @param  TaxCategory  $taxCategory
     * @return bool  True if at least one account exists, false otherwise.
     */
    public function hasAccounts(TaxCategory $taxCategory): bool
    {
        return $taxCategory->accounts()->exists();
    }

    /**
     * Generate the next sequential code for a record in the given company.
     *
     * This method retrieves the current maximum `code` for the specified
     * company and returns the next number in sequence. If no record exists,
     * it starts with a default prefix value.
     *
     * @param  int  $companyId  The ID of the company to generate the code for.
     * @return int  The next sequential code.
     */
    public function nextCode(int $companyId): int
    {
        $prefix = 1000;

        $maxCode = $this->model->where('company_id', $companyId)->max('code');

        return $maxCode ? $maxCode + 1 : $prefix + 1;
    }
}

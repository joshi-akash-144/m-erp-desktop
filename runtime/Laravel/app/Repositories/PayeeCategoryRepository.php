<?php

namespace App\Repositories;

use App\Models\PayeeCategory;


class PayeeCategoryRepository extends BaseRepository
{
    public function __construct(PayeeCategory $payeeCategory)
    {
        parent::__construct($payeeCategory);
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
    

}

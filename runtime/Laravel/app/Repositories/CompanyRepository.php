<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CompanyRepository extends BaseRepository
{
    public function __construct(Company $company)
    {
        parent::__construct($company);
    }

    public function nextCode(): string
    {
        $prefix = 'COMP';
        $lastCompany = $this->model->orderBy('code', 'desc')->first();

        if ($lastCompany) {
            $lastNumber = (int) str_replace($prefix, '', $lastCompany->code);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            return $prefix . $nextNumber;
        }

        return $prefix . '0101';
    }
}

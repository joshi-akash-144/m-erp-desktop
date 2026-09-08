<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepository;

use Illuminate\Support\Facades\DB;

class CompanyService
{
    protected CompanyRepository $repository;
    public function __construct(
        CompanyRepository $repository,

    ) {
        $this->repository = $repository;
    }

    public function current(?int $companyId = null): ?Company
    {
        if (!$companyId) {
            $companyId = session('company_id');
        }
        return Company::find($companyId);
    }

    public function createCompany(array $data): Company
    {
        return DB::transaction(function () use ($data) {

            $data['code'] = $this->repository->nextCode();

            $company = $this->repository->create($data);

            app(CompanySetupService::class)->setupFor($company, $data);
            
            return $company;
        });
    }

   
}

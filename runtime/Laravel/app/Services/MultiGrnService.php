<?php

namespace App\Services;

use App\Repositories\MultiGrnRepository;

class MultiGrnService
{
    protected VoucherService $voucherService;
    protected MultiGrnRepository $multiGrnRepository;
    protected LookupService $lookupService;

    public function __construct(VoucherService $voucherService, MultiGrnRepository $multiGrnRepository, LookupService $lookupService)
    {
        $this->voucherService = $voucherService;
        $this->multiGrnRepository = $multiGrnRepository;
        $this->lookupService = $lookupService;
    }
}

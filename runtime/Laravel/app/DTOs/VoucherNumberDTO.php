<?php

namespace App\DTOs;

class VoucherNumberDTO
{
    public function __construct(
        public int $serial,
        public string $voucher_number
    ) {}
}
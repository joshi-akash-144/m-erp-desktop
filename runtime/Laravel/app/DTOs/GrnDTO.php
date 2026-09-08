<?php

namespace App\DTOs;

class GrnDTO
{
    public function __construct(
        public int $grn_serial,
        public string $grn_number
    ) {}
}
<?php

namespace App\DTOs;

class DeliveryChallanDTO
{
    public function __construct(
        public int $challan_serial,
        public string $challan_number
    ) {}
}
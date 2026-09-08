<?php

namespace App\DTOs;

class PurchaseOrderNumberDTO
{
    public function __construct(
        public int $order_serial,
        public string $order_number
    ) {}
}
<?php

namespace App\DTOs;

class SalesOrderNumberDTO
{
    public function __construct(
        public int $order_serial,
        public string $order_number
    ) {}
}

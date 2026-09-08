<?php

namespace App\Enums;

enum GstItcEligibility: int
{
    case INPUTS          = 1;
    case CAPITAL_GOODS   = 2;
    case INPUT_SERVICES  = 3;
    case INELIGIBLE      = 4;
}

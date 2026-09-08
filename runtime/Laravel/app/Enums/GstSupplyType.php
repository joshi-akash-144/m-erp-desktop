<?php

namespace App\Enums;

enum GstSupplyType: int
{
    case B2B           = 1;
    case B2CL          = 2;
    case B2CS          = 3;
    case EXPORT        = 4;
    case SEZ           = 5;
    case DEEMED_EXPORT    = 6;
    case IMPORT_GOODS     = 7;
    case IMPORT_SERVICES  = 8;
}

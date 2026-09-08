<?php

namespace App\Enums;

enum GstItcType: int
{
    case ITC_AVAILABLE  = 1;
    case ITC_REVERSED   = 2;
    case INELIGIBLE_ITC = 3;
}

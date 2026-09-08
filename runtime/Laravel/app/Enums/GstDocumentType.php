<?php

namespace App\Enums;

enum GstDocumentType: int
{
    case INV = 1; // Tax Invoice
    case CRN = 2; // Credit Note
    case DBN = 3; // Debit Note
}

<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Cancelled = 'cancelled';
    case Amended = 'amended';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
}

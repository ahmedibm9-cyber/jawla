<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Submitted = 'submitted';
    case Cancelled = 'cancelled';
    case Amended = 'amended';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Credited = 'credited';
    case Voided = 'voided';
}

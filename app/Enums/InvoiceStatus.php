<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Cancelled = 'cancelled';
    case Amended = 'amended';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Submitted, self::Cancelled]),
            self::Submitted => in_array($next, [self::Cancelled, self::Amended]),
            self::Cancelled => $next === self::Amended,
            self::Amended => in_array($next, [self::Submitted, self::Cancelled]),
        };
    }
}

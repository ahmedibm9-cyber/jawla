<?php

namespace App\Enums;

enum VanTransferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Shipped = 'shipped';
    case Received = 'received';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Accepted, self::Rejected, self::Cancelled]),
            self::Accepted => in_array($next, [self::Shipped, self::Cancelled]),
            self::Shipped => in_array($next, [self::Received, self::Cancelled]),
            self::Received, self::Rejected, self::Cancelled => false,
        };
    }
}
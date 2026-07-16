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
}

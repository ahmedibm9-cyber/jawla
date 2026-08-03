<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Draft = 'draft';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';
}

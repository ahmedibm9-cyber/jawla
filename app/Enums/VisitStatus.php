<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
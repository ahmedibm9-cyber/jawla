<?php

namespace App\Enums;

enum VisitPurpose: string
{
    case Sale = 'sale';
    case Collection = 'collection';
    case Return = 'return';
    case Survey = 'survey';
    case Other = 'other';
    case CustomVisit = 'custom_visit';
}

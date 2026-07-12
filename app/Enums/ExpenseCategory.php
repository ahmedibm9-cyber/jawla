<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Fuel = 'fuel';
    case Maintenance = 'maintenance';
    case Food = 'food';
    case Other = 'other';
}
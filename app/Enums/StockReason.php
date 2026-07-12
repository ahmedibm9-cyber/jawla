<?php

namespace App\Enums;

enum StockReason: string
{
    case Sale = 'sale';
    case Return = 'return';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case Initial = 'initial';
    case Purchase = 'purchase';
    case LandedCost = 'landed_cost';
    case TransitIn = 'transit_in';
    case TransitOut = 'transit_out';
    case InterCompany = 'inter_company';
}
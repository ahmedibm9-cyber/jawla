<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case BankTransfer = 'bank_transfer';
    case Lc = 'lc';
    case CreditCard = 'credit_card';
    case Other = 'other';
}
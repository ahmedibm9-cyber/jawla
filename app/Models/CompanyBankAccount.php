<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBankAccount extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'bank_name', 'account_name', 'account_number',
        'iban', 'swift', 'currency', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];
}

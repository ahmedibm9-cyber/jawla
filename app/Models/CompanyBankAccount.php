<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CompanyBankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $bank_name
 * @property string $account_name
 * @property string $account_number
 * @property string|null $iban
 * @property string|null $swift
 * @property string $currency
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompanyBankAccount extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<CompanyBankAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'bank_name', 'account_name', 'account_number',
        'iban', 'swift', 'currency', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];
}

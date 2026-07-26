<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CustomerCredit extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_id', 'invoice_id', 'return_id', 'payment_id',
        'created_by', 'credit_number', 'amount', 'remaining_amount', 'status', 'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_id', 'customer_credit_id', 'cash_box_id',
        'requested_by', 'approved_by', 'refund_number', 'intent_id',
        'method', 'amount', 'status', 'reason', 'external_reference',
        'approved_at', 'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
    ];
}

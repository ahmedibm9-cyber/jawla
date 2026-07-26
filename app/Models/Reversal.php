<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Reversal extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'original_type', 'original_id', 'action', 'performed_by',
        'reason', 'status', 'amount', 'result_type', 'result_id',
    ];

    protected $casts = ['amount' => 'decimal:2'];
}

<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCountSession extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'warehouse_id', 'opened_by', 'approved_by', 'status',
        'reason', 'submitted_at', 'approved_at', 'applied_at',
    ];

    protected $casts = [
        'submitted_at' => 'immutable_datetime',
        'approved_at' => 'immutable_datetime',
        'applied_at' => 'immutable_datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }
}

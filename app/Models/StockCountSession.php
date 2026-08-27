<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $warehouse_id
 * @property int $opened_by
 * @property int|null $approved_by
 * @property string $status
 * @property string|null $reason
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $applied_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return HasMany<StockCountItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }
}

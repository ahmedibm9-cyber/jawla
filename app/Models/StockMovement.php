<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property string $quantity_change
 * @property string|null $valuation_rate
 * @property string|null $reason
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int $user_id
 * @property Carbon|null $posting_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StockMovement extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'warehouse_id', 'product_id', 'batch_id',
        'quantity_change', 'valuation_rate', 'reason',
        'reference_type', 'reference_id', 'user_id', 'posting_date',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'valuation_rate' => 'decimal:2',
        'posting_date' => 'date',
    ];

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

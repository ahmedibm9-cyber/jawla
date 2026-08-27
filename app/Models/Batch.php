<?php

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $batch_number
 * @property Carbon|null $manufacture_date
 * @property Carbon|null $expiry_date
 * @property string|null $coa_file_path
 * @property int|null $supplier_id
 * @property Carbon|null $received_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id', 'batch_number', 'manufacture_date', 'expiry_date',
        'coa_file_path', 'supplier_id', 'received_date', 'is_active',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function expiresWithinDays(int $days): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lessThanOrEqualTo(now()->addDays($days));
    }

    /** Active, unexpired batches for a product, earliest-expiry first (FEFO; null expiry last).
     * @param  Builder<Batch>  $query
     * @return Builder<Batch>
     */
    public function scopeFefoForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()))
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC');
    }

    /** @param Builder<Batch> $query
     * @return Builder<Batch>
     */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), now()->addDays($days)]);
    }
}

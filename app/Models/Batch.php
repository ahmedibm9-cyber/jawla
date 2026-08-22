<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    use BelongsToCompany;
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

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

    /** Active, unexpired batches for a product, earliest-expiry first (FEFO; null expiry last). */
    public function scopeFefoForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()))
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC');
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), now()->addDays($days)]);
    }
}

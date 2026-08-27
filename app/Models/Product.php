<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $category_id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $packaging_type
 * @property string $unit
 * @property string $price
 * @property string $cost
 * @property bool $vat_applicable
 * @property bool $track_batch
 * @property bool $track_expiry
 * @property bool $has_variants
 * @property int|null $variant_of
 * @property bool $is_bundle
 * @property string $max_discount
 * @property string $valuation_method
 * @property string|null $image_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Product extends Model
{
    use Concerns\BelongsToCompany;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'company_id', 'category_id', 'sku', 'barcode', 'name_ar', 'name_en',
        'packaging_type', 'unit', 'price', 'cost', 'vat_applicable',
        'track_batch', 'track_expiry', 'has_variants', 'variant_of',
        'is_bundle', 'max_discount', 'valuation_method',
        'image_path', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'vat_applicable' => 'boolean',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'has_variants' => 'boolean',
        'is_bundle' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return HasMany<Stock, $this> */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** @return HasMany<ReturnItem, $this> */
    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}

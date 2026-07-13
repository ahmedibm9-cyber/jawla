<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;

    use SoftDeletes;
    use Concerns\BelongsToCompany;

    protected $fillable = [
        'company_id', 'category_id', 'sku', 'name_ar', 'name_en',
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

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function stocks(): HasMany { return $this->hasMany(Stock::class); }
    public function invoiceItems(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function returnItems(): HasMany { return $this->hasMany(ReturnItem::class); }
}

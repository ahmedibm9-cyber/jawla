<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'category_id', 'sku', 'name_ar', 'name_en',
        'unit', 'price', 'cost', 'vat_applicable', 'image_path', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'vat_applicable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function stocks(): HasMany { return $this->hasMany(Stock::class); }
    public function invoiceItems(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function returnItems(): HasMany { return $this->hasMany(ReturnItem::class); }
}

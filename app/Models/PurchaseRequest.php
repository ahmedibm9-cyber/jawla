<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'supplier_id', 'product_id',
        'quantity', 'offered_price', 'currency', 'payment_terms',
        'status',
        'sales_reviewed_by', 'sales_reviewed_at', 'sales_review_notes',
        'purchasing_reviewed_by', 'purchasing_reviewed_at', 'purchasing_review_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'offered_price' => 'decimal:2',
        'sales_reviewed_at' => 'datetime',
        'purchasing_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_reviewed_by');
    }

    public function purchasingReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchasing_reviewed_by');
    }
}

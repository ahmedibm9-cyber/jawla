<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'supplier_id', 'product_id',
        'quantity', 'offered_price', 'currency', 'payment_terms',
        'status', 'reviewed_by', 'review_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'offered_price' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
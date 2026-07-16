<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutOfStockRequest extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'product_id',
        'quantity_requested', 'notes', 'status',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

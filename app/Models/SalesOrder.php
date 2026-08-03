<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'customer_outlet_id', 'user_id', 'invoice_id', 'order_number', 'status', 'requested_delivery_date', 'notes', 'subtotal', 'total', 'submitted_at', 'approved_by', 'approved_at', 'fulfilled_at', 'cancelled_by', 'cancelled_at', 'decision_reason'];
    protected function casts(): array { return ['requested_delivery_date' => 'date', 'subtotal' => 'decimal:2', 'total' => 'decimal:2', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'fulfilled_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function outlet(): BelongsTo { return $this->belongsTo(CustomerOutlet::class, 'customer_outlet_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function items(): HasMany { return $this->hasMany(SalesOrderItem::class); }
    public function approvals(): MorphMany { return $this->morphMany(ApprovalRequest::class, 'approvable'); }
}

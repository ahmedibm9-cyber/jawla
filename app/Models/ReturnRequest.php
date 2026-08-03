<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReturnRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'user_id', 'visit_id', 'against_invoice_id', 'return_record_id', 'request_number', 'status', 'reason', 'total', 'submitted_at', 'approved_by', 'approved_at', 'received_by', 'received_at', 'receipt_notes', 'decision_reason'];
    protected function casts(): array { return ['total' => 'decimal:2', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'received_at' => 'datetime']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function againstInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'against_invoice_id'); }
    public function returnRecord(): BelongsTo { return $this->belongsTo(ReturnRecord::class); }
    public function items(): HasMany { return $this->hasMany(ReturnRequestItem::class); }
    public function approvals(): MorphMany { return $this->morphMany(ApprovalRequest::class, 'approvable'); }
    public function photos(): MorphMany { return $this->morphMany(Photo::class, 'photable'); }
}

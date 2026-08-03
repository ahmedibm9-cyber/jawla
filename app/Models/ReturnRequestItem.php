<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequestItem extends Model
{
    protected $fillable = ['return_request_id', 'invoice_item_id', 'quantity', 'condition', 'line_total'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'line_total' => 'decimal:2']; }
    public function request(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_request_id'); }
    public function invoiceItem(): BelongsTo { return $this->belongsTo(InvoiceItem::class); }
}

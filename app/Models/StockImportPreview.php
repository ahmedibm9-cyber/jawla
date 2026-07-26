<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockImportPreview extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'staged_by',
        'approved_by',
        'token_hash',
        'file_path',
        'file_checksum',
        'parsed_rows',
        'errors',
        'requires_approval',
        'status',
        'expires_at',
        'approved_at',
        'consumed_at',
    ];

    protected $hidden = ['token_hash', 'file_path'];

    protected $casts = [
        'parsed_rows' => 'array',
        'errors' => 'array',
        'requires_approval' => 'boolean',
        'expires_at' => 'immutable_datetime',
        'approved_at' => 'immutable_datetime',
        'consumed_at' => 'immutable_datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stagedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staged_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

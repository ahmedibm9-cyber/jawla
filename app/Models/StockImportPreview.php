<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $warehouse_id
 * @property int $staged_by
 * @property int|null $approved_by
 * @property string $token_hash
 * @property string $file_path
 * @property string $source_disk
 * @property string|null $file_checksum
 * @property array<string, mixed>|null $parsed_rows
 * @property array<string, mixed>|null $errors
 * @property bool $requires_approval
 * @property string $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
        'source_disk',
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

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function stagedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staged_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

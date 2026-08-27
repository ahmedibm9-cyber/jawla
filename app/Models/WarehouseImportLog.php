<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $imported_by
 * @property string $file_name
 * @property int $rows_imported
 * @property Carbon|null $imported_at
 * @property string $status
 * @property int $rows_total
 * @property int $rows_rejected
 * @property array<string, mixed>|null $errors
 * @property string|null $checksum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WarehouseImportLog extends Model
{
    protected $fillable = [
        'warehouse_id', 'imported_by', 'file_name', 'rows_imported', 'imported_at',
        'status', 'rows_total', 'rows_rejected', 'errors', 'checksum',
    ];

    protected $casts = [
        'rows_imported' => 'integer',
        'rows_total' => 'integer',
        'rows_rejected' => 'integer',
        'errors' => 'array',
        'imported_at' => 'datetime',
    ];

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

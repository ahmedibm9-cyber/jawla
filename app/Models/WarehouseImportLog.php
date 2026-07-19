<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

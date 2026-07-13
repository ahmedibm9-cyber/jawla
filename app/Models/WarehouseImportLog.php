<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseImportLog extends Model
{
    protected $fillable = ['warehouse_id', 'imported_by', 'file_name', 'rows_imported', 'imported_at'];
    protected $casts = ['rows_imported' => 'integer', 'imported_at' => 'datetime'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function importedBy(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
}
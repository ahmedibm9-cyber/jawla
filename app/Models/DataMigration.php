<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMigration extends Model
{
    protected $fillable = ['table_name', 'rows_migrated', 'migrated_by', 'source', 'migrated_at'];
    protected $casts = ['rows_migrated' => 'integer', 'migrated_at' => 'datetime'];

    public function migratedBy(): BelongsTo { return $this->belongsTo(User::class, 'migrated_by'); }
}
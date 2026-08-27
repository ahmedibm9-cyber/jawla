<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $table_name
 * @property int $rows_migrated
 * @property int $migrated_by
 * @property string $source
 * @property Carbon|null $migrated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DataMigration extends Model
{
    protected $fillable = ['table_name', 'rows_migrated', 'migrated_by', 'source', 'migrated_at'];

    protected $casts = ['rows_migrated' => 'integer', 'migrated_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function migratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'migrated_by');
    }
}

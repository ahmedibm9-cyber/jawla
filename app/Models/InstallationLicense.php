<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $license_id
 * @property string $licensee
 * @property string $installation_id
 * @property string $edition
 * @property int $max_users
 * @property array<string, mixed> $features
 * @property Carbon $valid_from
 * @property Carbon $expires_at
 * @property string $status
 * @property string $raw_document
 * @property string $signature
 * @property string $document_hash
 * @property Carbon|null $last_verified_at
 * @property int|null $installed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InstallationLicense extends Model
{
    protected $fillable = ['license_id', 'licensee', 'installation_id', 'edition', 'max_users', 'features', 'valid_from', 'expires_at', 'status', 'raw_document', 'signature', 'document_hash', 'last_verified_at', 'installed_by'];

    protected $hidden = ['raw_document', 'signature'];

    protected function casts(): array
    {
        return ['features' => 'array', 'valid_from' => 'date', 'expires_at' => 'date', 'last_verified_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}

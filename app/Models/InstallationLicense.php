<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationLicense extends Model
{
    protected $fillable = ['license_id', 'licensee', 'installation_id', 'edition', 'max_users', 'features', 'valid_from', 'expires_at', 'status', 'raw_document', 'signature', 'document_hash', 'last_verified_at', 'installed_by'];

    protected $hidden = ['raw_document', 'signature'];

    protected function casts(): array
    {
        return ['features' => 'array', 'valid_from' => 'date', 'expires_at' => 'date', 'last_verified_at' => 'datetime'];
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}

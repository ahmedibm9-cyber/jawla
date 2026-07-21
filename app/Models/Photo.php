<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'photable_type', 'photable_id',
        'disk', 'path', 'original_name', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function photable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Display URL for the photo. Object-storage disks (s3 / Railway bucket) are
     * private, so return a short-lived signed URL; the local 'public' disk
     * returns a plain public URL. Keyed off the disk's driver so it follows
     * whatever disk the row was stored on.
     */
    public function url(): string
    {
        $disk = Storage::disk($this->disk);

        if (config("filesystems.disks.{$this->disk}.driver") === 's3') {
            return $disk->temporaryUrl($this->path, now()->addMinutes(30));
        }

        return $disk->url($this->path);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $photable_type
 * @property int $photable_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Photo extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'photable_type', 'photable_id',
        'disk', 'path', 'original_name', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /** @return MorphTo<Model, $this> */
    public function photable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
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

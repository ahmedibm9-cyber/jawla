<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores rep-captured photos (visit reports, complaints, returns) and links them
 * to their owning record. The file is written to the configured disk and a Photo
 * row records the location + provenance (company, rep). Deleting a Photo removes
 * the underlying file too. Company scoping comes from BelongsToCompany.
 */
class PhotoService
{
    private const DIRECTORY = 'photos';

    /**
     * Target disk for new photos, config-driven so prod can use durable object
     * storage (Railway bucket / S3) while local/tests stay on 'public'. The disk
     * name is recorded per Photo row, so existing photos keep resolving/deleting
     * against whatever disk they were written to — a safe, gradual cutover.
     */
    private function disk(): string
    {
        return (string) config('filesystems.photo_disk', 'public');
    }

    public function store(UploadedFile $file, User $rep, ?Model $photable = null): Photo
    {
        $disk = $this->disk();
        $path = $file->store(self::DIRECTORY, $disk);

        $this->stripExif($path, $disk);

        return Photo::create([
            'company_id' => $rep->activeCompanyId(),
            'user_id' => $rep->id,
            'photable_type' => $photable?->getMorphClass(),
            'photable_id' => $photable?->getKey(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => Storage::disk($disk)->size($path),
        ]);
    }

    /**
     * Strip EXIF metadata from JPEG images to prevent GPS/camera leakage.
     * Other formats are stored as-is.
     */
    private function stripExif(string $path, string $disk): void
    {
        $full = Storage::disk($disk)->path($path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        if ($finfo->file($full) !== 'image/jpeg') {
            return;
        }

        $img = @imagecreatefromjpeg($full);
        if (! $img) {
            return;
        }

        imagejpeg($img, $full, 90);
        imagedestroy($img);
    }

    /** Link an already-stored photo to its owning record (e.g. after the parent is saved). */
    public function attach(Photo $photo, Model $photable): Photo
    {
        $photo->update([
            'photable_type' => $photable->getMorphClass(),
            'photable_id' => $photable->getKey(),
        ]);

        return $photo;
    }

    public function delete(Photo $photo): void
    {
        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();
    }
}

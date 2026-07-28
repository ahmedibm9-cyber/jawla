<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
        $disk = (string) config('filesystems.photo_disk', 'public');

        if (app()->isProduction() && $disk === 'public') {
            throw new RuntimeException('PHOTO_DISK must use private durable storage in production.');
        }

        return $disk;
    }

    public function store(UploadedFile $file, User $rep, ?Model $photable = null): Photo
    {
        $disk = $this->disk();
        $sanitized = $this->sanitize($file);
        $path = null;

        try {
            $path = $sanitized->store(self::DIRECTORY, $disk);

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('The photo could not be written to storage.');
            }

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
        } catch (\Throwable $exception) {
            if (is_string($path) && $path !== '') {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        } finally {
            @unlink($sanitized->getRealPath());
        }
    }

    /**
     * Re-encode supported images before upload. This strips EXIF and ancillary
     * metadata while the file is still local, so the same privacy guarantee
     * works for S3/object storage as it does for local disks.
     */
    private function sanitize(UploadedFile $file): UploadedFile
    {
        $source = $file->getRealPath();
        $contents = is_string($source) ? file_get_contents($source) : false;
        $image = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            throw new RuntimeException('The uploaded photo could not be decoded safely.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'jawla-photo-');

        if ($temporaryPath === false) {
            imagedestroy($image);

            throw new RuntimeException('A temporary photo file could not be created.');
        }

        try {
            $encoded = match ($mime) {
                'image/jpeg' => imagejpeg($image, $temporaryPath, 90),
                'image/png' => imagepng($image, $temporaryPath, 6),
                'image/webp' => function_exists('imagewebp') && imagewebp($image, $temporaryPath, 90),
                default => false,
            };
        } finally {
            imagedestroy($image);
        }

        if (! $encoded) {
            @unlink($temporaryPath);

            throw new RuntimeException('The uploaded photo format is not supported safely.');
        }

        return new UploadedFile(
            $temporaryPath,
            $file->getClientOriginalName(),
            is_string($mime) ? $mime : null,
            null,
            true,
        );
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

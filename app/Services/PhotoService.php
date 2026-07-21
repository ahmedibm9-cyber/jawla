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

        return Photo::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'photable_type' => $photable?->getMorphClass(),
            'photable_id' => $photable?->getKey(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
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

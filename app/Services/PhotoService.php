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
    private const DISK = 'public';

    private const DIRECTORY = 'photos';

    public function store(UploadedFile $file, User $rep, ?Model $photable = null): Photo
    {
        $path = $file->store(self::DIRECTORY, self::DISK);

        return Photo::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'photable_type' => $photable?->getMorphClass(),
            'photable_id' => $photable?->getKey(),
            'disk' => self::DISK,
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

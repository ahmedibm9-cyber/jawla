<?php

namespace App\Livewire\Concerns;

use App\Models\Photo;
use App\Services\PhotoService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

/**
 * Shared rep photo-capture wiring for host flows (visit report, complaint,
 * return). The nested <livewire:app.photo-capture /> stores each image
 * immediately and dispatches photo-captured/photo-removed; the host collects the
 * ids here and calls attachPhotos() after its record is saved so the photable is
 * set server-side. Photos require connectivity, so the offline path skips them.
 */
trait CapturesPhotos
{
    /** @var array<int, int> Photo ids captured for this record, attached on save. */
    public array $photoIds = [];

    #[On('photo-captured')]
    public function addPhoto(int $id): void
    {
        if (! in_array($id, $this->photoIds, true)) {
            $this->photoIds[] = $id;
        }
    }

    #[On('photo-removed')]
    public function dropPhoto(int $id): void
    {
        $this->photoIds = array_values(array_filter($this->photoIds, fn ($p) => $p !== $id));
    }

    /** Link any rep-captured photos (owned by this rep) to the saved record. */
    protected function attachPhotos(Model $record): void
    {
        if (empty($this->photoIds)) {
            return;
        }

        $photos = Photo::query()
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->photoIds)
            ->get();

        foreach ($photos as $photo) {
            app(PhotoService::class)->attach($photo, $record);
        }

        $this->photoIds = [];
    }
}

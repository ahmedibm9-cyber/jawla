<?php

namespace App\Livewire\App;

use App\Models\Photo;
use App\Services\PhotoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Reusable rep photo-capture widget (Wave-1). Drop into any flow (visit report,
 * complaint, return) with <livewire:app.photo-capture />. Each captured image is
 * stored immediately as a standalone Photo owned by the rep and a
 * 'photo-captured' event is dispatched with its id; the host flow collects those
 * ids and attaches them to its record on save (PhotoService::attach) — so the
 * photable is set server-side by the parent, never from client input.
 */
class PhotoCapture extends Component
{
    use WithFileUploads;

    public $photo;

    /** @var array<int, array{id: int, url: string, name: ?string}> */
    public array $stored = [];

    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $rep = Auth::user();
        if ($rep === null) {
            return;
        }

        $photo = app(PhotoService::class)->store($this->photo, $rep);

        $this->stored[] = ['id' => $photo->id, 'url' => $photo->url(), 'name' => $photo->original_name];
        $this->reset('photo');
        $this->dispatch('photo-captured', id: $photo->id);
    }

    public function removePhoto(int $id): void
    {
        $rep = Auth::user();
        $photo = Photo::query()->where('user_id', $rep?->id)->find($id);
        if ($photo === null) {
            return;
        }

        app(PhotoService::class)->delete($photo);
        $this->stored = array_values(array_filter($this->stored, fn ($p) => $p['id'] !== $id));
        $this->dispatch('photo-removed', id: $id);
    }

    public function render()
    {
        return view('livewire.app.photo-capture');
    }
}

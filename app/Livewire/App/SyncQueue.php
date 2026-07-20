<?php

namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CG2.3 — offline sync queue viewer. The queue itself lives in the browser's
 * IndexedDB outbox; this component only renders the shell. All listing, retry,
 * and discard is handled client-side by the Alpine view against window.jawlaSync.
 */
#[Layout('layouts.app')]
class SyncQueue extends Component
{
    public function render()
    {
        return view('livewire.app.sync-queue');
    }
}

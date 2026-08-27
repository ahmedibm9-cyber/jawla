<?php

namespace App\Livewire\App;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Completion notice only. Committed financial and stock actions are corrected
 * through manager-controlled compensating transactions, never rep undo.
 */
class ActionToast extends Component
{
    public string $message = '';

    #[On('action-completed')]
    public function show(string $message): void
    {
        $this->message = $message;
    }

    public function dismiss(): void
    {
        $this->message = '';
    }

    public function render(): View
    {
        return view('livewire.app.action-toast');
    }
}

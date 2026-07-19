<?php

namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Notifications extends Component
{
    use WithPagination;

    /** @var array<int, string> ids that were unread when the page was opened */
    public array $newIds = [];

    public function mount(): void
    {
        $unread = auth()->user()->unreadNotifications()->get();

        $this->newIds = $unread->pluck('id')->all();

        // Mark-read on open — persists across reloads.
        $unread->markAsRead();
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->simplePaginate(20);

        return view('livewire.app.notifications', [
            'notifications' => $notifications,
        ]);
    }
}

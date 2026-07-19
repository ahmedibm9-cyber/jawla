<?php

namespace App\Livewire\App;

use App\Models\Visit;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Visits extends Component
{
    use WithPagination;

    public function render()
    {
        $visits = Visit::query()
            ->where('user_id', auth()->id())
            ->with('customer')
            ->orderByDesc('created_at')
            ->simplePaginate(25);

        return view('livewire.app.visits', [
            'visits' => $visits,
        ]);
    }
}

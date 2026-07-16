<?php

namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MorePage extends Component
{
    public function render()
    {
        return view('livewire.app.more', [
            'user' => auth()->user(),
        ]);
    }
}

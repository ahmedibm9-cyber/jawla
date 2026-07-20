<?php

namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsPage extends Component
{
    public function render()
    {
        return view('livewire.app.settings', [
            'user' => auth()->user()->load('company'),
            'currentLocale' => app()->getLocale(),
        ]);
    }
}

<?php

namespace App\Filament\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user && $user->hasAnyRole(['sales_rep', 'rep'])) {
            return redirect()->intended('/app');
        }

        return redirect()->intended(Filament::getUrl());
    }
}

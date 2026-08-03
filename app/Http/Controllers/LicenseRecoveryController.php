<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseRecoveryController
{
    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('licenses.manage'), 403);

        return view('license-recovery');
    }

    public function store(Request $request, LicenseService $licenses): RedirectResponse
    {
        abort_unless($request->user()?->can('licenses.manage'), 403);
        $validated = $request->validate([
            'document' => ['required', 'string', 'max:10000'],
            'signature' => ['required', 'string', 'max:5000'],
        ]);
        $licenses->install($validated['document'], $validated['signature'], $request->user());

        return redirect('/admin')->with('status', __('app.license_installed'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SystemPageController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function adminRoot(): RedirectResponse
    {
        $user = auth()->user();

        if ($user && $user->hasAnyRole(['sales_rep', 'rep'])) {
            return redirect('/app');
        }

        if ($user && method_exists($user, 'canAccessPanel')) {
            return redirect('/admin/dashboard');
        }

        return redirect()->route('login');
    }

    public function offline(): Response
    {
        return response()->view('vendor.laravel.offline');
    }

    public function health(): Response
    {
        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
        } catch (\Throwable) {
            return response('degraded', 503)
                ->header('Content-Type', 'text/plain; charset=UTF-8')
                ->header('Cache-Control', 'no-store, private');
        }

        return response('ok', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    public function appLoginRedirect(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function salesFlowRedirect(): RedirectResponse
    {
        return redirect()->route('app.sell');
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'ar'], true), 404);

        session(['locale' => $locale]);

        return back();
    }

    public function adminLogout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

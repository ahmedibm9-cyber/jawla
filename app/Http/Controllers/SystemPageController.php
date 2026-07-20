<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SystemPageController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect('/admin/login');
    }

    public function adminRoot(): RedirectResponse
    {
        $user = auth()->user();

        if ($user && $user->hasRole('rep')) {
            return redirect('/app');
        }

        if ($user && method_exists($user, 'canAccessPanel')) {
            return redirect('/admin/dashboard');
        }

        return redirect('/admin/login');
    }

    public function offline(): Response
    {
        return response()->view('vendor.laravel.offline');
    }

    public function health(): Response
    {
        return response('ok', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'ar'], true), 404);

        session(['locale' => $locale]);

        return back();
    }

    // TEMPORARY: demo account seeder — remove after demo
    public function demoSeed(): Response
    {
        $company = Company::first();
        abort_unless($company, 500, 'no company');

        $admin = User::firstOrCreate(
            ['email' => 'demo-admin@jawla.test'],
            ['company_id' => $company->id, 'name' => 'Demo Admin', 'employee_code' => 'DEMO-001', 'password' => bcrypt('Demo2026!'), 'is_active' => true]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $rep = User::firstOrCreate(
            ['email' => 'demo-rep@jawla.test'],
            ['company_id' => $company->id, 'name' => 'Demo Rep', 'employee_code' => 'DEMO-002', 'password' => bcrypt('Demo2026!'), 'is_active' => true]
        );
        if (! $rep->hasRole('rep')) {
            $rep->assignRole('rep');
        }

        return response()->json([
            'admin' => ['email' => 'demo-admin@jawla.test', 'password' => 'Demo2026!', 'url' => '/admin/login'],
            'rep' => ['email' => 'demo-rep@jawla.test', 'password' => 'Demo2026!', 'url' => '/admin/login'],
        ]);
    }
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

        return redirect('/admin/login');
    }
}
